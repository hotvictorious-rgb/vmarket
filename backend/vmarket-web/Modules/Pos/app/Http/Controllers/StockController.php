<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * [AI] StockController — POS Module stock management.
 * Ported from Hysam standalone StockController.
 *
 * KEY CHANGES:
 * - Warehouse model → Shop model (shops table in unified DB)
 * - StockLevel model → product_stocks table (Vmarket's unified stock table)
 * - All mutations wrapped in DB::transaction with lockForUpdate (Pessimistic Concurrency)
 * - All queries scoped by seller_id (Zero Cross-Tenant Bleed)
 * - Inventory logs → pos_inventory_logs (unified DB, no cross-connection)
 *
 * Clients: Verified Merchant, Unverified Merchant Free POS.
 */
class StockController extends Controller
{
    protected function resolveAuthSellerId(): int
    {
        if (Auth::guard('vendor_employee')->check()) {
            return (int) Auth::guard('vendor_employee')->user()->seller_id;
        }
        return (int) Auth::guard('seller')->id();
    }

    protected function resolveActiveBranchId(Request $request, int $sellerId): int
    {
        return (int) $request->get('warehouse_id', session('pos_active_branch_id', 0));
    }

    /**
     * Stock Management Index — shows all products with stock levels per branch.
     */
    public function index(Request $request)
    {
        $sellerId  = $this->resolveAuthSellerId();
        $branchId  = $this->resolveActiveBranchId($request, $sellerId);
        $search    = trim($request->get('search', ''));
        $category  = $request->get('category');
        $status    = $request->get('stock_status');

        $branches = DB::table('shops')->where('seller_id', $sellerId)->get()->map(function ($b) {
            $b->code = $b->code ?? ('SHP-' . $b->id);
            return $b;
        });
        $activeBranch = $branches->firstWhere('id', $branchId) ?? $branches->first();
        if ($activeBranch) {
            $activeBranch->code = $activeBranch->code ?? ('SHP-' . $activeBranch->id);
        }

        $query = Product::where('user_id', $sellerId)->where('status', '!=', 2);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('pos_barcode', 'like', "%{$search}%");
            });
        }
        if ($category) {
            $query->where('pos_category', $category);
        }

        $products = $query->orderBy('name')->get()->map(function ($p) use ($sellerId) {
            $stockRow = DB::table('product_stocks')->where('product_id', $p->id)->first();
            $p->code           = $p->code ?? (string)$p->id;
            $p->product_code   = $p->code;
            $p->category       = $p->pos_category ?? 'General';
            $p->physical_stock = $stockRow ? (int) $stockRow->qty : (int) $p->current_stock;
            $p->reorder_level  = (int) ($p->pos_reorder_level ?? 5);
            return $p;
        });

        // Filter by stock status
        if ($status === 'OUT_OF_STOCK') {
            $products = $products->filter(fn($p) => $p->physical_stock <= 0)->values();
        } elseif ($status === 'LOW_STOCK') {
            $products = $products->filter(fn($p) => $p->physical_stock > 0 && $p->physical_stock <= $p->reorder_level)->values();
        } elseif ($status === 'IN_STOCK') {
            $products = $products->filter(fn($p) => $p->physical_stock > $p->reorder_level)->values();
        }

        $categories = Product::where('user_id', $sellerId)->distinct()->pluck('pos_category')->filter()->values();
        $activeWarehouse = $activeBranch;
        $warehouses = $branches;
        $incomingTransfers = collect([]);
        $totalItemsCount = $products->count();
        $totalStockUnits = $products->sum('physical_stock');
        $totalPhysicalUnits = $totalStockUnits;
        $lowStockCount = $products->filter(fn($p) => $p->physical_stock > 0 && $p->physical_stock <= $p->reorder_level)->count();
        $outOfStockCount = $products->filter(fn($p) => $p->physical_stock <= 0)->count();

        $stockLevels = $products->map(function ($p) {
            return (object) [
                'id'              => $p->id,
                'product_id'      => $p->id,
                'physical_stock'  => $p->physical_stock,
                'min_stock_alert' => $p->reorder_level,
                'product'         => (object) [
                    'id'        => $p->id,
                    'name'      => $p->name,
                    'code'      => $p->code,
                    'category'  => $p->category,
                    'unitPrice' => (float) $p->unit_price,
                ],
            ];
        });

        $allProducts = $products;

        return view('pos::stock.index', compact('products', 'allProducts', 'branches', 'warehouses', 'activeBranch', 'activeWarehouse', 'incomingTransfers', 'categories', 'search', 'category', 'status', 'totalItemsCount', 'totalStockUnits', 'totalPhysicalUnits', 'lowStockCount', 'outOfStockCount', 'stockLevels'));
    }

    /**
     * Stock In form.
     */
    public function stockInForm(Request $request)
    {
        return redirect()->route('pos.stock.index');
    }

    /**
     * Process Stock In — atomic, pessimistic-locked stock increment.
     */
    public function stockIn(Request $request)
    {
        $request->validate([
            'product_id'    => 'required|integer',
            'warehouse_id'  => 'required|integer',
            'quantity'      => 'required|integer|min:1',
            'supplier_name' => 'nullable|string|max:255',
            'notes'         => 'nullable|string|max:500',
        ]);

        $sellerId    = $this->resolveAuthSellerId();
        $productId   = (int) $request->product_id;
        $branchId    = (int) $request->warehouse_id;
        $qty         = (int) $request->quantity;
        $cashierName = Auth::guard('vendor_employee')->check()
            ? Auth::guard('vendor_employee')->user()->name
            : (Auth::guard('seller')->user()->f_name . ' ' . Auth::guard('seller')->user()->l_name);

        // [AI] IDOR: ensure product belongs to this seller
        $product = Product::where('id', $productId)->where('user_id', $sellerId)->firstOrFail();

        DB::transaction(function () use ($sellerId, $branchId, $productId, $product, $qty, $request, $cashierName) {
            // [AI] Pessimistic lock — prevents double stock-in from concurrent requests
            $product = Product::where('id', $productId)->where('user_id', $sellerId)->lockForUpdate()->first();

            DB::table('products')->where('id', $productId)->increment('current_stock', $qty);

            // Upsert product_stocks row
            $existingStock = DB::table('product_stocks')->where('product_id', $productId)->first();
            if ($existingStock) {
                DB::table('product_stocks')->where('product_id', $productId)->increment('qty', $qty);
            } else {
                DB::table('product_stocks')->insert([
                    'product_id' => $productId,
                    'variant'    => null,
                    'price'      => $product->unit_price,
                    'qty'        => $qty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('pos_inventory_logs')->insert([
                'seller_id'       => $sellerId,
                'branch_id'       => $branchId,
                'product_id'      => $productId,
                'product_name'    => $product->name,
                'product_code'    => $product->code,
                'type'            => 'STOCK_IN',
                'quantity_change' => $qty,
                'reference_type'  => 'pos_stock_in',
                'recorded_by'     => $cashierName,
                'notes'           => "From: " . ($request->supplier_name ?? 'Direct') . ". " . ($request->notes ?? ''),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('pos_activities')->insert([
                'seller_id'   => $sellerId,
                'branch_id'   => $branchId,
                'actor_id'    => (string) Auth::id(),
                'actor_name'  => $cashierName,
                'type'        => 'STOCK_IN',
                'description' => "{$cashierName} added {$qty} units of [{$product->name}] from " . ($request->supplier_name ?? 'Direct'),
                'metadata'    => json_encode(['product_id' => $productId, 'qty' => $qty, 'branch_id' => $branchId]),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });

        return redirect()->route('pos.stock.index')->with('success', "✓ {$qty} units of [{$product->name}] added to stock.");
    }

    /**
     * Inter-branch stock transfers.
     */
    public function transfers(Request $request)
    {
        $sellerId  = $this->resolveAuthSellerId();
        $transfers = \Illuminate\Support\Facades\Schema::hasTable('pos_stock_transfers')
            ? DB::table('pos_stock_transfers')->where('seller_id', $sellerId)->orderByDesc('created_at')->paginate(25)
            : new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 25, 1, ['path' => request()->url(), 'query' => request()->query()]);

        $allTransfers = $transfers;
        $pendingCount = 0;
        $receivedCount = 0;
        $discrepancyCount = 0;
        $datePreset = $request->get('date_preset', 'ALL');
        $carriers = collect([]);
        $branches = DB::table('shops')->where('seller_id', $sellerId)->get()->map(function ($b) {
            $b->code = $b->code ?? ('SHP-' . $b->id);
            return $b;
        });

        $warehouses  = $branches;
        $allProducts = Product::where('user_id', $sellerId)->get();

        return view('pos::stock.transfers', compact(
            'transfers', 'allTransfers', 'pendingCount', 'receivedCount', 'discrepancyCount',
            'datePreset', 'carriers', 'branches', 'warehouses', 'allProducts'
        ));
    }

    /**
     * Create inter-branch transfer.
     */
    public function createTransfer(Request $request)
    {
        $request->validate([
            'from_branch_id' => 'required|integer|different:to_branch_id',
            'to_branch_id'   => 'required|integer',
            'product_id'     => 'required|integer',
            'quantity'       => 'required|integer|min:1',
        ]);

        $sellerId  = $this->resolveAuthSellerId();
        $productId = (int) $request->product_id;

        // [AI] IDOR: both branches must belong to this seller
        $fromBranch = DB::table('shops')->where('id', $request->from_branch_id)->where('seller_id', $sellerId)->first();
        $toBranch   = DB::table('shops')->where('id', $request->to_branch_id)->where('seller_id', $sellerId)->first();
        $product    = Product::where('id', $productId)->where('user_id', $sellerId)->firstOrFail();

        abort_if(!$fromBranch || !$toBranch, 403, 'Unauthorized branch access.');

        $qty = (int) $request->quantity;
        $cashierName = Auth::guard('vendor_employee')->check()
            ? Auth::guard('vendor_employee')->user()->name
            : Auth::guard('seller')->user()->f_name;

        DB::transaction(function () use ($sellerId, $product, $productId, $qty, $fromBranch, $toBranch, $cashierName) {
            // [AI] Pessimistic lock prevents race-condition transfers
            Product::where('id', $productId)->where('user_id', $sellerId)->lockForUpdate()->first();

            // Note: since we use current_stock (total across branches) in unified DB,
            // we simply log the transfer. Branch-level stock uses pos_inventory_logs.
            DB::table('pos_inventory_logs')->insert([[
                'seller_id'       => $sellerId,
                'branch_id'       => $fromBranch->id,
                'product_id'      => $productId,
                'product_name'    => $product->name,
                'product_code'    => $product->code,
                'type'            => 'TRANSFER_OUT',
                'quantity_change' => -$qty,
                'reference_type'  => 'pos_transfer',
                'recorded_by'     => $cashierName,
                'notes'           => "Transfer OUT → {$toBranch->name}",
                'created_at'      => now(),
                'updated_at'      => now(),
            ], [
                'seller_id'       => $sellerId,
                'branch_id'       => $toBranch->id,
                'product_id'      => $productId,
                'product_name'    => $product->name,
                'product_code'    => $product->code,
                'type'            => 'TRANSFER_IN',
                'quantity_change' => $qty,
                'reference_type'  => 'pos_transfer',
                'recorded_by'     => $cashierName,
                'notes'           => "Transfer IN ← {$fromBranch->name}",
                'created_at'      => now(),
                'updated_at'      => now(),
            ]]);
        });

        return redirect()->route('pos.stock.transfers')->with('success', "✓ Transfer of {$qty} units completed.");
    }

    /**
     * Printable Transfer Waybill / Delivery Note.
     */
    public function waybill($id)
    {
        $sellerId = $this->resolveAuthSellerId();
        
        // [AI] Tenant Isolation: ensure the transfer waybill belongs to this seller
        $transfer = DB::table('pos_transfers')
            ->where('id', $id)
            ->where('seller_id', $sellerId)
            ->first();

        abort_if(!$transfer, 404, 'Transfer waybill not found.');

        // Load relations manually
        $transfer->source = DB::table('shops')->where('id', $transfer->origin_branch_id)->first();
        $transfer->destination = DB::table('shops')->where('id', $transfer->destination_branch_id)->first();
        
        $transfer->transfer_no = $transfer->waybill_number;
        $transfer->dispatched_by = $transfer->dispatched_by_id 
            ? (DB::table('vendor_employees')->where('id', $transfer->dispatched_by_id)->value('name') ?? 'Staff') 
            : 'Store Admin';
        $transfer->carrier_name = $transfer->driver_name ?? 'N/A';

        $transfer->items = DB::table('pos_transfer_items')
            ->where('transfer_id', $transfer->id)
            ->get();

        foreach ($transfer->items as $item) {
            // [AI] Tenant Isolation: product must belong to the active seller
            $product = Product::where('id', $item->product_id)->where('user_id', $sellerId)->first();
            $item->product_name = $product?->name ?? 'Unknown Product';
            $item->product_code = $product?->code ?? '';
            $item->dispatched_qty = $item->dispatched_quantity;
            $item->received_qty = $item->received_quantity;
            $item->discrepancy_qty = $item->variance_quantity;
        }

        $businessName = getWebConfig('company_name') ?? 'Victorious MARKET';
        $systemSettings = (object) ['businessName' => $businessName];

        return view('pos::stock.waybill', compact('transfer', 'systemSettings'));
    }

    /**
     * Stock adjustments list.
     */
    public function adjustments(Request $request)
    {
        $sellerId    = $this->resolveAuthSellerId();
        $adjustments = DB::table('pos_stock_adjustments')
            ->where('seller_id', $sellerId)
            ->orderByDesc('created_at')
            ->paginate(25);

        $totalAdjustmentsCount = DB::table('pos_stock_adjustments')->where('seller_id', $sellerId)->count();
        $totalUnitsLost = (int) abs(DB::table('pos_stock_adjustments')->where('seller_id', $sellerId)->where('quantity_change', '<', 0)->sum('quantity_change'));
        $datePreset = $request->get('date_preset', 'ALL');

        $products = Product::where('user_id', $sellerId)->orderBy('name')->get()->map(function ($p) {
            $p->code         = $p->code ?? (string)$p->id;
            $p->product_code = $p->code;
            return $p;
        });
        $branches = DB::table('shops')->where('seller_id', $sellerId)->get()->map(function ($b) {
            $b->code = $b->code ?? ('SHP-' . $b->id);
            return $b;
        });
        $warehouses = $branches;

        return view('pos::stock.adjustments', compact('adjustments', 'products', 'branches', 'warehouses', 'totalAdjustmentsCount', 'totalUnitsLost', 'datePreset'));
    }

    /**
     * Unsupplied orders awaiting customer pickup.
     */
    public function unsuppliedOrders(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $datePreset = $request->get('date_preset', 'ALL');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = DB::table('pos_sales')
            ->where('seller_id', $sellerId)
            ->whereIn('delivery_status', ['UNSUPPLIED', 'pending']);

        $unsuppliedSales = $query->orderByDesc('created_at')->paginate(25);
        $totalUnsuppliedOrders = (clone $query)->count();
        $totalUnsuppliedValue = (clone $query)->sum('total_amount');
        $branches = DB::table('shops')->where('seller_id', $sellerId)->get();
        $activeWarehouse = $branches->first();

        return view('pos::stock.unsupplied', compact(
            'unsuppliedSales', 'totalUnsuppliedOrders', 'totalUnsuppliedValue',
            'branches', 'activeWarehouse', 'datePreset', 'fromDate', 'toDate'
        ));
    }

    /**
     * Create stock adjustment (damage, expiry, shrinkage, found stock).
     */
    public function createAdjustment(Request $request)
    {
        $request->validate([
            'product_id'      => 'required|integer',
            'quantity_change' => 'required|integer|not_in:0',
            'reason'          => 'required|string',
            'notes'           => 'nullable|string|max:500',
        ]);

        $sellerId    = $this->resolveAuthSellerId();
        $productId   = (int) $request->product_id;
        $qtyChange   = (int) $request->quantity_change;
        $cashierName = Auth::guard('vendor_employee')->check()
            ? Auth::guard('vendor_employee')->user()->name
            : Auth::guard('seller')->user()->f_name;

        $product = Product::where('id', $productId)->where('user_id', $sellerId)->firstOrFail();

        DB::transaction(function () use ($sellerId, $product, $productId, $qtyChange, $request, $cashierName) {
            Product::where('id', $productId)->where('user_id', $sellerId)->lockForUpdate()->first();

            DB::table('products')->where('id', $productId)->increment('current_stock', $qtyChange);
            DB::table('product_stocks')->where('product_id', $productId)->increment('qty', $qtyChange);

            DB::table('pos_stock_adjustments')->insert([
                'seller_id'       => $sellerId,
                'product_id'      => $productId,
                'quantity_change' => $qtyChange,
                'reason'          => $request->reason,
                'adjusted_by'     => $cashierName,
                'notes'           => $request->notes,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            DB::table('pos_inventory_logs')->insert([
                'seller_id'       => $sellerId,
                'product_id'      => $productId,
                'product_name'    => $product->name,
                'product_code'    => $product->code,
                'type'            => 'ADJUSTMENT',
                'quantity_change' => $qtyChange,
                'reference_type'  => 'pos_adjustment',
                'recorded_by'     => $cashierName,
                'notes'           => $request->reason . ': ' . ($request->notes ?? ''),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        });

        $direction = $qtyChange > 0 ? '+' . $qtyChange : $qtyChange;
        return redirect()->route('pos.stock.adjustments')->with('success', "✓ Stock adjusted ({$direction} units) for [{$product->name}].");
    }
}
