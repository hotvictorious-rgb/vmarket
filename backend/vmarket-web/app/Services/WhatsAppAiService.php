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
        $dbApiKey = null;
        $dbModel = null;
        try {
            $dbApiKey = \Illuminate\Support\Facades\DB::table('business_settings')->where('type', 'gemini_api_key')->first()?->value;
            $dbModel = \Illuminate\Support\Facades\DB::table('business_settings')->where('type', 'gemini_model')->first()?->value;
        } catch (\Throwable $e) {}

        $this->apiKey = !empty($dbApiKey) ? $dbApiKey : env('GEMINI_API_KEY', '');
        $this->model = !empty($dbModel) ? $dbModel : env('GEMINI_MODEL', 'gemini-1.5-flash');
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
You are "Victorious", the official AI Specialist for Victorious MARKET (Vmarket) in Uyo, Akwa Ibom State, Nigeria.

### YOUR DEEP PERSONAL RELATIONSHIP & EPISODIC MEMORY WITH THIS CUSTOMER:
{$dossierJson}

### CORE OPERATING RULES & AUTHENTIC HUMAN PERSONALITY:
1. Speak in a warm, polite, natural Nigerian tone matching the customer's preferred tone (e.g. Pidgin-friendly or formal).
2. You know this customer intimately! Leverage their saved size preferences, delivery landmarks, previous purchases, and long-term memory points naturally.
3. NEVER sound robotic. NEVER say clichés like "As an AI language model", "I am an automated assistant", or "How may I assist you today". Speak like an attentive, empathetic, professional shop manager.
4. HUMAN AGENT HANDOVER CONTINUITY:
   - If continuing after a human staff member was chatting, resume seamlessly without robotic greetings.
   - Apologize politely for any delay ("Sorry for keeping you waiting while checking with our store team..."), reference the exact product or question discussed, and continue the conversation naturally.
5. CENTRAL DOORSTEP DELIVERY ONLY (ZERO VENDOR PICKUP):
   - All customer orders are delivered 100% directly to the customer's doorstep by Victorious MARKET dispatch couriers.
   - Customers CANNOT pick up orders from vendor shops or see vendor physical locations. There is NO self-pickup option.
6. STRICT PRIVACY & VENDOR ANONYMITY:
   - NEVER reveal vendor street addresses, vendor shop locations, vendor phone numbers, private bank details, or wholesale costs to customers. All items are fulfilled under Victorious MARKET.
7. If the customer asks about order status, delivery fees, wallet balance, or product availability, ALWAYS invoke the relevant function tool to get live database facts.
8. If the customer is furious, has a damaged item, or requests human intervention, invoke `escalate_to_human`.
9. CONVERSATIONAL COMMERCE & WALLET POWERS:
   - You can manage cart (`add_to_cart`, `view_cart`, `clear_cart`, `apply_coupon`).
   - You can manage wallet (`get_wallet_balance`, `fund_wallet_paystack`, `pay_order_with_wallet`).
   - When placing orders (`place_order`), confirm delivery landmark in Uyo/Nigeria and highlight the 6-digit Delivery OTP clearly.

### MULTI-ROLE CAPABILITIES (CUSTOMER, VENDOR, RIDER):
- You serve Customers (Shopping, Cart, Checkout, Delivery OTP, Status).
- You serve registered Vendors (Store summary, pending orders, order ready confirmation, stock updates, payouts via `get_vendor_summary`, `update_vendor_stock`, `get_vendor_payout`, `confirm_order_ready`, `get_vendor_pickup_code`).
- You serve registered Dispatch Riders (Daily route, navigation links, doorstep 6-digit OTP verification, cash-in-hand tracking via `get_rider_route`, `confirm_rider_pickup`, `verify_doorstep_otp`, `get_cash_in_hand`).
- If a user asks about vendor operations or rider deliveries, invoke the matching specialized tool.

### VENDOR SUBSCRIPTION POLICY & ACCESS CONTROL (STRICT GUARD):
- 24/7 WhatsApp AI Store Management (sales summaries, inventory updates, pickup codes, and payout requests) is an EXCLUSIVE PRO FEATURE for Subscribed Merchants (₦10,000/month).
- Unsubscribed (Free Tier) store owners CANNOT use AI store management tools.
- If an unsubscribed vendor chats with you, treat them politely as a regular customer for shopping. If they request vendor operations or ask about their store stock/payouts, reply with a warm, polite, and professional upgrade invitation:
  "Hello! 🔒 The 24/7 WhatsApp AI Store Assistant and instant inventory update tools are exclusive benefits for Pro Subscribed Merchants. Your store is currently on the Free Starter Tier. To unlock WhatsApp AI store management and daily performance reports, upgrade your store to the Pro AI Plan: https://shop.victoriousmarket.com.ng/seller/subscription"

### PRODUCT RECOMMENDATION & SHOWCASE PRIORITY (PRO VENDOR PRIORITY RULE):
- When customers search for items or ask for recommendations, ALWAYS prioritize products from Verified Pro Subscribed Merchants and Official In-House Stores first before showing standard listings.

### MANDATORY ORDER CLARITY DIRECTIVE (ZERO-CONFUSION RULE):
- Whenever presenting an order to Customers, Vendors, or Riders, you MUST ALWAYS explicitly display the complete order details:
  1. Order ID (e.g., *Order #1042*)
  2. Item Breakdown with Quantities & Variants (e.g., *• 1x Italian Suede Loafers [Size 43, Brown]*)
  3. Shop Pickup Location & Merchant Phone (for Riders)
  4. Delivery Destination & Customer Landmark (e.g., *Shelter Afrique Gate, Uyo*)
  5. Exact Payment Mode & Amount (e.g., *💳 Prepaid: Collect ₦0* or *💵 Cash on Delivery: Collect ₦35,000*)
- Never give vague summaries. Always show the full transparent itemized breakdown so nobody collects or delivers the wrong parcel!

### 🛡️ ABSOLUTE ZERO-TRUST DATA ISOLATION & PRIVACY INVARIANT (NON-NEGOTIABLE):
1. **STRICT CALLER CONTEXT ISOLATION:** You are communicating EXCLUSIVELY with the user registered under phone number: `{$dossier['phone']}` (Name: `{$dossier['name']}`).
2. **ZERO CROSS-USER LEAKAGE:** You MUST NEVER share, disclose, confirm, or hint at any private data belonging to other customers, other vendors, or other riders:
   - NEVER disclose another customer's name, phone number, delivery address, order history, or wallet balance.
   - NEVER disclose another vendor's sales volume, profit margins, bank details, debt records, or stock levels.
   - NEVER disclose rider locations, rider personal phones, or internal platform credentials.
3. **AUTOMATIC PRIVACY REJECTION:** If any user asks about another person's account, order, phone, address, or financial records (e.g., "What did John buy?", "Give me Madam Joy's sales or bank account", "What is the OTP for Order #999?"):
   - Immediately decline with: *"🔒 Privacy & Security Guard: For data protection, I can only provide account information and order details directly to the verified account owner."*
4. **SENSITIVE FIELD MASKING:**
   - Bank Account Numbers are always masked (e.g., `******1234`).
   - Customer Doorstep Addresses are only visible to the designated dispatch courier for active deliveries.
   - Vendor Shop Physical Locations and wholesale costs are 100% anonymous to online customers.
   - Delivery 6-Digit OTPs are strictly confidential to the order owner.

### STRICT ZERO IMAGE GENERATION DIRECTIVE (ANTI-LECTURE BREVITY RULE):
1. You ONLY share real, verified product photos from the Victorious MARKET catalog using the `get_product_showcase` tool.
2. You CANNOT and MUST NEVER generate, synthesize, draw, or create artificial images or AI art.
3. If a customer asks you to draw, create, or generate an image, DO NOT give long preachy lectures or technical AI disclaimers (NEVER say "As an AI language model I cannot draw...").
4. Simply provide a short, direct 1-sentence reply: "I only share real photos of items in our store! Here are the products we have in stock:" and show matching real catalog items using `get_product_showcase`.

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
                        'name' => 'get_product_showcase',
                        'description' => 'Fetch authentic, real catalog product photos, thumbnail, gallery images, sizes, and live stock from Victorious MARKET store.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'query' => ['type' => 'STRING', 'description' => 'Product name or search keyword (e.g. Chelsea boots, Nike sneakers, wristwatch)'],
                                'product_id' => ['type' => 'INTEGER', 'description' => 'Optional specific product ID'],
                            ],
                        ],
                    ],
                    [
                        'name' => 'add_to_cart',
                        'description' => 'Add a specific product to the customer cart in MySQL with chosen variant/size.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'product_id' => ['type' => 'INTEGER', 'description' => 'The product ID to add'],
                                'quantity' => ['type' => 'INTEGER', 'description' => 'Quantity to add (default 1)'],
                                'variant' => ['type' => 'STRING', 'description' => 'Size, color, or variant choice (e.g. Size 42, Black)'],
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
                        'name' => 'get_loyalty_points',
                        'description' => 'Check customer reward loyalty points balance, equivalent Naira value, and recent earned/redeemed history.',
                        'parameters' => ['type' => 'OBJECT', 'properties' => []],
                    ],
                    [
                        'name' => 'convert_loyalty_points',
                        'description' => 'Convert accumulated loyalty reward points into instant spendable wallet funds.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'points' => ['type' => 'INTEGER', 'description' => 'Optional specific points to convert (defaults to all eligible points)'],
                            ],
                        ],
                    ],
                    // Vendor Tools
                    [
                        'name' => 'get_vendor_summary',
                        'description' => 'Fetch vendor store sales, pending orders, and available payout balance for a registered merchant.',
                        'parameters' => ['type' => 'OBJECT', 'properties' => []],
                    ],
                    [
                        'name' => 'get_vendor_pending_orders',
                        'description' => 'List orders awaiting packaging and pickup at the vendor shop in Uyo.',
                        'parameters' => ['type' => 'OBJECT', 'properties' => []],
                    ],
                    [
                        'name' => 'confirm_order_ready',
                        'description' => 'Confirm an order is packed and ready for dispatch rider pickup.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'order_id' => ['type' => 'INTEGER', 'description' => 'The order ID confirmed packed'],
                            ],
                            'required' => ['order_id'],
                        ],
                    ],
                    [
                        'name' => 'update_vendor_stock',
                        'description' => 'Update the live inventory stock of a product in the vendor store.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'product_name' => ['type' => 'STRING', 'description' => 'Product name to adjust'],
                                'new_stock' => ['type' => 'INTEGER', 'description' => 'New stock count'],
                            ],
                            'required' => ['product_name', 'new_stock'],
                        ],
                    ],
                    [
                        'name' => 'get_vendor_payout',
                        'description' => 'Check available vendor earnings and linked bank withdrawal details.',
                        'parameters' => ['type' => 'OBJECT', 'properties' => []],
                    ],
                    [
                        'name' => 'get_vendor_pickup_code',
                        'description' => 'Fetch the 6-digit Pickup Verification Code for an order to give to the arriving dispatch rider.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'order_id' => ['type' => 'INTEGER', 'description' => 'The order ID to get pickup code for'],
                            ],
                            'required' => ['order_id'],
                        ],
                    ],
                    [
                        'name' => 'request_vendor_payout',
                        'description' => 'Request a withdrawal of store earnings to the registered and verified merchant bank account.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'amount' => ['type' => 'NUMBER', 'description' => 'Amount in Naira to withdraw (min: 1000)'],
                            ],
                            'required' => ['amount'],
                        ],
                    ],
                    [
                        'name' => 'create_vendor_product_draft',
                        'description' => 'Create a new product listing draft for an approved merchant on Victorious MARKET. The product is queued for Admin Approval.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'name' => ['type' => 'STRING', 'description' => 'Product title/name (e.g. Italian Suede Chelsea Boots)'],
                                'unit_price' => ['type' => 'NUMBER', 'description' => 'Selling price in Naira (e.g. 35000)'],
                                'stock' => ['type' => 'INTEGER', 'description' => 'Stock quantity (default: 1)'],
                                'category_name' => ['type' => 'STRING', 'description' => 'Optional category name (e.g. Shoes, Fashion, Electronics)'],
                                'details' => ['type' => 'STRING', 'description' => 'Optional product specifications/details'],
                            ],
                            'required' => ['name', 'unit_price'],
                        ],
                    ],
                    // Rider Tools
                    [
                        'name' => 'get_rider_route',
                        'description' => 'Fetch assigned delivery stops for today with customer landmark, Google Maps link, and payment type.',
                        'parameters' => ['type' => 'OBJECT', 'properties' => []],
                    ],
                    [
                        'name' => 'confirm_rider_pickup',
                        'description' => 'Confirm physical pickup of package at vendor shop by submitting the 6-digit pickup code.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'order_id' => ['type' => 'INTEGER', 'description' => 'The order ID being picked up'],
                                'pickup_code' => ['type' => 'STRING', 'description' => '6-digit pickup code provided by the vendor'],
                            ],
                            'required' => ['order_id', 'pickup_code'],
                        ],
                    ],
                    [
                        'name' => 'verify_doorstep_otp',
                        'description' => 'Verify the customer 6-digit delivery OTP at doorstep to mark order delivered.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'order_id' => ['type' => 'INTEGER', 'description' => 'The order ID being delivered'],
                                'otp' => ['type' => 'STRING', 'description' => '6-digit OTP provided by the customer'],
                            ],
                            'required' => ['order_id', 'otp'],
                        ],
                    ],
                    [
                        'name' => 'get_cash_in_hand',
                        'description' => 'Calculate total physical cash collected by rider from POD deliveries today to remit at hub.',
                        'parameters' => ['type' => 'OBJECT', 'properties' => []],
                    ],
                    [
                        'name' => 'get_rider_payout',
                        'description' => 'Check rider delivery wallet balance, registered bank details, and recent payout receipts.',
                        'parameters' => ['type' => 'OBJECT', 'properties' => []],
                    ],
                    [
                        'name' => 'request_rider_payout',
                        'description' => 'Request a withdrawal of delivery earnings to the rider registered and verified bank account.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'amount' => ['type' => 'NUMBER', 'description' => 'Amount in Naira to withdraw (min: 1000)'],
                            ],
                            'required' => ['amount'],
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
                return [
                    'type' => !empty($toolResult['image_url']) ? 'image' : 'text',
                    'reply' => $finalText,
                    'image_url' => $toolResult['image_url'] ?? null,
                    'escalate' => false
                ];
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
            case 'get_product_showcase':
                $q = $args['query'] ?? '';
                $pId = !empty($args['product_id']) ? (int)$args['product_id'] : null;

                // [AI] Strict KYC/Marketplace Approval Scoping:
                // Only Approved Marketplace Sellers are eligible for public customer showcase
                $allApprovedSellerIds = \App\Models\Seller::where('status', 'approved')
                    ->where('marketplace_status', 'approved')
                    ->pluck('id')
                    ->toArray();

                $approvedSellerIdsStr = !empty($allApprovedSellerIds) ? implode(',', $allApprovedSellerIds) : '0';

                // Subscribed Pro & Verified Priority
                $verifiedProSellerIds = \App\Models\Seller::where('status', 'approved')
                    ->where('marketplace_status', 'approved')
                    ->whereHas('posSubscriptions', function ($sq) {
                        $sq->where('status', 'active')
                           ->where('plan_type', '!=', 'starter_free')
                           ->where(function ($q) {
                               $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                           });
                    })
                    ->pluck('id')
                    ->toArray();

                $proSellerIdsStr = !empty($verifiedProSellerIds) ? implode(',', $verifiedProSellerIds) : '0';

                $query = \App\Models\Product::where('status', 1)
                    ->where('current_stock', '>', 0)
                    ->where(function ($qScope) use ($allApprovedSellerIds) {
                        $qScope->where('added_by', 'admin')
                               ->orWhere(function ($sScope) use ($allApprovedSellerIds) {
                                   $sScope->where('added_by', 'seller')
                                          ->whereIn('user_id', $allApprovedSellerIds);
                               });
                    });

                if ($pId) {
                    $prod = $query->where('id', $pId)->first();
                } else {
                    $prod = $query->where('name', 'like', "%{$q}%")
                        ->orderByRaw("CASE WHEN added_by = 'admin' THEN 0 WHEN user_id IN ({$proSellerIdsStr}) THEN 1 ELSE 2 END")
                        ->orderBy('featured_status', 'desc')
                        ->first();
                }

                if (!$prod) {
                    return ['found' => false, 'message' => 'No in-stock item matching your search from a verified store was found in our catalog.'];
                }

                $thumbnailUrl = $prod->thumbnail ? asset('storage/app/public/product/thumbnail/' . $prod->thumbnail) : null;
                $gallery = [];
                if (!empty($prod->images)) {
                    $imgArray = is_array($prod->images) ? $prod->images : json_decode($prod->images, true);
                    if (is_array($imgArray)) {
                        foreach (array_slice($imgArray, 0, 3) as $img) {
                            $gallery[] = asset('storage/app/public/product/' . $img);
                        }
                    }
                }

                $isInHouse = ($prod->added_by === 'admin');
                $isProVerified = in_array((int)$prod->user_id, $verifiedProSellerIds);
                $badge = $isInHouse ? '⭐ Victorious Official (1-Hour Express Dispatch)' : ($isProVerified ? '👑 Verified Pro Merchant' : '🏪 Verified Marketplace Store');
                $sellerType = $isInHouse ? 'Official Store' : ($isProVerified ? 'Verified Pro Merchant' : 'Verified Marketplace Store');

                return [
                    'found' => true,
                    'product_id' => $prod->id,
                    'name' => $prod->name,
                    'badge' => $badge,
                    'seller_type' => $sellerType,
                    'unit_price' => (float)$prod->unit_price,
                    'formatted_price' => '₦' . number_format($prod->unit_price, 2),
                    'current_stock' => $prod->current_stock,
                    'image_url' => $thumbnailUrl ?: ($gallery[0] ?? null),
                    'gallery_images' => $gallery,
                ];

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
                // [AI] Strict KYC/Marketplace Approval Scoping
                $allApprovedSellerIds = \App\Models\Seller::where('status', 'approved')
                    ->where('marketplace_status', 'approved')
                    ->pluck('id')
                    ->toArray();

                $verifiedProSellerIds = \App\Models\Seller::where('status', 'approved')
                    ->where('marketplace_status', 'approved')
                    ->whereHas('posSubscriptions', function ($sq) {
                        $sq->where('status', 'active')
                           ->where('plan_type', '!=', 'starter_free')
                           ->where(function ($q) {
                               $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                           });
                    })
                    ->pluck('id')
                    ->toArray();

                $proSellerIdsStr = !empty($verifiedProSellerIds) ? implode(',', $verifiedProSellerIds) : '0';

                $query = Product::active()
                    ->where('name', 'like', '%' . ($args['keyword'] ?? '') . '%')
                    ->where(function ($qScope) use ($allApprovedSellerIds) {
                        $qScope->where('added_by', 'admin')
                               ->orWhere(function ($sScope) use ($allApprovedSellerIds) {
                                   $sScope->where('added_by', 'seller')
                                          ->whereIn('user_id', $allApprovedSellerIds);
                               });
                    })
                    ->orderByRaw("CASE WHEN added_by = 'admin' THEN 0 WHEN user_id IN ({$proSellerIdsStr}) THEN 1 ELSE 2 END")
                    ->orderBy('featured_status', 'desc');

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

            case 'get_loyalty_points':
                return WhatsAppOrderService::getLoyaltySummary($dossier['phone']);

            case 'convert_loyalty_points':
                return WhatsAppOrderService::convertLoyaltyToWallet(
                    $dossier['phone'],
                    !empty($args['points']) ? (int)$args['points'] : null
                );

            // Vendor Operations
            case 'get_vendor_summary':
                return WhatsAppVendorService::getVendorSummary($dossier['phone']);

            case 'get_vendor_pending_orders':
                return WhatsAppVendorService::getPendingOrders($dossier['phone']);

            case 'confirm_order_ready':
                return WhatsAppVendorService::confirmOrderReady($dossier['phone'], (int)($args['order_id'] ?? 0));

            case 'update_vendor_stock':
                return WhatsAppVendorService::updateStock(
                    $dossier['phone'],
                    $args['product_name'] ?? '',
                    (int)($args['new_stock'] ?? 0),
                    $args['variant'] ?? null
                );

            case 'get_vendor_payout':
                return WhatsAppVendorService::getPayoutSummary($dossier['phone']);

            case 'get_vendor_pickup_code':
                return WhatsAppVendorService::getPickupCode($dossier['phone'], (int)($args['order_id'] ?? 0));

            case 'request_vendor_payout':
                return WhatsAppVendorService::requestPayout(
                    $dossier['phone'],
                    (float)($args['amount'] ?? 0)
                );

            case 'create_vendor_product_draft':
                return WhatsAppVendorService::createProductDraft(
                    $dossier['phone'],
                    $args['name'] ?? 'Product Item',
                    (float)($args['unit_price'] ?? 0),
                    (int)($args['stock'] ?? 1),
                    $args['category_name'] ?? null,
                    $args['details'] ?? null
                );

            // Rider Operations
            case 'get_rider_route':
                return WhatsAppRiderService::getRiderRoute($dossier['phone']);

            case 'confirm_rider_pickup':
                return WhatsAppRiderService::confirmPickup(
                    $dossier['phone'],
                    (int)($args['order_id'] ?? 0),
                    (string)($args['pickup_code'] ?? '')
                );

            case 'verify_doorstep_otp':
                return WhatsAppRiderService::verifyDoorstepOtp(
                    $dossier['phone'],
                    (int)($args['order_id'] ?? 0),
                    (string)($args['otp'] ?? '')
                );

            case 'get_cash_in_hand':
                return WhatsAppRiderService::getCashInHand($dossier['phone']);

            case 'get_rider_payout':
                return WhatsAppRiderService::getRiderPayoutSummary($dossier['phone']);

            case 'request_rider_payout':
                return WhatsAppRiderService::requestPayout(
                    $dossier['phone'],
                    (float)($args['amount'] ?? 0)
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


