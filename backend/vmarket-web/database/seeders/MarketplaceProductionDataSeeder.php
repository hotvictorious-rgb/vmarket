<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketplaceProductionDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Run Category Specification Seeder
        $this->call(CategorySpecificationSeeder::class);

        // 2. Seed Strategic Brands
        $brands = [
            'Apple' => 'apple.png',
            'Samsung' => 'samsung.png',
            'HP' => 'hp.png',
            'Dell' => 'dell.png',
            'Oraimo' => 'oraimo.png',
            'Tecno' => 'tecno.png',
            'Infinix' => 'infinix.png',
            'Nike' => 'nike.png',
            'Adidas' => 'adidas.png',
            'LG Electronics' => 'lg.png',
            'Hisense' => 'hisense.png',
            'Sony' => 'sony.png',
        ];

        foreach ($brands as $name => $img) {
            Brand::updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'image' => $img,
                    'image_storage_type' => 'public',
                    'image_alt_text' => $name . ' Official Brand',
                    'status' => 1,
                ]
            );
        }

        // 3. Seed Exactly 3 Strategic Banner Touchpoints
        // Clear previous banners to ensure zero clutter
        Banner::truncate();

        // Touchpoint 1: Hero Banner Slider (Main Banner)
        Banner::create([
            'banner_type' => 'Main Banner',
            'theme' => 'theme_vmarket',
            'published' => 1,
            'url' => url('/products'),
            'resource_type' => 'category',
            'resource_id' => Category::where('position', 0)->first()?->id ?? 1,
            'title' => 'Shop with 100% Peace of Mind',
            'sub_title' => 'Physical In-Shop Inspection Before Payment • Verified Local Merchants',
            'button_text' => 'Shop Verified Items',
            'background_color' => '#5E17EB',
            'photo' => 'def.png',
        ]);

        Banner::create([
            'banner_type' => 'Main Banner',
            'theme' => 'theme_vmarket',
            'published' => 1,
            'url' => url('/vendors'),
            'resource_type' => 'shop',
            'resource_id' => 1,
            'title' => 'Top Electronics & Gadgets in Akwa Ibom',
            'sub_title' => 'Fast Dispatch from Uyo & Eket Hubs • Paystack Escrow Protected',
            'button_text' => 'Browse Verified Stores',
            'background_color' => '#170733',
            'photo' => 'def.png',
        ]);

        Banner::create([
            'banner_type' => 'Main Banner',
            'theme' => 'default',
            'published' => 1,
            'url' => url('/products'),
            'resource_type' => 'category',
            'resource_id' => 1,
            'title' => 'Seamless Shopping, Swift Logistics',
            'sub_title' => 'Over 15 verified product categories ready for immediate delivery',
            'button_text' => 'Explore Market',
            'background_color' => '#5E17EB',
            'photo' => 'def.png',
        ]);

        // Touchpoint 2: Footer Banner Slider (Strategic Conversion & Trust Strip)
        Banner::create([
            'banner_type' => 'Footer Banner',
            'theme' => 'theme_vmarket',
            'published' => 1,
            'url' => url('/contacts'),
            'resource_type' => 'brand',
            'resource_id' => 1,
            'title' => 'Direct LGA Rider Logistics',
            'sub_title' => 'Door-to-door delivery across Uyo, Eket, and all 31 Akwa Ibom Local Governments',
            'button_text' => 'Delivery Rates & Coverage',
            'background_color' => '#5E17EB',
            'photo' => 'def.png',
        ]);

        Banner::create([
            'banner_type' => 'Footer Banner',
            'theme' => 'theme_vmarket',
            'published' => 1,
            'url' => url('/vendors'),
            'resource_type' => 'shop',
            'resource_id' => 1,
            'title' => 'Become a Verified Merchant on Victorious MARKET',
            'sub_title' => 'Grow your local retail store with automated omnichannel orders and dedicated dispatch',
            'button_text' => 'Join as Merchant',
            'background_color' => '#0F172A',
            'photo' => 'def.png',
        ]);

        // Touchpoint 3: Popup Banner (Promotional Modal - Shown Once Per Session)
        Banner::create([
            'banner_type' => 'Popup Banner',
            'theme' => 'theme_vmarket',
            'published' => 1,
            'url' => url('/products'),
            'resource_type' => 'product',
            'resource_id' => 1,
            'title' => 'Welcome to Victorious MARKET!',
            'sub_title' => 'Enjoy genuine items, in-shop physical inspection before collection, and Paystack escrow guarantee.',
            'button_text' => 'Start Shopping Now',
            'background_color' => '#5E17EB',
            'photo' => 'def.png',
        ]);

        // 4. Relink Products to Real Admin Categories
        $mainCategories = Category::where('position', 0)->get();
        if ($mainCategories->count() > 0) {
            $catCount = $mainCategories->count();
            $products = Product::all();
            foreach ($products as $idx => $product) {
                // Distribute products evenly across categories
                $cat = $mainCategories[$idx % $catCount];
                $subCat = Category::where('parent_id', $cat->id)->first();

                $categoryIds = [
                    ['id' => (string)$cat->id, 'position' => 1]
                ];
                if ($subCat) {
                    $categoryIds[] = ['id' => (string)$subCat->id, 'position' => 2];
                }

                $product->category_id = $cat->id;
                $product->sub_category_id = $subCat?->id;
                $product->category_ids = json_encode($categoryIds);
                $product->save();
            }
        }
    }
}
