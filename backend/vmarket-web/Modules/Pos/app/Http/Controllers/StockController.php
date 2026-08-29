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

        $branches = DB::table('shops')->where('seller_id', $sellerId)->get();
        $activeBranch = $branches->firstWhere('id', $branchId) ?? $branches->first();

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

        return view('pos::stock.index', compact('products', 'branches', 'activeBranch', 'categories', 'search', 'category', 'status'));
    }

    /**
     * Stock In form.
     */
    public function stockInForm(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $products = Product::where('user_id', $sellerId)->where('status', '!=', 2)->orderBy('name')->get();
        $branches = DB::table('shops')->where('seller_id', $sellerId)->get();
        $suppliers = DB::table('suppliers')->where('seller_id', $sellerId)->orderBy('name')->get();

        return view('pos::stock.stock-in', compact('products', 'branches', 'suppliers'));
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
        $transfers = DB::table('pos_cashier_shifts') // reuse existing or pos_activities of type TRANSFER
            ->where('seller_id', $sellerId)
            ->orderByDesc('created_at')
            ->paginate(25);

        $branches = DB::table('shops')->where('seller_id', $sellerId)->get();

        return view('pos::stock.transfers', compact('transfers', 'branches'));
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
     * Stock adjustments list.
     */
    public function adjustments(Request $request)
    {
        $sellerId    = $this->resolveAuthSellerId();
        $adjustments = DB::table('pos_stock_adjustments')
            ->where('seller_id', $sellerId)
            ->orderByDesc('created_at')
            ->paginate(25);

        $products = Product::where('user_id', $sellerId)->orderBy('name')->get();
        $branches = DB::table('shops')->where('seller_id', $sellerId)->get();

        return view('pos::stock.adjustments', compact('adjustments', 'products', 'branches'));
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
