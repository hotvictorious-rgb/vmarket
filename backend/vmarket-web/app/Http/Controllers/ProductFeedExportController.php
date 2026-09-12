<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductFeedExportController extends Controller
{
    /**
     * Admin Panel View for Product Feeds and Catalogs.
     */
    public function index(Request $request)
    {
        $token = self::getFeedToken();
        $categories = Category::where(['position' => 0])->get();
        $totalProducts = Product::active()->count();
        $inhouseProducts = Product::active()->where('added_by', 'admin')->count();
        $vendorProducts = Product::active()->where('added_by', 'seller')->count();

        return view('admin-views.product.product-feeds', [
            'token' => $token,
            'categories' => $categories,
            'totalProducts' => $totalProducts,
            'inhouseProducts' => $inhouseProducts,
            'vendorProducts' => $vendorProducts,
        ]);
    }

    /**
     * Regenerate the Super Admin secret global feed token.
     */
    public function regenerateToken(Request $request)
    {
        $token = 'vm_feed_' . Str::random(32);
        BusinessSetting::updateOrCreate(
            ['type' => 'product_feed_export_token'],
            ['value' => $token, 'updated_at' => now()]
        );

        Toastr::success(translate('feed_security_token_regenerated_successfully'));
        return back();
    }

    /**
     * Vendor Web Dashboard: Manage Vendor Channels & Isolated Feeds.
     */
    public function vendorIndex(Request $request)
    {
        $seller = auth('seller')->user();
        if (!$seller) {
            Toastr::error(translate('unauthorized_access'));
            return redirect()->route('vendor.auth.login');
        }

        // Lazy-generate vendor feed token if not already present
        $feedToken = $seller->getOrCreateFeedToken();
        $shop = $seller->shop;

        $totalProducts = Product::where(['added_by' => 'seller', 'user_id' => $seller->id])->count();
        $activeProducts = Product::where(['added_by' => 'seller', 'user_id' => $seller->id, 'status' => 1, 'request_status' => 1])->count();
        $outOfStockProducts = Product::where(['added_by' => 'seller', 'user_id' => $seller->id, 'current_stock' => 0])->count();

        return view('vendor-views.product.feeds.index', [
            'seller' => $seller,
            'shop' => $shop,
            'feedToken' => $feedToken,
            'maskedToken' => $seller->masked_feed_token,
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'outOfStockProducts' => $outOfStockProducts,
        ]);
    }

    /**
     * Vendor Web Dashboard: Regenerate and invalidate Vendor Feed Token.
     */
    public function vendorRegenerateToken(Request $request)
    {
        $seller = auth('seller')->user();
        if (!$seller) {
            Toastr::error(translate('unauthorized_access'));
            return redirect()->route('vendor.auth.login');
        }

        $seller->generateFeedToken();

        Toastr::success(translate('feed_security_token_regenerated_successfully._Previous_token_immediately_invalidated.'));
        return back();
    }

    /**
     * Get or generate the permanent secret token for live admin platform feeds.
     */
    public static function getFeedToken(): string
    {
        $tokenSetting = BusinessSetting::where('type', 'product_feed_export_token')->first();
        if ($tokenSetting && !empty($tokenSetting->value)) {
            return $tokenSetting->value;
        }

        $token = 'vm_feed_' . Str::random(32);
        BusinessSetting::updateOrCreate(
            ['type' => 'product_feed_export_token'],
            ['value' => $token, 'updated_at' => now()]
        );

        return $token;
    }

    /**
     * [AI] Zero-Trust Feed Authentication & Tenant Resolution Engine.
     * Evaluates incoming token against:
     * 1. Global Platform Admin Token -> sets context: 'admin' (can view all or filter).
     * 2. Vendor-Scoped Feed Token -> sets context: 'vendor' hard-locked to that specific Seller model.
     * 
     * Security invariant:
     * For vendor tokens, any client query parameters (e.g. vendor_id, scope) are strictly ignored.
     */
    public function authenticateFeedRequest(Request $request): array
    {
        $providedToken = $request->query('token') ?? $request->header('X-Feed-Token');
        if (empty($providedToken)) {
            return ['authenticated' => false, 'scope' => null, 'seller' => null];
        }

        // 1. Check Super Admin Global Token
        $serverAdminToken = self::getFeedToken();
        if (hash_equals($serverAdminToken, $providedToken)) {
            return [
                'authenticated' => true,
                'scope' => 'admin',
                'seller' => null,
            ];
        }

        // 2. Check Vendor-Scoped Token (Exact Identity Lookup)
        // [AI] Strict Security Guard: Vendor must be both status='approved' AND marketplace_status='approved'
        $seller = Seller::where('feed_token', $providedToken)
            ->where('status', 'approved')
            ->where('marketplace_status', 'approved')
            ->first();

        if ($seller) {
            return [
                'authenticated' => true,
                'scope' => 'vendor',
                'seller' => $seller,
            ];
        }

        return ['authenticated' => false, 'scope' => null, 'seller' => null];
    }

    /**
     * [AI] Build Tenant-Isolated Products Query.
     * Enforces Zero-Trust isolation based on authenticated context.
     */
    public function getFilteredProductsQuery(Request $request, array $authContext)
    {
        $query = Product::marketplaceEligible()->with(['brand', 'category', 'rating']);

        // [AI] Strict Vendor Scoping: If request is authenticated via vendor token,
        // hard-lock query exclusively to this vendor. Completely ignore any client-supplied vendor_id/scope.
        if ($authContext['scope'] === 'vendor' && $authContext['seller']) {
            return $query->where([
                'added_by' => 'seller',
                'user_id' => $authContext['seller']->id,
            ])
            ->when($request->query('in_stock_only') == '1', function ($q) {
                return $q->where('marketplace_availability', 'in_stock');
            })
            ->latest('updated_at');
        }

        // [AI] Super Admin Scope: Allows platform-wide filtering
        return $query
            ->when($request->query('scope') === 'inhouse', function ($q) {
                return $q->where('added_by', 'admin');
            })
            ->when($request->query('scope') === 'vendor', function ($q) {
                return $q->where('added_by', 'seller');
            })
            ->when($request->filled('vendor_id'), function ($q) use ($request) {
                return $q->where(['added_by' => 'seller', 'user_id' => $request->query('vendor_id')]);
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                return $q->where('category_id', $request->query('category_id'));
            })
            ->when($request->query('in_stock_only') == '1', function ($q) {
                return $q->where('marketplace_availability', 'in_stock');
            })
            ->latest('updated_at');
    }

    /**
     * 1. Google Merchant Center (Google Shopping) RSS 2.0 XML Feed.
     * Supports both Admin Platform Feed and Vendor-Isolated Catalog Feeds.
     */
    public function googleMerchantXml(Request $request): Response
    {
        $auth = $this->authenticateFeedRequest($request);
        if (!$auth['authenticated']) {
            return response('<error>Unauthorized feed token. Access denied.</error>', 403, [
                'Content-Type' => 'application/xml; charset=utf-8'
            ]);
        }

        $products = $this->getFilteredProductsQuery($request, $auth)->limit(5000)->get();
        
        $channelTitle = ($auth['scope'] === 'vendor' && $auth['seller'] && $auth['seller']->shop)
            ? $auth['seller']->shop->name . ' - Victorious MARKET'
            : (getWebConfig('company_name') ?? 'Victorious MARKET');

        $siteUrl = ($auth['scope'] === 'vendor' && $auth['seller'] && $auth['seller']->shop)
            ? route('vendor-store', $auth['seller']->shop->slug)
            : url('/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>' . htmlspecialchars($channelTitle, ENT_XML1, 'UTF-8') . " Product Feed</title>\n";
        $xml .= '    <link>' . htmlspecialchars($siteUrl, ENT_XML1, 'UTF-8') . "</link>\n";
        $xml .= '    <description>Official Google Shopping Catalog Feed for ' . htmlspecialchars($channelTitle, ENT_XML1, 'UTF-8') . "</description>\n";

        foreach ($products as $product) {
            $productUrl = route('product', $product->slug ?? $product->id);
            $imageUrl = is_array($product->thumbnail_full_url)
                ? ($product->thumbnail_full_url['path'] ?? asset('public/assets/front-end/img/image-place-holder.png'))
                : ($product->thumbnail_full_url ?? asset('public/assets/front-end/img/image-place-holder.png'));

            $price = number_format((float)$product->unit_price, 2, '.', '') . ' NGN';
            $availability = ($product->marketplace_availability === 'in_stock') ? 'in stock' : 'out of stock';
            $brand = $product->brand ? $product->brand->name : $channelTitle;
            $categoryName = $product->category ? $product->category->name : 'General';
            $description = !empty($product->details) ? strip_tags($product->details) : $product->name;
            $description = mb_substr(trim(preg_replace('/\s+/', ' ', $description)), 0, 4990);

            $xml .= "    <item>\n";
            $xml .= '      <g:id>' . htmlspecialchars((string)($product->code ?: ('VM-' . $product->id)), ENT_XML1, 'UTF-8') . "</g:id>\n";
            $xml .= '      <g:title>' . htmlspecialchars($product->name, ENT_XML1, 'UTF-8') . "</g:title>\n";
            $xml .= '      <g:description>' . htmlspecialchars($description, ENT_XML1, 'UTF-8') . "</g:description>\n";
            $xml .= '      <g:link>' . htmlspecialchars($productUrl, ENT_XML1, 'UTF-8') . "</g:link>\n";
            $xml .= '      <g:image_link>' . htmlspecialchars($imageUrl, ENT_XML1, 'UTF-8') . "</g:image_link>\n";
            $xml .= '      <g:availability>' . $availability . "</g:availability>\n";
            $xml .= '      <g:price>' . $price . "</g:price>\n";

            if ($product->discount > 0) {
                $discountedAmount = $product->discount_type === 'percent'
                    ? $product->unit_price - ($product->unit_price * $product->discount / 100)
                    : max(0, $product->unit_price - $product->discount);
                $salePrice = number_format((float)$discountedAmount, 2, '.', '') . ' NGN';
                $xml .= '      <g:sale_price>' . $salePrice . "</g:sale_price>\n";
            }

            $xml .= '      <g:brand>' . htmlspecialchars($brand, ENT_XML1, 'UTF-8') . "</g:brand>\n";
            $xml .= "      <g:condition>new</g:condition>\n";
            $xml .= '      <g:product_type>' . htmlspecialchars($categoryName, ENT_XML1, 'UTF-8') . "</g:product_type>\n";

            // [AI] Google Product Taxonomy & Identifiers (GTIN/MPN)
            if (!empty($product->google_category_id)) {
                $xml .= '      <g:google_product_category>' . htmlspecialchars((string)$product->google_category_id, ENT_XML1, 'UTF-8') . "</g:google_product_category>\n";
            }
            if (!empty($product->gtin)) {
                $xml .= '      <g:gtin>' . htmlspecialchars((string)$product->gtin, ENT_XML1, 'UTF-8') . "</g:gtin>\n";
            }
            if (!empty($product->mpn)) {
                $xml .= '      <g:mpn>' . htmlspecialchars((string)$product->mpn, ENT_XML1, 'UTF-8') . "</g:mpn>\n";
            }
            if (empty($product->gtin) && empty($product->mpn)) {
                $xml .= "      <g:identifier_exists>no</g:identifier_exists>\n";
            }

            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= '</rss>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }

    /**
     * 2. Facebook & Instagram Commerce Manager Catalog CSV Feed.
     * Supports both Admin Platform Feed and Vendor-Isolated Catalog Feeds.
     */
    public function facebookCatalogCsv(Request $request): StreamedResponse|Response
    {
        $auth = $this->authenticateFeedRequest($request);
        if (!$auth['authenticated']) {
            return response('Unauthorized feed token. Access denied.', 403, [
                'Content-Type' => 'text/plain; charset=utf-8'
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'inline; filename="facebook_catalog_feed.csv"',
            'Cache-Control' => 'public, max-age=1800',
        ];

        $channelTitle = ($auth['scope'] === 'vendor' && $auth['seller'] && $auth['seller']->shop)
            ? $auth['seller']->shop->name
            : (getWebConfig('company_name') ?? 'Victorious MARKET');

        return response()->stream(function () use ($request, $auth, $channelTitle) {
            $handle = fopen('php://output', 'w');

            // Meta Commerce standard CSV column headers (includes GTIN and MPN)
            fputcsv($handle, [
                'id',
                'title',
                'description',
                'availability',
                'condition',
                'price',
                'sale_price',
                'link',
                'image_link',
                'brand',
                'google_product_category',
                'gtin',
                'mpn',
                'inventory'
            ]);

            $this->getFilteredProductsQuery($request, $auth)->chunk(200, function ($products) use ($handle, $channelTitle) {
                foreach ($products as $product) {
                    $productUrl = route('product', $product->slug ?? $product->id);
                    $imageUrl = is_array($product->thumbnail_full_url)
                        ? ($product->thumbnail_full_url['path'] ?? asset('public/assets/front-end/img/image-place-holder.png'))
                        : ($product->thumbnail_full_url ?? asset('public/assets/front-end/img/image-place-holder.png'));

                    $price = number_format((float)$product->unit_price, 2, '.', '') . ' NGN';
                    $availability = ($product->marketplace_availability === 'in_stock') ? 'in stock' : 'out of stock';
                    $brand = $product->brand ? $product->brand->name : $channelTitle;
                    $categoryName = $product->category ? $product->category->name : 'General';
                    $description = !empty($product->details) ? strip_tags($product->details) : $product->name;
                    $description = mb_substr(trim(preg_replace('/\s+/', ' ', $description)), 0, 4990);

                    $salePrice = '';
                    if ($product->discount > 0) {
                        $discountedAmount = $product->discount_type === 'percent'
                            ? $product->unit_price - ($product->unit_price * $product->discount / 100)
                            : max(0, $product->unit_price - $product->discount);
                        $salePrice = number_format((float)$discountedAmount, 2, '.', '') . ' NGN';
                    }

                    fputcsv($handle, [
                        $product->code ?: ('VM-' . $product->id),
                        $product->name,
                        $description,
                        $availability,
                        'new',
                        $price,
                        $salePrice,
                        $productUrl,
                        $imageUrl,
                        $brand,
                        $product->google_category_id ?: $categoryName,
                        $product->gtin ?? '',
                        $product->mpn ?? '',
                        ($product->marketplace_availability === 'in_stock') ? 1 : 0,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * 3. TikTok Catalog CSV Feed.
     * Supports both Admin Platform Feed and Vendor-Isolated Catalog Feeds.
     */
    public function tiktokCatalogCsv(Request $request): StreamedResponse|Response
    {
        $auth = $this->authenticateFeedRequest($request);
        if (!$auth['authenticated']) {
            return response('Unauthorized feed token. Access denied.', 403, [
                'Content-Type' => 'text/plain; charset=utf-8'
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'inline; filename="tiktok_catalog_feed.csv"',
            'Cache-Control' => 'public, max-age=1800',
        ];

        $channelTitle = ($auth['scope'] === 'vendor' && $auth['seller'] && $auth['seller']->shop)
            ? $auth['seller']->shop->name
            : (getWebConfig('company_name') ?? 'Victorious MARKET');

        return response()->stream(function () use ($request, $auth, $channelTitle) {
            $handle = fopen('php://output', 'w');

            // TikTok Catalog CSV headers
            fputcsv($handle, [
                'sku_id',
                'title',
                'description',
                'availability',
                'condition',
                'price',
                'sale_price',
                'product_url',
                'image_link',
                'brand',
                'quantity'
            ]);

            $this->getFilteredProductsQuery($request, $auth)->chunk(200, function ($products) use ($handle, $channelTitle) {
                foreach ($products as $product) {
                    $productUrl = route('product', $product->slug ?? $product->id);
                    $imageUrl = is_array($product->thumbnail_full_url)
                        ? ($product->thumbnail_full_url['path'] ?? asset('public/assets/front-end/img/image-place-holder.png'))
                        : ($product->thumbnail_full_url ?? asset('public/assets/front-end/img/image-place-holder.png'));

                    $price = number_format((float)$product->unit_price, 2, '.', '') . ' NGN';
                    $availability = ($product->marketplace_availability === 'in_stock') ? 'in_stock' : 'out_of_stock';
                    $brand = $product->brand ? $product->brand->name : $channelTitle;
                    $description = !empty($product->details) ? strip_tags($product->details) : $product->name;
                    $description = mb_substr(trim(preg_replace('/\s+/', ' ', $description)), 0, 4990);

                    $salePrice = '';
                    if ($product->discount > 0) {
                        $discountedAmount = $product->discount_type === 'percent'
                            ? $product->unit_price - ($product->unit_price * $product->discount / 100)
                            : max(0, $product->unit_price - $product->discount);
                        $salePrice = number_format((float)$discountedAmount, 2, '.', '') . ' NGN';
                    }

                    fputcsv($handle, [
                        $product->code ?: ('VM-' . $product->id),
                        $product->name,
                        $description,
                        $availability,
                        'new',
                        $price,
                        $salePrice,
                        $productUrl,
                        $imageUrl,
                        $brand,
                        ($product->marketplace_availability === 'in_stock') ? 1 : 0,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
