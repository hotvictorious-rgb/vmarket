<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductFeedExportController extends Controller
{
    /**
     * Get or generate the permanent secret token for live data feeds.
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
     * Verify token access for live crawlers.
     */
    private function verifyFeedToken(Request $request): bool
    {
        $serverToken = self::getFeedToken();
        $providedToken = $request->query('token') ?? $request->header('X-Feed-Token');

        return !empty($providedToken) && hash_equals($serverToken, $providedToken);
    }

    /**
     * Get filtered base products query.
     */
    private function getFilteredProductsQuery(Request $request)
    {
        return Product::active()
            ->with(['brand', 'category', 'rating'])
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
                return $q->where('current_stock', '>', 0);
            })
            ->latest('updated_at');
    }

    /**
     * 1. Google Merchant Center (Google Shopping) RSS 2.0 XML Feed.
     */
    public function googleMerchantXml(Request $request): Response
    {
        if (!$this->verifyFeedToken($request)) {
            return response('<error>Unauthorized feed token. Access denied.</error>', 403, [
                'Content-Type' => 'application/xml'
            ]);
        }

        $products = $this->getFilteredProductsQuery($request)->limit(5000)->get();
        $webConfig = getWebConfig('company_name') ?? 'Victorious MARKET';
        $siteUrl = url('/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>' . htmlspecialchars($webConfig, ENT_XML1, 'UTF-8') . " Product Feed</title>\n";
        $xml .= '    <link>' . htmlspecialchars($siteUrl, ENT_XML1, 'UTF-8') . "</link>\n";
        $xml .= '    <description>Official Google Shopping Catalog Feed for ' . htmlspecialchars($webConfig, ENT_XML1, 'UTF-8') . "</description>\n";

        foreach ($products as $product) {
            $productUrl = route('product', $product->slug ?? $product->id);
            $imageUrl = $product->thumbnail_full_url['path'] ?? asset('public/assets/front-end/img/image-place-holder.png');
            $price = number_format((float)$product->unit_price, 2, '.', '') . ' NGN';
            $availability = ($product->current_stock > 0) ? 'in stock' : 'out of stock';
            $brand = $product->brand ? $product->brand->name : $webConfig;
            $categoryName = $product->category ? $product->category->name : 'General';
            $description = !empty($product->details) ? strip_tags($product->details) : $product->name;
            $description = mb_substr(trim(preg_replace('/\s+/', ' ', $description)), 0, 4990);

            $xml .= "    <item>\n";
            $xml .= '      <g:id>' . htmlspecialchars((string)$product->id, ENT_XML1, 'UTF-8') . "</g:id>\n";
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
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= '</rss>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /**
     * 2. Facebook & Instagram Commerce Manager Catalog CSV Feed.
     */
    public function facebookCatalogCsv(Request $request): StreamedResponse|Response
    {
        if (!$this->verifyFeedToken($request)) {
            return response('Unauthorized feed token. Access denied.', 403);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'inline; filename="facebook_catalog_feed.csv"',
            'Cache-Control' => 'public, max-age=3600',
        ];

        $webConfig = getWebConfig('company_name') ?? 'Victorious MARKET';

        return response()->stream(function () use ($request, $webConfig) {
            $handle = fopen('php://output', 'w');

            // Meta Commerce standard CSV column headers
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
                'inventory'
            ]);

            $this->getFilteredProductsQuery($request)->chunk(200, function ($products) use ($handle, $webConfig) {
                foreach ($products as $product) {
                    $productUrl = route('product', $product->slug ?? $product->id);
                    $imageUrl = $product->thumbnail_full_url['path'] ?? asset('public/assets/front-end/img/image-place-holder.png');
                    $price = number_format((float)$product->unit_price, 2, '.', '') . ' NGN';
                    $availability = ($product->current_stock > 0) ? 'in stock' : 'out of stock';
                    $brand = $product->brand ? $product->brand->name : $webConfig;
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
                        $product->id,
                        $product->name,
                        $description,
                        $availability,
                        'new',
                        $price,
                        $salePrice,
                        $productUrl,
                        $imageUrl,
                        $brand,
                        $categoryName,
                        $product->current_stock ?? 0,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * 3. TikTok Catalog CSV Feed.
     */
    public function tiktokCatalogCsv(Request $request): StreamedResponse|Response
    {
        if (!$this->verifyFeedToken($request)) {
            return response('Unauthorized feed token. Access denied.', 403);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'inline; filename="tiktok_catalog_feed.csv"',
            'Cache-Control' => 'public, max-age=3600',
        ];

        $webConfig = getWebConfig('company_name') ?? 'Victorious MARKET';

        return response()->stream(function () use ($request, $webConfig) {
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

            $this->getFilteredProductsQuery($request)->chunk(200, function ($products) use ($handle, $webConfig) {
                foreach ($products as $product) {
                    $productUrl = route('product', $product->slug ?? $product->id);
                    $imageUrl = $product->thumbnail_full_url['path'] ?? asset('public/assets/front-end/img/image-place-holder.png');
                    $price = number_format((float)$product->unit_price, 2, '.', '') . ' NGN';
                    $availability = ($product->current_stock > 0) ? 'in_stock' : 'out_of_stock';
                    $brand = $product->brand ? $product->brand->name : $webConfig;
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
                        $product->id,
                        $product->name,
                        $description,
                        $availability,
                        'new',
                        $price,
                        $salePrice,
                        $productUrl,
                        $imageUrl,
                        $brand,
                        $product->current_stock ?? 0,
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
