<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategorySpecification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySpecificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultCategories = [
            1 => [
                'name' => 'Electronics & Gadgets',
                'sub' => ['Laptops & Computers', 'Audio & Headphones', 'Smart TVs & Monitors', 'Cameras & Drones', 'Power & Solar']
            ],
            2 => [
                'name' => 'Furniture & Beddings',
                'sub' => ['Living Room Furniture', 'Bedroom & Mattresses', 'Office Furniture', 'Dining & Bar Sets']
            ],
            3 => [
                'name' => 'Musical Instruments',
                'sub' => ['Keyboards & Pianos', 'Guitars & Bass', 'Drums & Percussion', 'Microphones & PA Systems']
            ],
            4 => [
                'name' => 'Fashion & Clothing',
                'sub' => ['Men\'s Wear', 'Women\'s Wear', 'Shoes & Sneakers', 'Traditional & Native Attire', 'Watches & Jewelry']
            ],
            5 => [
                'name' => 'Phones & Accessories',
                'sub' => ['Smartphones & iPhones', 'Basic & Feature Phones', 'Power Banks & Chargers', 'Cases & Screen Protectors', 'Smartwatches']
            ],
            6 => [
                'name' => 'Home Appliances',
                'sub' => ['Air Conditioners', 'Washing Machines', 'Refrigerators & Freezers', 'Fans & Generators']
            ],
            7 => [
                'name' => 'Kitchen Appliances',
                'sub' => ['Blenders & Grinders', 'Microwaves & Ovens', 'Gas Cookers & Stoves', 'Air Fryers & Deep Fryers']
            ],
            8 => [
                'name' => 'Beauty & Personal Care',
                'sub' => ['Skincare & Serums', 'Perfumes & Fragrances', 'Hair Extensions & Wigs', 'Makeup & Cosmetics']
            ],
            9 => [
                'name' => 'Automobile',
                'sub' => ['Car Maintenance & Oils', 'Tyres & Wheels', 'Car Electronics & Dash Cams', 'Exterior & Interior Accessories']
            ],
            10 => [
                'name' => 'Bags & Luggages',
                'sub' => ['Travel Suitcases', 'Laptop Backpacks', 'Women\'s Handbags', 'Duffel & Gym Bags']
            ],
            11 => [
                'name' => 'Groceries & Foodstuffs',
                'sub' => ['Rice & Grains', 'Cooking Oils & Seasoning', 'Beverages & Drinks', 'Snacks & Canned Food']
            ],
        ];

        foreach ($defaultCategories as $id => $catData) {
            $parentCat = Category::updateOrCreate(
                ['name' => $catData['name'], 'position' => 0],
                [
                    'slug' => Str::slug($catData['name']),
                    'icon' => 'default.png',
                    'icon_storage_type' => 'public',
                    'parent_id' => 0,
                    'position' => 0,
                    'home_status' => 1,
                    'priority' => $id,
                ]
            );

            if (!empty($catData['sub'])) {
                foreach ($catData['sub'] as $subIdx => $subName) {
                    Category::updateOrCreate(
                        ['name' => $subName, 'parent_id' => $parentCat->id],
                        [
                            'slug' => Str::slug($subName),
                            'icon' => 'default.png',
                            'icon_storage_type' => 'public',
                            'parent_id' => $parentCat->id,
                            'position' => 1,
                            'home_status' => 1,
                            'priority' => $subIdx + 1,
                        ]
                    );
                }
            }
        }

        $specMatrix = [
            'Phones & Accessories' => [
                ['name' => 'Storage Capacity', 'input_type' => 'select', 'options' => ['32GB', '64GB', '128GB', '256GB', '512GB', '1TB'], 'unit' => 'GB', 'is_required' => true, 'sort_order' => 1],
                ['name' => 'RAM / Memory', 'input_type' => 'select', 'options' => ['2GB', '3GB', '4GB', '6GB', '8GB', '12GB', '16GB'], 'unit' => 'GB', 'is_required' => true, 'sort_order' => 2],
                ['name' => 'Battery Capacity', 'input_type' => 'number', 'placeholder' => 'e.g. 5000', 'unit' => 'mAh', 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Screen Size', 'input_type' => 'select', 'options' => ['5.5"', '6.1"', '6.5"', '6.7"', '6.8"'], 'unit' => 'Inches', 'is_required' => false, 'sort_order' => 4],
                ['name' => 'Operating System', 'input_type' => 'select', 'options' => ['iOS', 'Android', 'Other'], 'is_required' => true, 'sort_order' => 5],
                ['name' => 'Main Camera', 'input_type' => 'text', 'placeholder' => 'e.g. 48MP Triple Camera', 'unit' => 'MP', 'is_required' => false, 'sort_order' => 6],
                ['name' => 'SIM Type', 'input_type' => 'select', 'options' => ['Dual Nano SIM', 'Single SIM + eSIM', 'eSIM Only'], 'is_required' => false, 'sort_order' => 7],
                ['name' => 'Condition', 'input_type' => 'select', 'options' => ['Brand New (Sealed)', 'UK Used (Direct A+ Grade)', 'Refurbished'], 'is_required' => true, 'sort_order' => 8],
            ],
            'Electronics & Gadgets' => [
                ['name' => 'Processor / Chip', 'input_type' => 'text', 'placeholder' => 'e.g. Intel Core i7 13th Gen / Apple M3', 'is_required' => false, 'sort_order' => 1],
                ['name' => 'RAM Memory', 'input_type' => 'select', 'options' => ['4GB', '8GB', '16GB', '32GB', '64GB'], 'unit' => 'GB', 'is_required' => false, 'sort_order' => 2],
                ['name' => 'Power Rating', 'input_type' => 'number', 'placeholder' => 'e.g. 65', 'unit' => 'Watts', 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Connectivity', 'input_type' => 'multi_select', 'options' => ['Bluetooth 5.3', 'WiFi 6', 'HDMI', 'USB Type-C', 'Ethernet'], 'is_required' => false, 'sort_order' => 4],
                ['name' => 'Display Resolution', 'input_type' => 'select', 'options' => ['HD (720p)', 'Full HD (1080p)', '2K QHD', '4K UHD (2160p)'], 'is_required' => false, 'sort_order' => 5],
                ['name' => 'Battery Life', 'input_type' => 'text', 'placeholder' => 'e.g. Up to 18 Hours', 'is_required' => false, 'sort_order' => 6],
            ],
            'Fashion & Clothing' => [
                ['name' => 'Fabric / Material', 'input_type' => 'select', 'options' => ['100% Pure Cotton', 'Linen', 'Silk', 'Polyester', 'Senator Material', 'Guinea Brocade', 'Denim', 'Genuine Leather', 'Velvet'], 'is_required' => true, 'sort_order' => 1],
                ['name' => 'Fit Type', 'input_type' => 'select', 'options' => ['Slim Fit', 'Regular Fit', 'Oversized / Relaxed', 'Skinny Fit'], 'is_required' => false, 'sort_order' => 2],
                ['name' => 'Sleeve Length', 'input_type' => 'select', 'options' => ['Short Sleeve', 'Long Sleeve', '3/4 Sleeve', 'Sleeveless'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Gender', 'input_type' => 'select', 'options' => ['Men', 'Women', 'Unisex', 'Boys', 'Girls'], 'is_required' => true, 'sort_order' => 4],
                ['name' => 'Shoe Size Range', 'input_type' => 'select', 'options' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44', 'EU 45', 'EU 46'], 'is_required' => false, 'sort_order' => 5],
                ['name' => 'Occasion', 'input_type' => 'select', 'options' => ['Casual', 'Corporate / Office', 'Traditional / Wedding', 'Sports / Athletic', 'Evening / Party'], 'is_required' => false, 'sort_order' => 6],
            ],
            'Furniture & Beddings' => [
                ['name' => 'Dimensions (L x W x H)', 'input_type' => 'text', 'placeholder' => 'e.g. 6ft x 6ft x 18 inches', 'is_required' => false, 'sort_order' => 1],
                ['name' => 'Primary Material', 'input_type' => 'select', 'options' => ['Solid Mahogany Wood', 'Teak Wood', 'Engineered HDF Wood', 'Stainless Steel Frame', 'Velvet Fabric', 'Genuine Leather'], 'is_required' => true, 'sort_order' => 2],
                ['name' => 'Bed Frame / Mattress Size', 'input_type' => 'select', 'options' => ['4.5 x 6 (Standard)', '6 x 6 (Queen)', '6 x 7 (King)', 'Single 3 x 6'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Mattress Density', 'input_type' => 'select', 'options' => ['Orthopedic (Firm)', 'Semi-Orthopedic', 'High Density Foam', 'Spring / Pocket Coil'], 'is_required' => false, 'sort_order' => 4],
                ['name' => 'Assembly Required', 'input_type' => 'select', 'options' => ['No - Fully Assembled', 'Yes - Simple Assembly Included'], 'is_required' => false, 'sort_order' => 5],
            ],
            'Beauty & Personal Care' => [
                ['name' => 'Volume / Net Weight', 'input_type' => 'text', 'placeholder' => 'e.g. 50ml, 100ml, 250g', 'is_required' => true, 'sort_order' => 1],
                ['name' => 'Target Skin Type', 'input_type' => 'select', 'options' => ['All Skin Types', 'Oily / Acne-Prone', 'Dry Skin', 'Combination', 'Sensitive Skin'], 'is_required' => false, 'sort_order' => 2],
                ['name' => 'Formulation', 'input_type' => 'select', 'options' => ['Serum', 'Cream', 'Lotion', 'Gel', 'Oil', 'Liquid Wash'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Fragrance Concentration', 'input_type' => 'select', 'options' => ['Eau de Parfum (EDP)', 'Extrait de Parfum', 'Eau de Toilette (EDT)', 'Body Mist / Spray'], 'is_required' => false, 'sort_order' => 4],
                ['name' => 'Scent Profile', 'input_type' => 'select', 'options' => ['Oud & Woody', 'Sweet Vanilla & Gourmand', 'Fresh Citrus', 'Floral', 'Spicy Oriental'], 'is_required' => false, 'sort_order' => 5],
                ['name' => 'SPF Rating', 'input_type' => 'select', 'options' => ['None', 'SPF 30', 'SPF 50+', 'SPF 100'], 'is_required' => false, 'sort_order' => 6],
            ],
            'Kitchen Appliances' => [
                ['name' => 'Power Consumption', 'input_type' => 'number', 'placeholder' => 'e.g. 1500', 'unit' => 'Watts', 'is_required' => true, 'sort_order' => 1],
                ['name' => 'Capacity', 'input_type' => 'text', 'placeholder' => 'e.g. 2.0 Litres, 6.5 Litres', 'is_required' => false, 'sort_order' => 2],
                ['name' => 'Control Type', 'input_type' => 'select', 'options' => ['Digital Touchscreen', 'Manual Rotary Knob', 'Push Button'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Body Material', 'input_type' => 'select', 'options' => ['Stainless Steel', 'BPA-Free Plastic', 'Tempered Glass', 'Cast Iron'], 'is_required' => false, 'sort_order' => 4],
                ['name' => 'Warranty Period', 'input_type' => 'select', 'options' => ['6 Months Warranty', '1 Year Official Warranty', '2 Years Official Warranty', 'No Warranty'], 'is_required' => true, 'sort_order' => 5],
            ],
            'Home Appliances' => [
                ['name' => 'Horsepower / Power Rating', 'input_type' => 'text', 'placeholder' => 'e.g. 1.5 HP, 2000W', 'is_required' => false, 'sort_order' => 1],
                ['name' => 'Inverter Technology', 'input_type' => 'select', 'options' => ['Yes - Energy Saving Inverter', 'No - Non-Inverter Standard'], 'is_required' => true, 'sort_order' => 2],
                ['name' => 'Voltage Operation', 'input_type' => 'select', 'options' => ['Low Voltage Operation (Generator Friendly)', 'Standard 220V-240V'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Warranty Period', 'input_type' => 'select', 'options' => ['1 Year Manufacturer Warranty', '2 Years Warranty', '5 Years Compressor Warranty'], 'is_required' => true, 'sort_order' => 4],
            ],
            'Bags & Luggages' => [
                ['name' => 'Luggage / Bag Size', 'input_type' => 'select', 'options' => ['20" Carry-On', '24" Medium Luggage', '28" Large Luggage', 'Set of 3 (20+24+28)', 'Compact Laptop Backpack'], 'is_required' => false, 'sort_order' => 1],
                ['name' => 'Outer Material', 'input_type' => 'select', 'options' => ['Unbreakable Polycarbonate', 'ABS Hard Shell', 'Waterproof Oxford Nylon', 'Genuine Leather', 'PU Leather'], 'is_required' => true, 'sort_order' => 2],
                ['name' => 'Wheel System', 'input_type' => 'select', 'options' => ['360° Double Spinner Wheels (4x2)', 'Single Wheels (4x1)', 'No Wheels'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Lock Type', 'input_type' => 'select', 'options' => ['Built-in TSA Combination Lock', 'Standard 3-Digit Code Lock', 'Key Padlock'], 'is_required' => false, 'sort_order' => 4],
            ],
            'Musical Instruments' => [
                ['name' => 'Instrument Category', 'input_type' => 'select', 'options' => ['Digital Keyboard / Workstation', 'Acoustic Guitar', 'Electric / Bass Guitar', 'Drum Kit / Electronic Drums', 'Wireless Microphones & Audio'], 'is_required' => true, 'sort_order' => 1],
                ['name' => 'Keys / Strings Count', 'input_type' => 'text', 'placeholder' => 'e.g. 61 Keys, 88 Weighted Keys, 6 Strings', 'is_required' => false, 'sort_order' => 2],
                ['name' => 'Connectivity & Outputs', 'input_type' => 'multi_select', 'options' => ['MIDI USB', '1/4" Line Out (Jack)', 'XLR Balanced Out', 'Headphone Out 3.5mm'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Power Supply', 'input_type' => 'select', 'options' => ['AC Power Adapter Included', 'Battery Powered (Phantom/AA)', 'Passive (No Power Required)'], 'is_required' => false, 'sort_order' => 4],
            ],
            'Automobile' => [
                ['name' => 'Compatible Vehicle Make', 'input_type' => 'text', 'placeholder' => 'e.g. Toyota, Lexus, Honda, Mercedes-Benz, Universal', 'is_required' => true, 'sort_order' => 1],
                ['name' => 'Compatible Year Range', 'input_type' => 'text', 'placeholder' => 'e.g. 2008 - 2024', 'is_required' => false, 'sort_order' => 2],
                ['name' => 'Part Condition', 'input_type' => 'select', 'options' => ['Brand New OEM Original', 'Aftermarket High Quality', 'Foreign Used (Direct Tokunbo)'], 'is_required' => true, 'sort_order' => 3],
                ['name' => 'Fluid Viscosity / Battery Ah', 'input_type' => 'text', 'placeholder' => 'e.g. 5W-30, 20W-50, 75Ah, 100Ah', 'is_required' => false, 'sort_order' => 4],
            ],
            'Groceries & Foodstuffs' => [
                ['name' => 'Net Weight / Pack Size', 'input_type' => 'text', 'placeholder' => 'e.g. 1kg, 5kg, 25kg, 50kg Bag, 5 Litres', 'is_required' => true, 'sort_order' => 1],
                ['name' => 'Shelf Life / Expiry', 'input_type' => 'text', 'placeholder' => 'e.g. 12 Months', 'is_required' => false, 'sort_order' => 2],
                ['name' => 'Storage Instructions', 'input_type' => 'select', 'options' => ['Store in a Cool Dry Place', 'Keep Refrigerated After Opening', 'Keep Frozen (-18°C)'], 'is_required' => false, 'sort_order' => 3],
                ['name' => 'Dietary / Organic', 'input_type' => 'select', 'options' => ['100% Natural / Organic', 'Standard Commercial', 'Gluten-Free', 'Halal Certified'], 'is_required' => false, 'sort_order' => 4],
            ],
        ];

        foreach ($specMatrix as $categoryName => $specs) {
            $categories = Category::where('name', 'like', "%$categoryName%")->get();
            if ($categories->isEmpty()) {
                $simpleName = explode('&', $categoryName)[0];
                $categories = Category::where('name', 'like', '%' . trim($simpleName) . '%')->get();
            }

            foreach ($categories as $cat) {
                foreach ($specs as $spec) {
                    CategorySpecification::updateOrCreate(
                        [
                            'category_id' => $cat->id,
                            'name' => $spec['name'],
                        ],
                        [
                            'input_type' => $spec['input_type'],
                            'options' => $spec['options'] ?? null,
                            'is_required' => $spec['is_required'] ?? false,
                            'unit' => $spec['unit'] ?? null,
                            'placeholder' => $spec['placeholder'] ?? null,
                            'sort_order' => $spec['sort_order'] ?? 0,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
