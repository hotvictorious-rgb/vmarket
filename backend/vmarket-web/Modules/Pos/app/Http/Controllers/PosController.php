<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Seller;
use App\Models\VendorEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * [AI] PosController — Ported from Hysam standalone app into the unified Vmarket Pos Module.
 *
 * KEY DIFFERENCES FROM HYSAM ORIGINAL:
 * - Auth:  Uses `auth('seller')` (Vmarket Seller guard) instead of Hysam's local `auth()` guard.
 *          Uses `auth('vendor_employee')` for cashier employees.
 * - Shop:  Hysam `Warehouse` → Vmarket `Shop` model (same concept, unified table).
 * - Products: Reads from unified Vmarket `products` table (no cross-DB calls).
 * - Customers: Reads from unified Vmarket `customers` table (marketplace customers = POS customers).
 * - Sales: Writes to `pos_sales` + `pos_sale_items` (dedicated POS tables, not marketplace `orders`).
 * - Tenancy: All queries scoped to `seller_id` = authenticated seller (Zero Cross-Tenant Bleed).
 * - marketplace_status gate: Sellers with status='pos_only' can sell in-store but CANNOT list on marketplace.
 *
 * Clients: Verified Merchant POS terminal, Unverified Merchant Free POS terminal.
 */
class PosController extends Controller
{
    /**
     * [AI] Helper: Resolve the authenticated seller_id.
     * Works for both direct seller login and vendor_employee login.
     */
    protected function resolveAuthSellerId(): int
    {
        if (Auth::guard('vendor_employee')->check()) {
            return (int) Auth::guard('vendor_employee')->user()->seller_id;
        }
        return (int) Auth::guard('seller')->id();
    }

    /**
     * [AI] Helper: Resolve the active branch (shop) id.
     * Vendor employees share their seller's branches (no per-employee branch lock in DB yet).
     * Sellers can switch branches via the ?warehouse_id query param or session.
     */
    protected function resolveActiveBranchId(Request $request): int
    {
        $sellerId = $this->resolveAuthSellerId();
        $fallbackBranch = Shop::where('seller_id', $sellerId)->where('temporary_close', 0)->first();
        return (int) $request->get(
            'warehouse_id',
            session('pos_active_branch_id', $fallbackBranch?->id ?? 0)
        );
    }

    /**
     * Display the POS terminal interface.
     */
    public function index(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();

        // [AI] Load all branches belonging to this seller (Zero Cross-Tenant Bleed)
        $branches = Shop::where('seller_id', $sellerId)->get();

        if ($branches->isEmpty()) {
            // Auto-seed a default branch for new merchants
            $seller = Seller::findOrFail($sellerId);
            $defaultBranch = Shop::create([
                'seller_id'  => $sellerId,
                'name'       => $seller->f_name . "'s Store",
                'url'        => Str::slug($seller->f_name . '-store-' . Str::random(4)),
                'address'    => $seller->address ?? 'Main Branch',
            ]);
            $branches = collect([$defaultBranch]);
        }

        $activeBranchId = $this->resolveActiveBranchId($request);
        session(['pos_active_branch_id' => $activeBranchId]);
        $activeBranch = $branches->firstWhere('id', $activeBranchId) ?? $branches->first();

        // [AI] Load this seller's products from the unified catalog, scoped strictly to seller_id
        $products = Product::where('user_id', $sellerId)
            ->where('status', '!=', 2) // exclude archived/banned
            ->get()
            ->map(function ($product) use ($activeBranchId) {
                // Stock from unified product_stocks table
                $stockRow = DB::table('product_stocks')
                    ->where('product_id', $product->id)
                    ->first();
                $product->physical_stock  = $stockRow ? (int) $stockRow->qty : (int) $product->current_stock;
                $product->available_stock = max(0, $product->physical_stock);
                $product->pos_display_name = $product->name;
                return $product;
            });

        $categories = $products->pluck('pos_category')->merge($products->pluck('category.name'))->filter()->unique()->values();

        // POS in-store customers (seller-scoped)
        $customers = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->whereNotNull('customer_name')
            ->selectRaw('customer_id, customer_name, customer_phone, MAX(created_at) as last_visit, SUM(debt_amount) as total_debt')
            ->groupBy('customer_id', 'customer_name', 'customer_phone')
            ->orderByDesc('last_visit')
            ->limit(200)
            ->get();

        return view('pos::pos.index', compact('products', 'categories', 'branches', 'activeBranch', 'customers'));
    }

    /**
     * Quick-Register or Update a POS Customer directly from the checkout screen.
     */
    public function quickRegisterCustomer(Request $request)
    {
        $rawPhone = preg_replace('/[\s\-\(\)\+]/', '', trim($request->phone ?? ''));
        if (str_starts_with($rawPhone, '234') && strlen($rawPhone) === 13) {
            $rawPhone = '0' . substr($rawPhone, 3);
        }
        $request->merge(['phone' => $rawPhone]);

        $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => ['required', 'string', 'regex:/^0\d{10}$/'],
            'address' => 'nullable|string|max:500',
        ], [
            'phone.regex' => 'Customer phone must be exactly 11 digits (e.g. 08031234567).',
        ]);

        $sellerId   = $this->resolveAuthSellerId();
        $phone      = $rawPhone;
        $name       = trim($request->name);

        // [AI] Look up or create an in-store POS customer record (pos_sales history)
        // We use the unified Vmarket `customers` table (marketplace customers = POS customers).
        $customer = DB::table('customers')->where('phone', $phone)->first();
        if ($customer) {
            DB::table('customers')->where('id', $customer->id)->update([
                'name'       => $name,
                'address'    => $request->address ?? $customer->address,
                'updated_at' => now(),
            ]);
        } else {
            $customerId = DB::table('customers')->insertGetId([
                'name'       => $name,
                'phone'      => $phone,
                'address'    => $request->address,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $customer = DB::table('customers')->find($customerId);
        }

        return response()->json([
            'success'  => true,
            'message'  => "Customer {$name} registered successfully!",
            'customer' => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
            ],
        ]);
    }

    /**
     * Process POS Checkout — atomic sale + stock decrement.
     * [AI] Zero Cross-Tenant enforcement: writes scoped to seller_id.
     * [AI] Atomicity: DB::transaction wraps sale + stock + payment + activity log.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|integer',
            'items'        => 'required|array|min:1',
            'totalAmount'  => 'required|numeric|min:0',
            'paidAmount'   => 'required|numeric|min:0',
            'is_supplied'  => 'required',
        ]);

        $sellerId    = $this->resolveAuthSellerId();
        $branchId    = $this->resolveActiveBranchId($request);
        $cashierId   = Auth::guard('vendor_employee')->check() ? Auth::guard('vendor_employee')->id() : null;
        $cashierName = Auth::guard('vendor_employee')->check()
            ? Auth::guard('vendor_employee')->user()->name
            : (Auth::guard('seller')->user()->f_name . ' ' . Auth::guard('seller')->user()->l_name);

        $totalAmount = (float) $request->totalAmount;
        $paidAmount  = (float) $request->paidAmount;
        $debtAmount  = max(0, $totalAmount - $paidAmount);
        $isSupplied  = in_array(strtolower($request->is_supplied), ['1', 'yes', 'true', 'on']);

        $customerPhone = preg_replace('/[\s\-\(\)\+]/', '', trim($request->customerPhone ?? ''));
        if (str_starts_with($customerPhone, '234') && strlen($customerPhone) === 13) {
            $customerPhone = '0' . substr($customerPhone, 3);
        }
        $customerName = trim($request->customerName ?? 'Walk-in Customer');
        $customerId   = $request->customerId ? (int) $request->customerId : null;

        // [AI] ZERO-BYPASS DEBT RULE: credit/delayed-pickup MUST have registered customer + phone
        if ($debtAmount > 0 || !$isSupplied) {
            $reason = $debtAmount > 0 ? 'Credit / Part-Payment' : 'Delayed Pickup';
            if (empty($customerPhone) || !preg_match('/^0\d{10}$/', $customerPhone)) {
                $err = "🔒 Exactly 11-digit phone number required for {$reason}. Walk-in customers cannot take credit or delayed pickup.";
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'error' => $err], 422)
                    : back()->withErrors(['error' => $err])->withInput();
            }
            if (strtolower($customerName) === 'walk-in customer' || empty($customerName)) {
                $err = "🔒 Customer name is required for {$reason}.";
                return $request->wantsJson()
                    ? response()->json(['success' => false, 'error' => $err], 422)
                    : back()->withErrors(['error' => $err])->withInput();
            }
        }

        try {
            $saleId = DB::transaction(function () use (
                $sellerId, $branchId, $cashierId, $cashierName,
                $totalAmount, $paidAmount, $debtAmount, $isSupplied,
                $customerName, $customerPhone, $customerId, $request
            ) {
                // [AI] 1. Create POS Sale record
                $receiptNo = 'RCP-' . strtoupper(Str::random(8));
                $saleId = DB::table('pos_sales')->insertGetId([
                    'seller_id'       => $sellerId,
                    'branch_id'       => $branchId,
                    'cashier_id'      => $cashierId,
                    'cashier_name'    => $cashierName,
                    'customer_name'   => $customerName,
                    'customer_id'     => $customerId,
                    'customer_phone'  => $customerPhone ?: null,
                    'total_amount'    => $totalAmount,
                    'paid_amount'     => $paidAmount,
                    'cash_amount'     => (float) ($request->cashAmount ?? 0),
                    'pos_card_amount' => (float) ($request->posAmount ?? 0),
                    'transfer_amount' => (float) ($request->transferAmount ?? 0),
                    'debt_amount'     => $debtAmount,
                    'status'          => $isSupplied ? 'completed' : 'pending_delivery',
                    'delivery_status' => $isSupplied ? 'delivered' : 'pending',
                    'receipt_number'  => $receiptNo,
                    'note'            => $request->note,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // [AI] 2. Insert sale line items + decrement stock
                foreach ($request->items as $item) {
                    $productId = (int) $item['productId'];
                    $qty       = (int) $item['quantity'];
                    $unitPrice = (float) $item['unitPrice'];

                    // [AI] Verify product belongs to this seller (IDOR guard)
                    $product = Product::where('id', $productId)
                        ->where('user_id', $sellerId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    DB::table('pos_sale_items')->insert([
                        'pos_sale_id'    => $saleId,
                        'product_id'     => $productId,
                        'product_name'   => $product->name,
                        'product_code'   => $product->code,
                        'quantity'       => $qty,
                        'unit_price'     => $unitPrice,
                        'total_price'    => $qty * $unitPrice,
                        'purchase_price' => (float) $product->purchase_price,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    // [AI] Decrement unified stock (current_stock on product)
                    // Also decrement product_stocks row if it exists
                    DB::table('products')
                        ->where('id', $productId)
                        ->decrement('current_stock', $qty);

                    DB::table('product_stocks')
                        ->where('product_id', $productId)
                        ->decrement('qty', $qty);

                    // [AI] POS Inventory log
                    DB::table('pos_inventory_logs')->insert([
                        'seller_id'      => $sellerId,
                        'branch_id'      => $branchId,
                        'product_id'     => $productId,
                        'product_name'   => $product->name,
                        'product_code'   => $product->code,
                        'type'           => 'SALE',
                        'quantity_change' => -$qty,
                        'reference_id'   => (string) $saleId,
                        'reference_type' => 'pos_sale',
                        'recorded_by'    => $cashierName,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }

                // [AI] 3. Record POS payment methods
                $paymentMethods = [
                    'cash'         => (float) ($request->cashAmount ?? 0),
                    'pos_card'     => (float) ($request->posAmount ?? 0),
                    'bank_transfer' => (float) ($request->transferAmount ?? 0),
                ];
                foreach ($paymentMethods as $method => $amount) {
                    if ($amount > 0) {
                        DB::table('pos_payments')->insert([
                            'pos_sale_id' => $saleId,
                            'seller_id'   => $sellerId,
                            'amount'      => $amount,
                            'method'      => $method,
                            'recorded_by' => $cashierName,
                            'paid_at'     => now(),
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
                if ($debtAmount > 0) {
                    DB::table('pos_payments')->insert([
                        'pos_sale_id' => $saleId,
                        'seller_id'   => $sellerId,
                        'amount'      => $debtAmount,
                        'method'      => 'debt',
                        'recorded_by' => $cashierName,
                        'paid_at'     => null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }

                // [AI] 4. POS Activity log
                DB::table('pos_activities')->insert([
                    'seller_id'   => $sellerId,
                    'branch_id'   => $branchId,
                    'actor_id'    => (string) ($cashierId ?? $sellerId),
                    'actor_name'  => $cashierName,
                    'type'        => 'SALE',
                    'description' => "POS Sale #{$receiptNo} — ₦" . number_format($totalAmount, 2) . " for {$customerName}",
                    'metadata'    => json_encode(['pos_sale_id' => $saleId, 'items' => count($request->items)]),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                return $saleId;
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success'    => true,
                    'message'    => 'Sale completed successfully!',
                    'saleId'     => $saleId,
                    'receiptUrl' => route('pos.receipt', $saleId),
                ]);
            }

            return redirect()->route('pos.receipt', $saleId)->with('success', 'Sale recorded successfully!');

        } catch (\Throwable $e) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'error' => $e->getMessage()], 422)
                : back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Printable Receipt / Invoice.
     */
    public function receipt($id)
    {
        $sellerId = $this->resolveAuthSellerId();

        // [AI] Zero-Trust IDOR: scope receipt strictly to this seller
        $sale = DB::table('pos_sales')
            ->where('id', $id)
            ->where('seller_id', $sellerId)
            ->first();

        abort_if(!$sale, 404, 'Receipt not found.');

        $items = DB::table('pos_sale_items')->where('pos_sale_id', $id)->get();
        $branch = Shop::where('seller_id', $sellerId)->find($sale->branch_id);
        $seller = Seller::find($sellerId);

        return view('pos::pos.receipt', compact('sale', 'items', 'branch', 'seller'));
    }

    /**
     * Sales Returns screen.
     */
    public function returns(Request $request)
    {
        $sellerId    = $this->resolveAuthSellerId();
        $datePreset  = $request->get('date_preset', 'ALL');
        $fromDate    = $request->get('from_date');
        $toDate      = $request->get('to_date');
        $search      = trim($request->get('search', ''));

        $query = DB::table('pos_sales_returns')->where('seller_id', $sellerId);

        if ($fromDate && $toDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($fromDate)->startOfDay(),
                Carbon::parse($toDate)->endOfDay(),
            ]);
        } elseif ($datePreset === 'TODAY') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($datePreset === 'THIS_WEEK') {
            $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(),
            ]);
        } elseif ($datePreset === 'THIS_MONTH') {
            $query->whereBetween('created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(),
            ]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        $recentReturns    = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $totalRefundValue = (clone $query)->sum('refund_amount');
        $totalReturnsCount = (clone $query)->count();

        // Recent sales for the return-initiation dropdown
        $sales = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        $branches = Shop::where('seller_id', $sellerId)->get();

        return view('pos::pos.returns', compact(
            'recentReturns', 'totalRefundValue', 'totalReturnsCount',
            'sales', 'branches', 'datePreset', 'fromDate', 'toDate', 'search'
        ));
    }

    /**
     * Process a Sales Return — restores stock, records refund.
     */
    public function processReturn(Request $request)
    {
        $request->validate([
            'sale_id'       => 'required|integer',
            'warehouse_id'  => 'required|integer',
            'items'         => 'required|array|min:1',
            'refund_method' => 'required|string',
            'reason'        => 'required|string',
        ]);

        $sellerId    = $this->resolveAuthSellerId();
        $cashierName = Auth::guard('vendor_employee')->check()
            ? Auth::guard('vendor_employee')->user()->name
            : (Auth::guard('seller')->user()->f_name . ' ' . Auth::guard('seller')->user()->l_name);

        // [AI] IDOR check: ensure the sale belongs to this seller
        $sale = DB::table('pos_sales')
            ->where('id', $request->sale_id)
            ->where('seller_id', $sellerId)
            ->first();
        abort_if(!$sale, 403, 'Unauthorized.');

        try {
            DB::transaction(function () use ($sellerId, $sale, $request, $cashierName) {
                foreach ($request->items as $item) {
                    $productId = (int) $item['product_id'];
                    $qty       = (int) $item['quantity'];
                    $refund    = (float) ($item['refund_amount'] ?? 0);

                    // [AI] IDOR: product must belong to this seller
                    $product = Product::where('id', $productId)->where('user_id', $sellerId)->firstOrFail();

                    DB::table('pos_sales_returns')->insert([
                        'pos_sale_id'     => $sale->id,
                        'seller_id'       => $sellerId,
                        'branch_id'       => $sale->branch_id,
                        'product_id'      => $productId,
                        'product_name'    => $product->name,
                        'product_code'    => $product->code,
                        'quantity'        => $qty,
                        'refund_amount'   => $refund,
                        'reason'          => $request->reason,
                        'stock_restocked' => true,
                        'processed_by'    => $cashierName,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    // [AI] Restock: increment current_stock and product_stocks
                    DB::table('products')->where('id', $productId)->increment('current_stock', $qty);
                    DB::table('product_stocks')->where('product_id', $productId)->increment('qty', $qty);

                    DB::table('pos_inventory_logs')->insert([
                        'seller_id'       => $sellerId,
                        'branch_id'       => $sale->branch_id,
                        'product_id'      => $productId,
                        'product_name'    => $product->name,
                        'product_code'    => $product->code,
                        'type'            => 'RETURN',
                        'quantity_change' => $qty,
                        'reference_id'    => (string) $sale->id,
                        'reference_type'  => 'pos_sale_return',
                        'recorded_by'     => $cashierName,
                        'notes'           => $request->reason,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            });

            return redirect()->route('pos.returns')->with('success', '✓ Return processed. Items restocked.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
