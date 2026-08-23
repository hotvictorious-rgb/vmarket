<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\WhatsAppFaq;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppAiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->model = 'gemini-1.5-flash';
    }

    /**
     * [AI] Main entrypoint for autonomous customer inquiry reasoning.
     */
    public function generateResponse(string $phone, string $customerMessage, array $recentChatHistory = []): array
    {
        // 0. Security Guard: Blacklist check
        if (\App\Models\BlacklistedCustomer::isBlacklisted($phone)) {
            return [
                'type' => 'text',
                'reply' => "Hello. This WhatsApp account has been restricted by Victorious MARKET security due to a policy violation. Please contact support@victoriousmarket.com for assistance.",
                'escalate' => false,
            ];
        }

        // 1. Fetch Customer Past/Present/Future Dossier
        $dossier = CustomerAiRelationshipEngine::buildCustomerDossier($phone);

        // 2. Fetch Active FAQs from Knowledge Base
        $faqs = WhatsAppFaq::where('is_active', true)->take(20)->get(['question', 'answer'])->toArray();

        // 3. Construct Context Grounded System Prompt
        $systemPrompt = $this->buildSystemPrompt($dossier, $faqs);

        // 4. Construct Tools / Function Declarations
        $tools = $this->getFunctionToolDeclarations();

        // 5. Execute Gemini Reasoning with Function Calling Loop
        return $this->executeAiLoop($customerMessage, $systemPrompt, $tools, $dossier, $recentChatHistory);
    }

    protected function buildSystemPrompt(array $dossier, array $faqs): string
    {
        $dossierJson = json_encode($dossier, JSON_PRETTY_PRINT);
        $faqsJson = json_encode($faqs, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are "Victor", the official AI Sales & Customer Care Specialist for Victorious MARKET (Vmarket) in Uyo, Akwa Ibom State, Nigeria.

### YOUR DEEP PERSONAL RELATIONSHIP & EPISODIC MEMORY WITH THIS CUSTOMER:
{$dossierJson}

### CORE OPERATING RULES & AUTHENTIC HUMAN PERSONALITY:
1. Speak in a warm, polite, natural Nigerian tone matching the customer's preferred tone (e.g. Pidgin-friendly or formal).
2. You know this customer intimately! Leverage their saved size preferences, delivery landmarks, previous purchases, and long-term memory points naturally.
3. NEVER sound robotic. NEVER say clichés like "As an AI language model", "I am an automated assistant", or "How may I assist you today". Speak like an attentive, empathetic, professional shop manager.
4. HUMAN AGENT HANDOVER CONTINUITY:
   - If continuing after a human staff member was chatting, resume seamlessly without robotic greetings.
   - Apologize politely for any delay ("Sorry for keeping you waiting while checking with our store team..."), reference the exact product or question discussed, and continue the conversation naturally.
5. All orders are centrally fulfilled by Victorious MARKET logistics in Uyo. 
6. NEVER reveal vendor phone numbers, private bank details, or wholesale costs.
7. If the customer asks about order status, delivery fees, wallet balance, or product availability, ALWAYS invoke the relevant function tool to get live database facts.
8. If the customer is furious, has a damaged item, or requests human intervention, invoke `escalate_to_human`.
9. CONVERSATIONAL COMMERCE & WALLET POWERS:
   - You can manage cart (`add_to_cart`, `view_cart`, `clear_cart`, `apply_coupon`).
   - You can manage wallet (`get_wallet_balance`, `fund_wallet_paystack`, `pay_order_with_wallet`).
   - When placing orders (`place_order`), confirm delivery landmark in Uyo/Nigeria and highlight the 6-digit Delivery OTP clearly.

### STORE KNOWLEDGE BASE (FAQs):
{$faqsJson}
PROMPT;
    }

    protected function getFunctionToolDeclarations(): array
    {
        return [
            [
                'function_declarations' => [
                    [
                        'name' => 'add_to_cart',
                        'description' => 'Add a specific product to the customer cart in MySQL with chosen variant/size.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'product_id' => ['type' => 'INTEGER', 'description' => 'The ID of the product to add'],
                                'quantity' => ['type' => 'INTEGER', 'description' => 'Quantity to add (default 1)'],
                                'variant' => ['type' => 'STRING', 'description' => 'Optional chosen size or color variant (e.g. Size 43, Black)'],
                            ],
                            'required' => ['product_id'],
                        ],
                    ],
                    [
                        'name' => 'view_cart',
                        'description' => 'View the customer current cart items, subtotal, and grand total.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [],
                        ],
                    ],
                    [
                        'name' => 'clear_cart',
                        'description' => 'Clear all items from the customer cart.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [],
                        ],
                    ],
                    [
                        'name' => 'apply_coupon',
                        'description' => 'Apply a promotional coupon code to the customer cart and get the exact discount in Naira.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'coupon_code' => ['type' => 'STRING', 'description' => 'The promo code to validate and apply (e.g. UYO10)'],
                            ],
                            'required' => ['coupon_code'],
                        ],
                    ],
                    [
                        'name' => 'place_order',
                        'description' => 'Atomically places a formal order in MySQL, reserves stock, generates 6-digit Delivery OTP and Paystack link.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'delivery_address' => ['type' => 'STRING', 'description' => 'Full street delivery address or landmark in Uyo/Nigeria'],
                                'payment_method' => ['type' => 'STRING', 'description' => 'paystack or cash_on_delivery (default: cash_on_delivery)'],
                                'customer_name' => ['type' => 'STRING', 'description' => 'Customer full name'],
                                'coupon_code' => ['type' => 'STRING', 'description' => 'Optional coupon code used'],
                            ],
                            'required' => ['delivery_address'],
                        ],
                    ],
                    [
                        'name' => 'search_inventory',
                        'description' => 'Search products in store, live stock availability, and retail price in Naira.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'keyword' => ['type' => 'STRING', 'description' => 'Product name or search keyword (e.g. Nike sneakers, perfume)'],
                                'max_price' => ['type' => 'NUMBER', 'description' => 'Optional maximum price budget in Naira'],
                            ],
                            'required' => ['keyword'],
                        ],
                    ],
                    [
                        'name' => 'get_order_status',
                        'description' => 'Get real-time delivery status, rider contact, and 6-digit Delivery OTP for this customer.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'order_id' => ['type' => 'STRING', 'description' => 'Optional specific Order ID'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'get_shipping_rates',
                        'description' => 'Get official delivery fees and estimated arrival time for locations in Akwa Ibom/Nigeria.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'location' => ['type' => 'STRING', 'description' => 'Area or city name (e.g. Uyo Central, Shelter Afrique, Eket, Ikot Ekpene)'],
                            ],
                            'required' => ['location'],
                        ],
                    ],
                    [
                        'name' => 'generate_paystack_link',
                        'description' => 'Generate dynamic Paystack payment link for an active unpaid or Cash-on-Delivery order.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'order_id' => ['type' => 'STRING', 'description' => 'The order ID to generate link for'],
                            ],
                            'required' => ['order_id'],
                        ],
                    ],
                    [
                        'name' => 'get_wallet_balance',
                        'description' => 'Check the customer live verified Victorious MARKET wallet balance in Naira.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [],
                        ],
                    ],
                    [
                        'name' => 'fund_wallet_paystack',
                        'description' => 'Generate an instant Paystack payment link for the customer to add funds/top up their wallet.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'amount' => ['type' => 'NUMBER', 'description' => 'Amount in Naira to top up (e.g. 5000, 10000, 20000)'],
                            ],
                            'required' => ['amount'],
                        ],
                    ],
                    [
                        'name' => 'pay_order_with_wallet',
                        'description' => 'Atomically pay for an active order using the customer wallet balance with 0% gateway friction.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'order_id' => ['type' => 'INTEGER', 'description' => 'Optional specific Order ID to pay for'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'escalate_to_human',
                        'description' => 'Transfer the chat to a live support agent when the customer requests human help or has a dispute.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'reason' => ['type' => 'STRING', 'description' => 'Reason for escalation'],
                                'summary' => ['type' => 'STRING', 'description' => 'Brief 1-sentence issue summary for the human worker'],
                            ],
                            'required' => ['reason'],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function executeAiLoop(string $userMessage, string $systemPrompt, array $tools, array $dossier, array $chatHistory): array
    {
        if (empty($this->apiKey)) {
            return [
                'type' => 'text',
                'reply' => "Hello {$dossier['name']}! Welcome to Victorious MARKET. A customer service agent will attend to your message shortly.",
                'escalate' => true,
            ];
        }

        $contents = [];

        // Append recent chat history
        foreach ($chatHistory as $msg) {
            $role = ($msg['sender_type'] === 'customer') ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['message_body'] ?? '']],
            ];
        }

        // Current message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        try {
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(20)->post($apiUrl, [
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => $contents,
                'tools' => $tools,
            ]);

            if (!$response->successful()) {
                Log::error('[AI WhatsApp Gemini Error]', ['body' => $response->body()]);
                return [
                    'type' => 'text',
                    'reply' => "Hello {$dossier['name']}! Let me connect you with one of our support specialists right now.",
                    'escalate' => true,
                ];
            }

            $resJson = $response->json();
            $candidate = $resJson['candidates'][0]['content']['parts'][0] ?? [];

            // Check if AI requested a Function Call
            if (isset($candidate['functionCall'])) {
                $functionName = $candidate['functionCall']['name'];
                $functionArgs = $candidate['functionCall']['args'] ?? [];

                // Execute the function
                $toolResult = $this->invokeLocalTool($functionName, $functionArgs, $dossier);

                // If tool was escalate_to_human
                if ($functionName === 'escalate_to_human') {
                    return [
                        'type' => 'text',
                        'reply' => "I understand completely, {$dossier['name']}. I am transferring you to a senior customer support manager right now to assist you personally.",
                        'escalate' => true,
                        'escalation_summary' => $functionArgs['summary'] ?? $functionArgs['reason'] ?? '',
                    ];
                }

                // Send tool response back to Gemini for final natural language generation
                $contents[] = ['role' => 'model', 'parts' => [['functionCall' => $candidate['functionCall']]]];
                $contents[] = [
                    'role' => 'user',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $functionName,
                                'response' => ['content' => $toolResult],
                            ],
                        ],
                    ],
                ];

                $finalResponse = Http::timeout(20)->post($apiUrl, [
                    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents' => $contents,
                ]);

                $finalText = $finalResponse->json('candidates.0.content.parts.0.text') ?? "I have found the information for you!";
                return ['type' => 'text', 'reply' => $finalText, 'escalate' => false];
            }

            // Normal text response
            $replyText = $candidate['text'] ?? "Hello! How can I assist your shopping on Victorious MARKET today?";
            return ['type' => 'text', 'reply' => $replyText, 'escalate' => false];

        } catch (Exception $e) {
            Log::error('[AI WhatsApp Gemini Exception] ' . $e->getMessage());
            return [
                'type' => 'text',
                'reply' => "Hello {$dossier['name']}! Let me get a customer care specialist to assist you right away.",
                'escalate' => true,
            ];
        }
    }

    protected function invokeLocalTool(string $name, array $args, array $dossier): array
    {
        switch ($name) {
            case 'add_to_cart':
                return WhatsAppOrderService::addToCart(
                    $dossier['phone'],
                    (int)($args['product_id'] ?? 0),
                    (int)($args['quantity'] ?? 1),
                    $args['variant'] ?? null
                );

            case 'view_cart':
                return WhatsAppOrderService::getCartSummary($dossier['phone']);

            case 'clear_cart':
                $cleared = WhatsAppOrderService::clearCart($dossier['phone']);
                return ['status' => $cleared, 'message' => 'Cart cleared successfully.'];

            case 'apply_coupon':
                return WhatsAppOrderService::applyCoupon($dossier['phone'], $args['coupon_code'] ?? '');

            case 'place_order':
                return WhatsAppOrderService::placeOrder(
                    $dossier['phone'],
                    $args['delivery_address'] ?? 'Uyo, Akwa Ibom',
                    $args['payment_method'] ?? 'cash_on_delivery',
                    $args['customer_name'] ?? $dossier['name'],
                    $args['coupon_code'] ?? null
                );

            case 'search_inventory':
                $query = Product::active()->where('name', 'like', '%' . ($args['keyword'] ?? '') . '%');
                if (!empty($args['max_price'])) {
                    $query->where('unit_price', '<=', (float)$args['max_price']);
                }
                $products = $query->take(3)->get();
                $sanitized = [];
                foreach ($products as $p) {
                    $sanitized[] = WhatsAppCustomerTransformer::sanitizeProductForChat($p);
                }
                return ['found_count' => count($sanitized), 'products' => $sanitized];

            case 'get_order_status':
                return ['active_orders' => $dossier['active_orders'], 'recent_orders' => array_slice($dossier['recent_orders'], 0, 2)];

            case 'get_shipping_rates':
                $loc = strtolower($args['location'] ?? '');
                if (str_contains($loc, 'uyo') || str_contains($loc, 'shelter') || str_contains($loc, 'plaza') || str_contains($loc, 'ewet')) {
                    return ['city' => 'Uyo Central', 'rate' => '₦1,000', 'timeline' => '1 - 3 Hours (Same Day Express)'];
                } elseif (str_contains($loc, 'eket') || str_contains($loc, 'ikot ekpene') || str_contains($loc, 'oron')) {
                    return ['city' => 'Regional Hub (Akwa Ibom)', 'rate' => '₦2,500', 'timeline' => '24 Hours via Corridor Batch'];
                }
                return ['city' => 'Standard Delivery', 'rate' => '₦1,500', 'timeline' => '1 - 2 Business Days'];

            case 'generate_paystack_link':
                $requestedId = !empty($args['order_id']) ? (int)$args['order_id'] : null;
                $order = null;

                if ($requestedId && !empty($dossier['user_id'])) {
                    // [AI] Zero-Trust IDOR Guard: Verify order belongs to the chatting customer
                    $order = Order::where('id', $requestedId)->where('customer_id', $dossier['user_id'])->first();
                } elseif (!empty($dossier['active_orders'])) {
                    $orderId = $dossier['active_orders'][0]['order_id'] ?? null;
                    $order = $orderId ? Order::find($orderId) : null;
                }

                if ($order) {
                    return [
                        'order_id' => $order->id,
                        'order_amount' => (float)$order->order_amount,
                        'formatted_amount' => '₦' . number_format($order->order_amount, 2),
                        'payment_link' => url("/pay/order/{$order->id}"),
                        'instruction' => 'Click this link to pay securely with Card, Bank Transfer, or USSD via Paystack.',
                    ];
                }
                return ['error' => 'No active order matching your account was found to generate a payment link.'];

            case 'get_wallet_balance':
                return WhatsAppOrderService::getWalletSummary($dossier['phone']);

            case 'fund_wallet_paystack':
                return WhatsAppOrderService::generateWalletTopUpLink(
                    $dossier['phone'],
                    (float)($args['amount'] ?? 1000)
                );

            case 'pay_order_with_wallet':
                return WhatsAppOrderService::payWithWallet(
                    $dossier['phone'],
                    !empty($args['order_id']) ? (int)$args['order_id'] : null
                );

            default:
                return ['status' => 'acknowledged'];
        }
    }

    /**
     * [AI] Autonomous Vision Inspection of WhatsApp Customer Transfer Receipts.
     * Enforces Anti-Duplicate Session ID locking, Zero-Trust customer scoping & zero-privacy-leak responses.
     */
    public function processReceiptImage(string $phone, string $imageFullPath, ?int $orderId = null): array
    {
        $dossier = CustomerAiRelationshipEngine::buildCustomerDossier($phone);
        $order = null;

        if ($orderId && !empty($dossier['user_id'])) {
            // [AI] Zero-Trust IDOR Guard: Verify order belongs to chatting customer
            $order = Order::where('id', $orderId)->where('customer_id', $dossier['user_id'])->first();
        } elseif (!empty($dossier['active_orders'])) {
            $order = Order::find($dossier['active_orders'][0]['order_id']);
        }

        if (!$order) {
            return [
                'type' => 'text',
                'reply' => "Hello {$dossier['name']}! I received your transfer receipt, but I couldn't find an active unpaid order under your account. Please confirm your order first, or let me know if you want to place a new one!",
                'escalate' => false,
            ];
        }

        $ocrService = app(ReceiptOcrAiService::class);
        $inspection = $ocrService->inspectReceipt($imageFullPath, $order, (float)$order->order_amount);

        if (!$inspection['status']) {
            return [
                'type' => 'text',
                'reply' => "Hello {$dossier['name']}! I couldn't clearly read the details from this receipt image. Please send a clearer screenshot of your bank transfer receipt so we can verify your payment!",
                'escalate' => false,
            ];
        }

        // 1. Handle Duplicate Session ID (Zero Customer Privacy Leak)
        if ($inspection['is_duplicate']) {
            return [
                'type' => 'text',
                'reply' => "Hello {$dossier['name']}! ⚠️ This transfer reference (Ref: {$inspection['session_id']}) has already been recorded in our system for a previous order. Please provide a fresh, valid transfer receipt for Order #{$order->id} or pay securely via Paystack.",
                'escalate' => false,
            ];
        }

        // 2. Handle Underpaid Amount Mismatch
        if ($inspection['is_underpaid']) {
            $shortage = $inspection['target_amount'] - $inspection['amount'];
            return [
                'type' => 'text',
                'reply' => "Hello {$dossier['name']}! ℹ️ Your order total is {$inspection['formatted_target_amount']}, but your uploaded receipt shows {$inspection['formatted_amount']} (Remaining balance: ₦" . number_format($shortage, 2) . "). Please complete the balance transfer so we can verify and dispatch your rider!",
                'escalate' => false,
            ];
        }

        // 3. Attach receipt metadata to Order record
        $order->update([
            'bank_session_id' => $inspection['session_id'],
            'receipt_image' => $imageFullPath,
            'receipt_metadata' => $inspection,
        ]);

        return [
            'type' => 'text',
            'reply' => "Thank you, {$dossier['name']}! 🙏 I have received your {$inspection['formatted_amount']} transfer receipt (Ref: {$inspection['session_id']}). Our verification team has been notified and will confirm your credit shortly. Your 6-digit delivery OTP will be issued upon approval!",
            'escalate' => false,
            'inspection' => $inspection,
        ];
    }

    /**
     * [AI] Ghostwriter Auto-Resume: Seamlessly continues conversation where a human agent stepped away.
     */
    public function resumeHumanChat(string $phone, array $recentChatHistory = []): array
    {
        // 0. Security Guard
        if (\App\Models\BlacklistedCustomer::isBlacklisted($phone)) {
            return ['type' => 'text', 'reply' => '', 'escalate' => false];
        }

        // 1. Fetch Customer Dossier & Episodic Memories
        $dossier = CustomerAiRelationshipEngine::buildCustomerDossier($phone);
        $faqs = WhatsAppFaq::where('is_active', true)->take(20)->get(['question', 'answer'])->toArray();

        // 2. Extract the last unanswered customer message
        $lastCustomerMsg = "Hello! I am following up on my previous message.";
        foreach (array_reverse($recentChatHistory) as $msg) {
            if (($msg['sender_type'] ?? '') === 'customer') {
                $lastCustomerMsg = $msg['message_body'] ?? $lastCustomerMsg;
                break;
            }
        }

        // 3. Construct System Prompt & Tools
        $systemPrompt = $this->buildSystemPrompt($dossier, $faqs);
        $tools = $this->getFunctionToolDeclarations();

        // 4. Execute AI reasoning loop with full transcript context
        return $this->executeAiLoop($lastCustomerMsg, $systemPrompt, $tools, $dossier, $recentChatHistory);
    }
}


