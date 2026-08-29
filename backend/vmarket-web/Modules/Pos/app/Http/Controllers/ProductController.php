<?php

namespace Modules\Pos\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * [AI] ProductController — POS product management (catalog scoped to this seller).
 * Ported from Hysam standalone ProductController.
 *
 * KEY CHANGES:
 * - Operates on the unified Vmarket `products` table (user_id = seller_id).
 * - New products are inserted as `status=0, request_status=0` (POS draft, not yet on marketplace).
 * - Sellers can promote products to marketplace via the Vendor Panel (separate flow).
 * - All queries strictly scoped to `user_id = seller_id` (IDOR protection).
 */
class ProductController extends Controller
{
    protected function resolveAuthSellerId(): int
    {
        if (Auth::guard('vendor_employee')->check()) {
            return (int) Auth::guard('vendor_employee')->user()->seller_id;
        }
        return (int) Auth::guard('seller')->id();
    }

    public function index(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $search   = trim($request->get('search', ''));
        $category = $request->get('category');
        $status   = $request->get('stock_status');

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

        $products = $query->orderBy('name')->get()->map(function ($p) {
            $p->physical_stock = max(0, (int) $p->current_stock);
            $p->reorder_level  = (int) ($p->pos_reorder_level ?? 5);
            return $p;
        });

        if ($status === 'OUT_OF_STOCK') {
            $products = $products->filter(fn($p) => $p->physical_stock <= 0)->values();
        } elseif ($status === 'LOW_STOCK') {
            $products = $products->filter(fn($p) => $p->physical_stock > 0 && $p->physical_stock <= $p->reorder_level)->values();
        }

        $categories = Product::where('user_id', $sellerId)->distinct()->pluck('pos_category')->filter()->values();
        $branches   = DB::table('shops')->where('seller_id', $sellerId)->get();
        $warehouses = $branches;

        return view('pos::products.index', compact('products', 'categories', 'branches', 'warehouses', 'search', 'category', 'status'));
    }

    public function create()
    {
        $sellerId   = $this->resolveAuthSellerId();
        $categories = Product::where('user_id', $sellerId)->distinct()->pluck('pos_category')->filter()->values();

        return view('pos::products.create', compact('categories'));
    }

    /**
     * Store new product directly in the unified Vmarket products table.
     * [AI] New products are created as drafts (status=0). They appear in POS immediately
     * but require Super Admin approval (request_status=1) to list on the marketplace.
     */
    public function store(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();

        $request->validate([
            'name'         => 'required|string|max:255',
            'pos_category' => 'nullable|string|max:100',
            'unit_price'   => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'current_stock'  => 'required|integer|min:0',
            'pos_barcode'    => 'nullable|string|max:100',
            'pos_reorder_level' => 'nullable|integer|min:0',
        ]);

        $slug = Str::slug($request->name) . '-' . Str::lower(Str::random(5));

        $productId = DB::table('products')->insertGetId([
            'user_id'          => $sellerId,
            'added_by'         => 'seller',
            'name'             => $request->name,
            'code'             => $request->pos_barcode ?? strtoupper(Str::random(8)),
            'slug'             => $slug,
            'pos_barcode'      => $request->pos_barcode,
            'pos_category'     => $request->pos_category,
            'pos_reorder_level' => (int) ($request->pos_reorder_level ?? 5),
            'category_id'      => 1, // Default POS category
            'category_ids'     => json_encode([['id' => '1', 'position' => 1]]),
            'unit_price'       => (float) $request->unit_price,
            'purchase_price'   => (float) ($request->purchase_price ?? 0),
            'current_stock'    => (int) $request->current_stock,
            'minimum_order_qty' => 1,
            'min_qty'          => 1,
            'unit'             => 'pc',
            'status'           => 0,           // [AI] 0 = POS draft, not on marketplace
            'request_status'   => 0,           // [AI] 0 = pending Super Admin approval
            'published'        => 0,
            'images'           => json_encode([]),
            'color_image'      => json_encode([]),
            'thumbnail'        => '',
            'details'          => $request->description ?? '',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Seed product_stocks row
        DB::table('product_stocks')->insert([
            'product_id' => $productId,
            'variant'    => null,
            'price'      => (float) $request->unit_price,
            'qty'        => (int) $request->current_stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.products.index')->with('success', "✓ Product [{$request->name}] added to your POS catalog.");
    }

    public function edit(int $id)
    {
        $sellerId   = $this->resolveAuthSellerId();
        // [AI] IDOR: product must belong to this seller
        $product    = Product::where('id', $id)->where('user_id', $sellerId)->firstOrFail();
        $categories = Product::where('user_id', $sellerId)->distinct()->pluck('pos_category')->filter()->values();

        return view('pos::products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        $product  = Product::where('id', $id)->where('user_id', $sellerId)->firstOrFail();

        $request->validate([
            'name'              => 'required|string|max:255',
            'unit_price'        => 'required|numeric|min:0',
            'purchase_price'    => 'nullable|numeric|min:0',
            'current_stock'     => 'required|integer|min:0',
            'pos_barcode'       => 'nullable|string|max:100',
            'pos_category'      => 'nullable|string|max:100',
            'pos_reorder_level' => 'nullable|integer|min:0',
        ]);

        DB::table('products')->where('id', $id)->where('user_id', $sellerId)->update([
            'name'              => $request->name,
            'pos_barcode'       => $request->pos_barcode,
            'pos_category'      => $request->pos_category,
            'pos_reorder_level' => (int) ($request->pos_reorder_level ?? 5),
            'unit_price'        => (float) $request->unit_price,
            'purchase_price'    => (float) ($request->purchase_price ?? 0),
            'current_stock'     => (int) $request->current_stock,
            'details'           => $request->description ?? $product->details,
            'updated_at'        => now(),
        ]);

        DB::table('product_stocks')->where('product_id', $id)->update([
            'price'      => (float) $request->unit_price,
            'qty'        => (int) $request->current_stock,
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.products.index')->with('success', "✓ Product [{$request->name}] updated.");
    }

    public function destroy(int $id)
    {
        $sellerId = $this->resolveAuthSellerId();
        // [AI] IDOR: must own the product
        $product  = Product::where('id', $id)->where('user_id', $sellerId)->firstOrFail();

        // [AI] Soft-archive instead of hard delete — preserves sale history references
        DB::table('products')->where('id', $id)->where('user_id', $sellerId)->update([
            'status'     => 2, // archived/inactive
            'updated_at' => now(),
        ]);

        return redirect()->route('pos.products.index')->with('success', "Product [{$product->name}] archived from POS catalog.");
    }

    /**
     * Download CSV layout template for bulk POS product registration.
     */
    public function downloadCsvTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="vmarket_pos_products_template.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'code', 'pos_category', 'unit_price', 'purchase_price', 'current_stock', 'pos_reorder_level']);
            fputcsv($handle, ['Kings Vegetable Oil (25L)', 'KINGS-OIL-25L', 'Oils & Fats', '46500', '40000', '30', '5']);
            fputcsv($handle, ['Peak Milk Powder (900g)', 'PEAK-MILK-900G', 'Provisions', '7200', '6500', '100', '15']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Bulk import POS products from an uploaded CSV sheet.
     */
    public function importCsv(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        $header = fgetcsv($handle);
        if (!$header) {
            return redirect()->route('pos.products.index')->with('error', 'Uploaded CSV file is empty or invalid.');
        }

        // Normalize header keys
        $headerMap = [];
        foreach ($header as $index => $col) {
            $cleaned = strtolower(trim(str_replace([' ', '_', '-'], '', $col)));
            $headerMap[$cleaned] = $index;
        }

        $importedCount = 0;
        $updatedCount = 0;

        try {
            DB::transaction(function () use ($handle, $headerMap, $sellerId, &$importedCount, &$updatedCount) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty(array_filter($row))) continue;

                    $name = $row[$headerMap['name'] ?? -1] ?? null;
                    if (!$name) continue;

                    $code = $row[$headerMap['code'] ?? $headerMap['sku'] ?? -1] ?? null;
                    if (!$code) {
                        $code = 'SKU-' . strtoupper(Str::random(6));
                    } else {
                        $code = strtoupper(trim($code));
                    }

                    $category = $row[$headerMap['poscategory'] ?? $headerMap['category'] ?? -1] ?? 'General';
                    $unitPrice = (float) ($row[$headerMap['unitprice'] ?? $headerMap['price'] ?? -1] ?? 0);
                    $purchasePrice = (float) ($row[$headerMap['purchaseprice'] ?? -1] ?? 0);
                    $stock = (int) ($row[$headerMap['currentstock'] ?? $headerMap['stock'] ?? $headerMap['quantity'] ?? -1] ?? 0);
                    $minStock = (int) ($row[$headerMap['posreorderlevel'] ?? $headerMap['reorderlevel'] ?? $headerMap['minstock'] ?? -1] ?? 5);

                    // Scope lookup strictly to current seller
                    $product = Product::where('user_id', $sellerId)->where('code', $code)->first();

                    if ($product) {
                        DB::table('products')->where('id', $product->id)->update([
                            'name'              => $name,
                            'pos_category'      => $category,
                            'pos_reorder_level' => $minStock,
                            'unit_price'        => $unitPrice > 0 ? $unitPrice : $product->unit_price,
                            'purchase_price'    => $purchasePrice > 0 ? $purchasePrice : $product->purchase_price,
                            'current_stock'     => $stock > 0 ? $stock : $product->current_stock,
                            'updated_at'        => now(),
                        ]);

                        DB::table('product_stocks')->where('product_id', $product->id)->update([
                            'price'      => $unitPrice > 0 ? $unitPrice : $product->unit_price,
                            'qty'        => $stock > 0 ? $stock : $product->current_stock,
                            'updated_at' => now(),
                        ]);

                        $updatedCount++;
                    } else {
                        $slug = Str::slug($name) . '-' . Str::lower(Str::random(5));
                        $productId = DB::table('products')->insertGetId([
                            'user_id'          => $sellerId,
                            'added_by'         => 'seller',
                            'name'             => $name,
                            'code'             => $code,
                            'slug'             => $slug,
                            'pos_barcode'      => $code,
                            'pos_category'     => $category,
                            'pos_reorder_level' => $minStock,
                            'category_id'      => 1,
                            'category_ids'     => json_encode([['id' => '1', 'position' => 1]]),
                            'unit_price'       => $unitPrice,
                            'purchase_price'   => $purchasePrice,
                            'current_stock'    => $stock,
                            'minimum_order_qty' => 1,
                            'min_qty'          => 1,
                            'unit'             => 'pc',
                            'status'           => 0,
                            'request_status'   => 0,
                            'published'        => 0,
                            'images'           => json_encode([]),
                            'color_image'      => json_encode([]),
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ]);

                        DB::table('product_stocks')->insert([
                            'product_id' => $productId,
                            'sku'        => $code,
                            'price'      => $unitPrice,
                            'qty'        => $stock,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $importedCount++;
                    }
                }
            });
        } catch (\Throwable $e) {
            fclose($handle);
            return redirect()->route('pos.products.index')->with('error', 'Error during CSV import: ' . $e->getMessage());
        }

        fclose($handle);
        return redirect()->route('pos.products.index')->with('success', "✓ Bulk import complete! Added {$importedCount} new products, updated {$updatedCount} existing items.");
    }

    /**
     * Export master POS product catalog to CSV.
     */
    public function exportCsv(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $fileName = "vmarket_pos_products_catalog_" . date('Y_m_d_His') . ".csv";

        return response()->stream(function () use ($sellerId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['SKU / Code', 'Product Name', 'Category', 'Selling Price (NGN)', 'Purchase Price (NGN)', 'Min Stock Alert', 'Current Stock', 'Asset Value (NGN)']);

            $products = Product::where('user_id', $sellerId)->where('status', '!=', 2)->orderBy('name')->get();
            foreach ($products as $p) {
                fputcsv($handle, [
                    $p->code,
                    $p->name,
                    $p->pos_category ?? 'General',
                    $p->unit_price,
                    $p->purchase_price,
                    $p->pos_reorder_level ?? 5,
                    $p->current_stock,
                    $p->current_stock * (float) $p->unit_price
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Export master POS product catalog to JSON for AI analysis.
     */
    public function exportJson(Request $request)
    {
        $sellerId = $this->resolveAuthSellerId();
        $fileName = "vmarket_pos_products_catalog_" . date('Y_m_d_His') . ".json";

        $products = Product::where('user_id', $sellerId)->where('status', '!=', 2)->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'pos_category' => $p->pos_category ?? 'General',
                'unit_price' => (float) $p->unit_price,
                'purchase_price' => (float) $p->purchase_price,
                'pos_reorder_level' => (int) ($p->pos_reorder_level ?? 5),
                'current_stock' => (int) $p->current_stock,
                'total_asset_value' => $p->current_stock * (float) $p->unit_price,
            ];
        });

        return response()->json([
            'metadata' => [
                'report' => 'Vmarket Products & Price Catalog',
                'generated_at' => now()->toIso8601String(),
                'total_skus' => $products->count(),
            ],
            'products' => $products,
        ], 200, [
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ], JSON_PRETTY_PRINT);
    }
}
