<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Response;

/**
 * [AI] Class SitemapController
 *
 * Serves canonical XML sitemaps for the VMarket Storefront (SEO / discovery layer)
 * according to VMARKET_STOREFRONT_SPEC.md Section 5.
 */
class SitemapController extends Controller
{
    /**
     * Main XML sitemap index.
     */
    public function index(): Response
    {
        $baseUrl = config('app.url');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= '<sitemap><loc>' . $baseUrl . '/sitemap-products.xml</loc></sitemap>';
        $xml .= '<sitemap><loc>' . $baseUrl . '/sitemap-categories.xml</loc></sitemap>';
        $xml .= '<sitemap><loc>' . $baseUrl . '/sitemap-brands.xml</loc></sitemap>';
        $xml .= '<sitemap><loc>' . $baseUrl . '/sitemap-shops.xml</loc></sitemap>';
        $xml .= '</sitemapindex>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * XML sitemap for active products.
     */
    public function products(): Response
    {
        $baseUrl = config('app.url');
        $products = Product::where('status', 1)->select('id', 'slug', 'updated_at')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($products as $product) {
            $loc = $baseUrl . '/product/' . ($product->slug ?? $product->id);
            $lastmod = $product->updated_at ? $product->updated_at->toAtomString() : date('c');
            $xml .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * XML sitemap for active categories.
     */
    public function categories(): Response
    {
        $baseUrl = config('app.url');
        $categories = Category::where('home_status', 1)->orWhere('position', 0)->select('id', 'slug', 'updated_at')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($categories as $category) {
            $loc = $baseUrl . '/products?category_id=' . $category->id;
            $lastmod = $category->updated_at ? $category->updated_at->toAtomString() : date('c');
            $xml .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * XML sitemap for active brands.
     */
    public function brands(): Response
    {
        $baseUrl = config('app.url');
        $brands = Brand::where('status', 1)->select('id', 'slug', 'updated_at')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($brands as $brand) {
            $loc = $baseUrl . '/products?brand_id=' . $brand->id;
            $lastmod = $brand->updated_at ? $brand->updated_at->toAtomString() : date('c');
            $xml .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * XML sitemap for active seller shops.
     */
    public function shops(): Response
    {
        $baseUrl = config('app.url');
        $shops = Shop::where('vacation_status', 0)->select('id', 'slug', 'updated_at')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($shops as $shop) {
            $loc = $baseUrl . '/shopView/' . $shop->id;
            $lastmod = $shop->updated_at ? $shop->updated_at->toAtomString() : date('c');
            $xml .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>daily</changefreq><priority>0.7</priority></url>";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
