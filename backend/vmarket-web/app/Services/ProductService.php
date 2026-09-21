<?php

namespace App\Services;


use App\Events\RestockProductNotificationEvent;
use App\Models\Color;
use App\Models\Product;
use App\Traits\FileManagerTrait;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;
class ProductService
{
    use FileManagerTrait;

    public function __construct(private readonly Color $color)
    {
    }

    public function getProcessedImages(object $request): array
    {
        $colorImageSerial = [];
        $imageNames = [];
        $storage = config('filesystems.disks.default') ?? 'public';
        if ($request->has('colors_active') && $request->has('colors') && count($request['colors']) > 0) {
            foreach ($request['colors'] as $color) {
                $color_ = Str::replace('#', '', $color);
                $imgKey = 'color_image_' . $color_;
                $file = $request->file($imgKey);
                $inputImage = $request->input($imgKey);
                if ($file) {
                    $image = $this->upload(dir: 'product/', format: 'webp', image: $file);
                } elseif ($inputImage) {
                    $image = is_array($inputImage) ? $inputImage[0] : $inputImage;
                } else {
                    continue;
                }
                $colorImageSerial[] = [
                    'color' => $color_,
                    'image_name' => $image,
                    'storage' => $storage,
                ];
                $imageNames[] = [
                    'image_name' => $image,
                    'storage' => $storage,
                ];
            }
        }
        if ($request->file('images')) {
            foreach ($request->file('images') as $image) {
                $images = $this->upload(dir: 'product/', format: 'webp', image: $image);
                $imageNames[] = [
                    'image_name' => $images,
                    'storage' => $storage,
                ];
                if ($request->has('colors_active') && $request->has('colors') && count($request['colors']) > 0) {
                    $colorImageSerial[] = [
                        'color' => null,
                        'image_name' => $images,
                        'storage' => $storage,
                    ];
                }
            }
        }
        $existingImages = $request->input('existing_images', []);
        if (!empty($existingImages)) {
            foreach ($request->existing_images as $image) {
                $colorImageSerial[] = [
                    'color' => null,
                    'image_name' => $image,
                    'storage' => $storage,
                ];

                $imageNames[] = [
                    'image_name' => $image,
                    'storage' => $storage,
                ];
            }
        }
        return [
            'image_names' => $imageNames ?? [],
            'colored_image_names' => $colorImageSerial ?? []
        ];

    }

    public function getProcessedUpdateImages(object $request, object $product): array
    {
        $productImages = collect(json_decode($product->images, true))
            ->unique('image_name')
            ->values()->toArray();

        $colorImageArray = [];
        $storage = config('filesystems.disks.default') ?? 'public';
        $dbColorImage = $product->color_image ? json_decode($product->color_image, true) : [];
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            if (!$dbColorImage) {
                foreach ($productImages as $image) {
                    $image = is_string($image) ? $image : (array)$image;
                    $dbColorImage[] = [
                        'color' => null,
                        'image_name' => is_array($image) ? $image['image_name'] : $image,
                        'storage' => $image['storage'] ?? $storage,
                    ];
                }
            }

            $dbColorImageFinal = [];
            if ($dbColorImage) {
                foreach ($dbColorImage as $colorImage) {
                    if ($colorImage['color']) {
                        $dbColorImageFinal[] = $colorImage['color'];
                    }
                }
            }

            $inputColors = [];
            foreach ($request->colors as $color) {
                $inputColors[] = str_replace('#', '', $color);
            }
            $colorImageArray = $dbColorImage;

            foreach ($inputColors as $color) {
                $image = 'color_image_' . $color;
                if (!in_array($color, $dbColorImageFinal)) {
                    if ($request->file($image)) {
                        $imageName = $this->upload(dir: 'product/', format: 'webp', image: $request->file($image));
                        $productImages[] = [
                            'image_name' => $imageName,
                            'storage' => $storage,
                        ];
                        $colorImages = [
                            'color' => $color,
                            'image_name' => $imageName,
                            'storage' => $storage,
                        ];
                        $colorImageArray[] = $colorImages;
                    }
                } else if ($dbColorImage && in_array($color, $dbColorImageFinal) && $request->has($image) && $request->file($image)) {
                    $dbColorFilterImages = [];
                    foreach ($dbColorImage as $colorImage) {
                        if ($colorImage['color'] == $color) {
                            $this->delete(filePath: 'product/' . $colorImage['image_name']);
                            $imageName = $this->upload(dir: 'product/', format: 'webp', image: $request->file($image));

                            $productImages = collect($productImages)->filter(function ($productImageItem) use ($colorImage) {
                                if (is_array($productImageItem) && isset($productImageItem['image_name'])) {
                                    return $productImageItem['image_name'] != $colorImage['image_name'];
                                }
                                return $productImageItem != $colorImage['image_name'];
                            })->values()->toArray();


                            $dbColorFilterImages = collect($dbColorImage)->filter(function ($dbColorImageItem) use ($colorImage) {
                                return $dbColorImageItem['image_name'] != $colorImage['image_name'];
                            })->values()->toArray();

                            $productImages[] = [
                                'image_name' => $imageName,
                                'storage' => $storage,
                            ];

                            $colorImageArray = collect($colorImageArray)->filter(function ($colorItem) use ($color, $colorImage) {
                                return $colorItem['color'] != $color && $colorItem['image_name'] != $colorImage['image_name'];
                            })->values()->toArray();

                            $colorImages = [
                                'color' => $color,
                                'image_name' => $imageName,
                                'storage' => $storage,
                            ];
                            $colorImageArray[] = $colorImages;
                        }
                    }
                    $dbColorImage = $dbColorFilterImages;
                }
            }
        }

        foreach ($dbColorImage as $colorImage) {
            $image = is_string($colorImage) ? $colorImage : (array)$colorImage;
            $productImages[] = [
                'image_name' => is_array($image) ? $image['image_name'] : $image,
                'storage' => $image['storage'] ?? $storage,
            ];
        }
        $requestColors = [];
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            foreach ($request['colors'] as $color) {
                $requestColors[] = str_replace('#', '', $color);
            }
        }

        foreach ($colorImageArray as $colorImage) {
            if (!in_array($colorImage['color'], $requestColors)) {
                $productImages[] = [
                    'image_name' => $colorImage['image_name'],
                    'storage' => $colorImage['storage'] ?? $storage,
                ];
            }
        }

        $colorImageArray = collect($colorImageArray)->map(function ($colorImage) use ($requestColors) {
            if (!in_array($colorImage['color'], $requestColors)) {
                $colorImage['color'] = null;
            }
            return $colorImage;
        })->sortByDesc(function ($colorImage) {
            return !is_null($colorImage['color']);
        })->values()->toArray();

        if ($request->file('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = $this->upload(dir: 'product/', format: 'webp', image: $image);
                $productImages[] = [
                    'image_name' => $imageName,
                    'storage' => $storage,
                ];
                if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
                    $colorImageArray[] = [
                        'color' => null,
                        'image_name' => $imageName,
                        'storage' => $storage,
                    ];
                }
            }
        }
        $productImages = collect($productImages)->unique('image_name')->values()->toArray();

        return [
            'image_names' => $productImages ?? [],
            'colored_image_names' => $colorImageArray ?? []
        ];
    }


    public function getProcessedUpdateAdditionalImages(object $request, object $product): array
    {
        $productImages = collect(json_decode($product->images, true))
            ->unique('image_name')
            ->values()->toArray();

        $colorImageArray = [];
        $storage = config('filesystems.disks.default') ?? 'public';
        $dbColorImage = $product->color_image ? json_decode($product->color_image, true) : [];

        if ($request->has('images') && count($request->images) > 0) {
            if (!$dbColorImage) {
                foreach ($productImages as $image) {
                    $image = is_string($image) ? $image : (array)$image;
                    $dbColorImage[] = [
                        'color' => null,
                        'image_name' => is_array($image) ? $image['image_name'] : $image,
                        'storage' => $image['storage'] ?? $storage,
                    ];
                }
            }
        }

        if ($request->file('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = $this->upload(dir: 'product/', format: 'webp', image: $image);
                $productImages[] = [
                    'image_name' => $imageName,
                    'storage' => $storage,
                ];
                if ($product->product_type === 'physical' && count($dbColorImage) > 0) {
                    $dbColorImage[] = [
                        'color' => null,
                        'image_name' => $imageName,
                        'storage' => $storage,
                    ];
                }
            }
        }
        $productImages = collect($productImages)->unique('image_name')->values()->toArray();

        return [
            'image_names' => $productImages ?? [],
            'colored_image_names' => $dbColorImage ?? []
        ];
    }

    public function getCategoriesArray(object $request): array
    {
        $category = [];
        if ($request['category_id'] != null) {
            $category[] = [
                'id' => $request['category_id'],
                'position' => 1,
            ];
        }
        if ($request['sub_category_id'] != null) {
            $category[] = [
                'id' => $request['sub_category_id'],
                'position' => 2,
            ];
        }
        if ($request['sub_sub_category_id'] != null) {
            $category[] = [
                'id' => $request['sub_sub_category_id'],
                'position' => 3,
            ];
        }
        return $category;
    }

    public function getColorsObject(object $request): bool|string
    {
        if ($request->has('colors_active') && $request->has('colors') && count($request['colors']) > 0) {
            $colors = $request['product_type'] == 'physical' ? json_encode($request['colors']) : json_encode([]);
        } else {
            $colors = json_encode([]);
        }
        return $colors;
    }

    public function getSlug(object $request): string
    {
        return Str::slug($request['name'][array_search('en', $request['lang'])], '-') . '-' . Str::random(6);
    }

    public function getChoiceOptions(object $request): array
    {
        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                $item['name'] = 'choice_' . $no;
                $item['title'] = $request->choice[$key];
                $item['options'] = explode(',', implode('|', $request[$str]));
                $choice_options[] = $item;
            }
        }
        return $choice_options;
    }

    public function getOptions(object $request): array
    {
        $options = [];
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $options[] = $request->colors;
        }
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $no) {
                $name = 'choice_options_' . $no;
                $myString = implode('|', $request[$name]);
                $optionArray = array_filter(explode(',', $myString), function ($value) {
                    return $value !== '';
                });
                $options[] = $optionArray;
            }
        }
        return $options;
    }

    public function getCombinations(array $arrays): array
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }
        return $result;
    }

    public function getSkuCombinationView(object $request, ?object $product = null): string
    {
        $colorsActive = ($request->has('colors_active') && $request->has('colors') && count($request['colors']) > 0) ? 1 : 0;
        $unitPrice = $request['unit_price'];
        $productName = $request['name'][array_search('en', $request['lang'])];
        $options = $this->getOptions(request: $request);
        $combinations = $this->getCombinations(arrays: $options);
        $combinations = $this->generatePhysicalVariationCombination(request: $request, options: $options, combinations: $combinations, product: $product);
        $generatedCombinations = json_decode($request->generated_combinations ?? '[]', true);
        $generatedCombinations = is_array($generatedCombinations) && !empty($generatedCombinations) ? collect($generatedCombinations)->filter(fn($item) => isset($item['option']))->keyBy(fn($item) => strtolower($item['option']))->toArray() : [];

        foreach ($combinations as &$combination) {
            $key = strtolower($combination['type']);
            if (!empty($generatedCombinations[$key])) {
                $combination['price'] = round($generatedCombinations[$key]['price']);
                $combination['sku']   = $generatedCombinations[$key]['sku']   ?? $combination['sku'];
                $combination['qty']   = $generatedCombinations[$key]['stock'] ?? $combination['qty'];
            }
        }
        if ($product) {
            return view('admin-views.product.partials._edit-sku-combinations', compact('combinations', 'unitPrice', 'colorsActive', 'productName'))->render();
        } else {
            return view('admin-views.product.partials._sku-combinations', compact('combinations', 'unitPrice', 'colorsActive', 'productName'))->render();
        }
    }

    public function getVariations(object $request, array $combinations): array
    {
        $variations = [];
        if (isset($combinations[0]) && count($combinations[0]) > 0) {
            foreach ($combinations as $combination) {
                $str = '';
                foreach ($combination as $combinationKey => $item) {
                    if ($combinationKey > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                    } else {
                        if ($request->has('colors_active') && $request->has('colors') && count($request['colors']) > 0) {
                            $color_name = $this->color->where('code', $item)->first()->name;
                            $str .= $color_name;
                        } else {
                            $str .= str_replace(' ', '', $item);
                        }
                    }
                }
                $item = [];
                $item['type'] = $str;
                $item['price'] = currencyConverter(abs($request['price_' . str_replace('.', '_', $str)]));
                $item['sku'] = $request['sku_' . str_replace('.', '_', $str)];
                $item['qty'] = abs($request['qty_' . str_replace('.', '_', $str)]);
                $variations[] = $item;
            }
        }

        return $variations;
    }

    public function getTotalQuantity(array $variations): int
    {
        $sum = 0;
        foreach ($variations as $item) {
            if (isset($item['qty'])) {
                $sum += $item['qty'];
            }
        }
        return $sum;
    }

    public function getCategoryDropdown(object $request, object $categories): string
    {
        $dropdown = '<option value="' . 0 . '" disabled selected>---' . translate("Select") . '---</option>';
        foreach ($categories as $row) {
            if ($row->id == $request['sub_category']) {
                $dropdown .= '<option value="' . $row->id . '" selected >' . $row->defaultName . '</option>';
            } else {
                $dropdown .= '<option value="' . $row->id . '">' . $row->defaultName . '</option>';
            }
        }

        return $dropdown;
    }

    public function deleteImages(object $product): bool
    {
        foreach (json_decode($product['images'], true) as $image) {
            $this->delete(filePath: '/product/' . (isset($image['image_name']) ? $image['image_name'] : $image));
        }
        $this->delete(filePath: '/product/thumbnail/' . $product['thumbnail']);

        return true;
    }

    public function deletePreviewFile(object $product): bool
    {
        if ($product['preview_file']) {
            $this->delete(filePath: '/product/preview/' . $product['preview_file']);
        }
        return true;
    }

    public function deleteImage(object $request, object $product): array
    {
        $colors = json_decode($product['colors']);
        $color_image = json_decode($product['color_image']);
        $images = [];
        $imageNames = [];
        $color_images = [];
        if ($colors && $color_image) {
            foreach ($color_image as $img) {
                if ($img->color != $request['color'] && $img?->image_name != $request['name']) {
                    $imageNames[] = $img->image_name;
                    $color_images[] = [
                        'color' => $img->color != null ? $img->color : null,
                        'image_name' => $img->image_name,
                        'storage' => $img?->storage ?? 'public',
                    ];
                }
            }

            foreach (json_decode($product['images']) as $image) {
                $imageName = $image?->image_name ?? $image;
                if ($imageName != $request['name'] && !in_array($imageName, $imageNames)) {
                    $color_images[] = [
                        'color' => null,
                        'image_name' => $imageName,
                        'storage' => $image?->storage ?? 'public',
                    ];
                }
            }
        }

        foreach (json_decode($product['images']) as $image) {
            $imageName = $image?->image_name ?? $image;
            if ($imageName != $request['name']) {
                $images[] = $image;
            }
        }

        return [
            'images' => $images,
            'color_images' => $color_images
        ];
    }

    public function getAddProductData(object $request, string $addedBy, int|string $shopId): array
    {
        if (!$request->hasFile('image') && $request->hasFile('images')) {
            $files = $request->file('images');
            if (count($files) > 0) {
                $request->files->set('image', $files[0]);
            }
        }
        $storage = config('filesystems.disks.default') ?? 'public';
        $processedImages = $this->getProcessedImages(request: $request); //once the images are processed do not call this function again just use the variable
        $combinations = $this->getCombinations($this->getOptions(request: $request));
        $variations = $this->getVariations(request: $request, combinations: $combinations);
        $stockCount = isset($combinations[0]) && count($combinations[0]) > 0 ? $this->getTotalQuantity(variations: $variations) : (integer)$request['current_stock'];

        $pricingService = app(\App\Services\PricingService::class);
        if ($addedBy == 'seller') {
            $vendorCost = currencyConverter(amount: (float)($request['purchase_price'] ?? $request['unit_price'] ?? 0));
            $pricing = $pricingService->calculateRetailPrice($vendorCost, $request['category_id'] ?? null);
            $unitPrice = $pricing['unit_price'];
            $purchasePrice = $vendorCost;
            $variations = $pricingService->calculateVariationPrices($variations, $request['category_id'] ?? null);
        } else {
            $unitPrice = currencyConverter(amount: (float)($request['unit_price'] ?? 0));
            $purchasePrice = currencyConverter(amount: (float)($request['purchase_price'] ?? 0));
        }

        return [
            'added_by' => $addedBy,
            'user_id' => $addedBy == 'admin' ? auth('admin')->id() : auth('seller')->id(),
            'shop_id' => $shopId,
            'name' => $request['name'][array_search('en', $request['lang'])],
            'code' => $request['code'],
            'slug' => $this->getSlug($request),
            'category_ids' => json_encode($this->getCategoriesArray(request: $request)),
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
            'sub_sub_category_id' => $request['sub_sub_category_id'],
            'brand_id' => $request['product_type'] == "physical" ? ( $request['brand_id'] ?? null) : null,
            'unit' => $request['product_type'] == 'physical' ? $request['unit'] : null,
            'product_type' => $request['product_type'],
            'details' => $request['description'][array_search('en', $request['lang'])],
            'colors' => $this->getColorsObject(request: $request),
            'choice_options' => $request['product_type'] == 'physical' ? json_encode($this->getChoiceOptions(request: $request)) : json_encode([]),
            'variation' => $request['product_type'] == 'physical' ? json_encode($variations) : json_encode([]),
            'specifications' => $request->has('specifications') && is_array($request['specifications']) ? $request['specifications'] : null,
            'unit_price' => $unitPrice,
            'purchase_price' => $purchasePrice,
            'tax' => $request['tax_type'] == 'flat' ? currencyConverter(amount: $request['tax']) : $request['tax'],
            'tax_type' => $request->get('tax_type', 'percent'),
            'tax_model' => $request['tax_model'],
            'discount' => $request['discount_type'] == 'flat' ? currencyConverter(amount: $request['discount']) : $request['discount'],
            'discount_type' => $request['discount_type'],
            'attributes' => $request['product_type'] == 'physical' ? json_encode($request['choice_attributes']) : json_encode([]),
            'current_stock' => $request['product_type'] == 'physical' ? abs($stockCount) : 999999999,
            'minimum_order_qty' => $request['minimum_order_qty'],
            'video_provider' => 'youtube',
            'video_url' => $request['video_url'],
            'status' => $addedBy == 'admin' ? 1 : 0,
            'request_status' => $addedBy == 'admin' ? 1 : (getWebConfig(name: 'new_product_approval') == 1 ? 0 : 1),
            'price_updated_at' => now(),
            'price_expiry_notified_at' => null,
            'deactivation_reason' => null,
            'shipping_cost' => $request['product_type'] == 'physical' ? currencyConverter(amount: $request['shipping_cost']) : 0,
            'multiply_qty' => ($request['product_type'] == 'physical') ? ($request['multiply_qty'] == 'on' ? 1 : 0) : 0, //to be changed in form multiply_qty
            'color_image' => json_encode($processedImages['colored_image_names']),
            'images' => json_encode($processedImages['image_names']),
            'thumbnail' => $request->has('image') ? $this->upload(dir: 'product/thumbnail/', format: 'webp', image: $request['image']) : $request->existing_thumbnail,
            'thumbnail_storage_type' => $request->has('image') ? $storage : null,
            'meta_title' => $request['meta_title'],
            'meta_description' => $request['meta_description'],
            'meta_image' => $request->has('meta_image') ? $this->upload(dir: 'product/meta/', format: 'webp', image: $request['meta_image']) : $request->existing_meta_image,
            'gtin' => $request['gtin'] ?? null,
            'mpn' => $request['mpn'] ?? null,
            'google_category_id' => $request['google_category_id'] ?? null,
            'marketplace_listing_status' => 'unlisted',
            'marketplace_availability' => 'in_stock',
            'marketplace_confirmed_at' => null,
        ];
    }

    public function getUpdateProductData(object $request, object $product, string $updateBy): array
    {
        if (!$request->hasFile('image') && $request->hasFile('images')) {
            $files = $request->file('images');
            if (count($files) > 0) {
                $request->files->set('image', $files[0]);
            }
        }
        $storage = config('filesystems.disks.default') ?? 'public';
        $processedImages = $this->getProcessedUpdateImages(request: $request, product: $product);
        $combinations = $this->getCombinations($this->getOptions(request: $request));
        $variations = $this->getVariations(request: $request, combinations: $combinations);
        $stockCount = isset($combinations[0]) && count($combinations[0]) > 0 ? $this->getTotalQuantity(variations: $variations) : (integer)$request['current_stock'];

        $pricingService = app(\App\Services\PricingService::class);
        if ($updateBy == 'seller') {
            $vendorCost = currencyConverter(amount: (float)($request['purchase_price'] ?? $request['unit_price'] ?? $product['purchase_price'] ?? 0));
            $pricing = $pricingService->calculateRetailPrice($vendorCost, $request['category_id'] ?? $product['category_id'] ?? null);
            $unitPrice = $pricing['unit_price'];
            $purchasePrice = $vendorCost;
            $variations = $pricingService->calculateVariationPrices($variations, $request['category_id'] ?? $product['category_id'] ?? null);
        } else {
            $unitPrice = currencyConverter(amount: (float)($request['unit_price'] ?? $product['unit_price'] ?? 0));
            $purchasePrice = currencyConverter(amount: (float)($request['purchase_price'] ?? $product['purchase_price'] ?? 0));
        }

        $dataArray = [
            'name' => $request['name'][array_search('en', $request['lang'])],
            'code' => $request['code'],
            'product_type' => $request['product_type'],
            'category_ids' => json_encode($this->getCategoriesArray(request: $request)),
            'category_id' => $request['category_id'],
            'sub_category_id' => $request['sub_category_id'],
            'sub_sub_category_id' => $request['sub_sub_category_id'],
            'brand_id' => $request['product_type'] == "physical" ? ($request['brand_id'] ?? null) : null,
            'unit' => $request['product_type'] == 'physical' ? $request['unit'] : null,
            'details' => $request['description'][array_search('en', $request['lang'])],
            'colors' => $this->getColorsObject(request: $request),
            'choice_options' => $request['product_type'] == 'physical' ? json_encode($this->getChoiceOptions(request: $request)) : json_encode([]),
            'variation' => $request['product_type'] == 'physical' ? json_encode($variations) : json_encode([]),
            'specifications' => $request->has('specifications') && is_array($request['specifications']) ? $request['specifications'] : null,
            'unit_price' => $unitPrice,
            'purchase_price' => $purchasePrice,
            'tax' => $request['tax_type'] == 'flat' ? currencyConverter(amount: $request['tax']) : $request['tax'],
            'tax_type' => $request['tax_type'],
            'tax_model' => $request['tax_model'],
            'discount' => $request['discount_type'] == 'flat' ? currencyConverter(amount: $request['discount']) : $request['discount'],
            'discount_type' => $request['discount_type'],
            'attributes' => $request['product_type'] == 'physical' ? json_encode($request['choice_attributes']) : json_encode([]),
            'current_stock' => $request['product_type'] == 'physical' ? abs($stockCount) : 999999999,
            'minimum_order_qty' => $request['minimum_order_qty'],
            'video_provider' => 'youtube',
            'video_url' => $request['video_url'],
            'price_updated_at' => now(),
            'price_expiry_notified_at' => null,
            'deactivation_reason' => null,
            'multiply_qty' => ($request['product_type'] == 'physical') ? ($request['multiply_qty'] == 'on' ? 1 : 0) : 0,
            'color_image' => json_encode($processedImages['colored_image_names']),
            'images' => json_encode($processedImages['image_names']),
            'meta_title' => $request['meta_title'],
            'meta_description' => $request['meta_description'],
            'meta_image' => $request->file('meta_image') ? $this->update(dir: 'product/meta/', oldImage: $product['meta_image'], format: 'png', image: $request['meta_image']) : $product['meta_image'],
            'gtin' => $request['gtin'] ?? $product['gtin'],
            'mpn' => $request['mpn'] ?? $product['mpn'],
            'google_category_id' => $request['google_category_id'] ?? $product['google_category_id'],
        ];

        if ($request->file('image')) {
            $dataArray += [
                'thumbnail' => $this->update(dir: 'product/thumbnail/', oldImage: $product['thumbnail'], format: 'webp', image: $request['image'], fileType: 'image'),
                'thumbnail_storage_type' => $storage
            ];
        }

        if ($updateBy == 'seller' && getWebConfig(name: 'product_wise_shipping_cost_approval') == 1 && $product->shipping_cost != currencyConverter($request->shipping_cost)) {
            $dataArray += [
                'temp_shipping_cost' => currencyConverter($request->shipping_cost),
                'is_shipping_cost_updated' => 0,
                'shipping_cost' => $product->shipping_cost,
            ];
        } else {
            $dataArray += [
                'shipping_cost' => $request['product_type'] == 'physical' ? currencyConverter(amount: $request['shipping_cost']) : 0,
            ];
        }
        if ($updateBy == 'seller') {
            $needsApproval = $this->shouldRequireUpdateApproval(oldProduct: $product, newData: $dataArray, updateBy: $updateBy);
            $dataArray += [
                'request_status' => $needsApproval ? 0 : 1,
                'status' => $needsApproval ? 0 : 1,
            ];
        }
        if ($updateBy == 'admin' && $product->added_by == 'seller' && ($product->request_status == 2 || $product->request_status == 0)) {
            $dataArray += [
                'request_status' => 1,
                'status' => 1,
            ];
        }

        return $dataArray;
    }

    /**
     * [AI] Determine if a vendor product update requires Admin re-approval based on business policy.
     *
     * @param object $oldProduct
     * @param array $newData
     * @param string $updateBy
     * @return bool
     */
    public function shouldRequireUpdateApproval(object $oldProduct, array $newData, string $updateBy): bool
    {
        if ($updateBy !== 'seller') {
            return false;
        }

        // Previously denied products must always be reviewed again
        if (isset($oldProduct->request_status) && $oldProduct->request_status == 2) {
            return true;
        }

        $approvalMode = getWebConfig(name: 'product_edit_approval_mode') ?? 'threshold';

        if ($approvalMode === 'strict') {
            return true;
        }

        if ($approvalMode === 'auto') {
            return false;
        }

        // Mode: 'threshold' (Default)
        $thresholdPercent = (float)(getWebConfig(name: 'product_edit_price_threshold_percentage') ?? 20.0);
        $oldPrice = (float)($oldProduct->purchase_price > 0 ? $oldProduct->purchase_price : ($oldProduct->unit_price ?? 0));
        $newPrice = (float)($newData['purchase_price'] ?? $newData['unit_price'] ?? $oldPrice);

        if ($oldPrice > 0) {
            $variance = abs($newPrice - $oldPrice) / $oldPrice * 100.0;
            if ($variance > $thresholdPercent) {
                return true;
            }
        }

        // Category change warrants review to prevent policy evasion
        if (isset($newData['category_id']) && $oldProduct->category_id != $newData['category_id']) {
            return true;
        }

        return false;
    }

    public function getUniqueProductSKUCode(): string
    {
        $code = strtoupper(Str::random('6'));
        if (\App\Models\Product::where('code', $code)->exists()) {
            return self::getUniqueProductSKUCode();
        }
        return $code;
    }

    public function getImportBulkProductData(object $request, string $addedBy, int|string $shopId): array
    {
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            return [
                'status' => false,
                'message' => translate('you_have_uploaded_a_wrong_format_file') . ',' . translate('please_upload_the_right_file'),
                'products' => []
            ];
        }

        $columnKey = [
            'name',
            'category_id',
            'sub_category_id',
            'sub_sub_category_id',
            'brand_id', 'unit',
            'minimum_order_qty',
            'status',
            'refundable',
            'youtube_video_url',
            'unit_price',
            'purchase_price',
            'tax_ids',
            'discount',
            'discount_type',
            'current_stock',
            'details',
            'thumbnail',
            'specifications',
            'delivery_hub_id'
        ];
        $skip = ['sub_category_id', 'sub_sub_category_id', 'brand_id', 'youtube_video_url', 'details', 'thumbnail', 'purchase_price', 'specifications', 'delivery_hub_id'];

        if (count($collections) <= 0) {
            return [
                'status' => false,
                'message' => translate('you_need_to_upload_with_proper_data'),
                'products' => []
            ];
        }

        $products = [];
        $productsTax = [];
        $pricingService = app(\App\Services\PricingService::class);

        foreach ($collections as $collection) {
            foreach ($collection as $key => $value) {
                if ($key != "" && !in_array($key, $columnKey)) {
                    return [
                        'status' => false,
                        'message' => translate('Please_upload_the_correct_format_file'),
                        'products' => []
                    ];
                }

                if ($key != "" && $value === "" && !in_array($key, $skip)) {
                    return [
                        'status' => false,
                        'message' => translate('Please fill ' . $key . ' fields'),
                        'products' => []
                    ];
                }
            }
            $thumbnail = explode('/', $collection['thumbnail']);

            $productCode = self::getUniqueProductSKUCode();

            $productsTax[$productCode] = $collection['tax_ids'];

            // [AI] Dynamic Retail Margin & Pricing Resolution on Bulk Import
            if ($addedBy == 'seller') {
                $vendorCost = currencyConverter(amount: (float)($collection['purchase_price'] ?? $collection['unit_price'] ?? 0));
                $pricing = $pricingService->calculateRetailPrice($vendorCost, $collection['category_id'] ?? null);
                $unitPrice = $pricing['unit_price'];
                $purchasePrice = $vendorCost;
            } else {
                $unitPrice = currencyConverter(amount: (float)($collection['unit_price'] ?? 0));
                $purchasePrice = currencyConverter(amount: (float)($collection['purchase_price'] ?? 0));
            }

            $specs = null;
            if (!empty($collection['specifications'])) {
                $decoded = json_decode($collection['specifications'], true);
                $specs = is_array($decoded) ? $decoded : null;
            }

            $products[] = [
                'name' => $collection['name'],
                'shop_id' => $shopId,
                'slug' => Str::slug($collection['name'], '-') . '-' . Str::random(6),
                'category_ids' => json_encode([['id' => (string)$collection['category_id'], 'position' => 1], ['id' => (string)$collection['sub_category_id'], 'position' => 2], ['id' => (string)$collection['sub_sub_category_id'], 'position' => 3]]),
                'category_id' => $collection['category_id'],
                'sub_category_id' => $collection['sub_category_id'],
                'sub_sub_category_id' => $collection['sub_sub_category_id'],
                'brand_id' => $collection['brand_id'],
                'unit' => $collection['unit'],
                'minimum_order_qty' => $collection['minimum_order_qty'],
                'refundable' => $collection['refundable'],
                'unit_price' => $unitPrice,
                'purchase_price' => $purchasePrice,
                'discount' => ($collection['discount_type'] ?? 'percent') == 'flat' ? currencyConverter(amount: $collection['discount'] ?? 0) : ($collection['discount'] ?? 0),
                'discount_type' => $collection['discount_type'] ?? 'percent',
                'shipping_cost' => 0,
                'current_stock' => $collection['current_stock'],
                'details' => $collection['details'],
                'video_provider' => 'youtube',
                'video_url' => $collection['youtube_video_url'],
                'images' => json_encode(['def.png']),
                'thumbnail' => $thumbnail[1] ?? $thumbnail[0],
                'status' => $addedBy == 'admin' && ($collection['status'] ?? 1) == 1 ? 1 : 0,
                'request_status' => $addedBy == 'admin' ? 1 : (getWebConfig(name: 'new_product_approval') == 1 ? 0 : 1),
                'price_updated_at' => now(),
                'price_expiry_notified_at' => null,
                'deactivation_reason' => null,
                'specifications' => $specs,
                'colors' => json_encode([]),
                'attributes' => json_encode([]),
                'choice_options' => json_encode([]),
                'variation' => json_encode([]),
                'featured_status' => 0,
                'added_by' => $addedBy,
                'user_id' => $addedBy == 'admin' ? auth('admin')->id() : auth('seller')->id(),
                'code' => $productCode,
                'created_at' => now(),
            ];
        }

        return [
            'status' => true,
            'message' => count($products) . ' - ' . translate('products_imported_successfully'),
            'products' => $products,
            'productsTax' => $productsTax,
        ];
    }

    public function generatePhysicalVariationCombination(object|array $request, object|array $options, object|array $combinations, object|array|null $product): array
    {
        $productName = $request['name'][array_search('en', $request['lang'])];
        $unitPrice = $request['unit_price'];

        $generateCombination = [];
        $existingType = [];

        if ($product && $product->variation && count(json_decode($product->variation, true)) > 0) {
            foreach (json_decode($product->variation, true) as $digitalVariation) {
                $existingType[] = $digitalVariation['type'];
            }
        }

        $existingType = array_unique($existingType);

        $combinations = array_filter($combinations, function ($value) {
            return !empty($value);
        });

        foreach ($combinations as $combination) {
            $type = '';
            foreach ($combination as $combinationKey => $item) {
                if ($combinationKey > 0) {
                    $type .= '-' . str_replace(' ', '', $item);
                } else {
                    if ($request->has('colors_active') && $request->has('colors') && count($request['colors']) > 0) {
                        $color_name = $this->color->where('code', $item)->first()->name;
                        $type .= $color_name;
                    } else {
                        $type .= str_replace(' ', '', $item);
                    }
                }
            }

            $sku = '';
            foreach (explode(' ', $productName) as $value) {
                $sku .= substr($value, 0, 1);
            }
            $sku .= '-' . $type;
            if (in_array($type, $existingType)) {
                if ($product && $product->variation && count(json_decode($product->variation, true)) > 0) {
                    foreach (json_decode($product->variation, true) as $digitalVariation) {
                        if ($digitalVariation['type'] == $type) {
                            $digitalVariation['sku'] = str_replace(' ', '', $digitalVariation['sku']);
                            $generateCombination[] = $digitalVariation;
                        }
                    }
                }
            } else {
                $generateCombination[] = [
                    'type' => $type,
                    'price' => currencyConverter(amount: $unitPrice),
                    'sku' => str_replace(' ', '', $sku),
                    'qty' => 1,
                ];
            }
        }

        return $generateCombination;
    }

    public function getProductSEOData(object $request, object|null $product = null, ?string $action = null): array
    {
        if ($product) {
            if ($request->file('meta_image')) {
                $metaImage = $this->update(dir: 'product/meta/', oldImage: $product['meta_image'], format: 'png', image: $request['meta_image']);
            } elseif (!$request->file('meta_image') && $request->file('image') && $action == 'add') {
                $metaImage = $this->upload(dir: 'product/meta/', format: 'webp', image: $request['image']);
            } else {
                $metaImage = $product?->seoInfo?->image ?? $product['meta_image'];
            }
        } else {
            if ($request->file('meta_image')) {
                $metaImage = $this->upload(dir: 'product/meta/', format: 'webp', image: $request['meta_image']);
            } elseif (!$request->file('meta_image') && $request->file('image') && $action == 'add') {
                $metaImage = $this->upload(dir: 'product/meta/', format: 'webp', image: $request['image']);
            }
        }
        return [
            "product_id" => $product['id'],
            "title" => $request['meta_title'] ?? ($product ? $product['meta_title'] : null),
            "description" => $request['meta_description'] ?? ($product ? $product['meta_description'] : null),
            "index" => $request['meta_index'] == 'index' ? '' : 'noindex',
            "no_follow" => $request['meta_no_follow'] ? 'nofollow' : '',
            "no_image_index" => $request['meta_no_image_index'] ? 'noimageindex' : '',
            "no_archive" => $request['meta_no_archive'] ? 'noarchive' : '',
            "no_snippet" => $request['meta_no_snippet'] ?? 0,
            "max_snippet" => $request['meta_max_snippet'] ?? 0,
            "max_snippet_value" => $request['meta_max_snippet_value'] ?? 0,
            "max_video_preview" => $request['meta_max_video_preview'] ?? 0,
            "max_video_preview_value" => $request['meta_max_video_preview_value'] ?? 0,
            "max_image_preview" => $request['meta_max_image_preview'] ?? 0,
            "max_image_preview_value" => $request['meta_max_image_preview_value'] ?? 0,
            "image" => $metaImage ?? ($product ? $product['meta_image'] : null),
            "created_at" => now(),
            "updated_at" => now(),
        ];
    }

    public function sendRestockProductNotification(object|array $restockRequest, ?string $type = null): void
    {
        // Send Notification to customer
        $data = [
            'topic' => getRestockProductFCMTopic(restockRequest: $restockRequest),
            'title' => $restockRequest?->product?->name,
            'product_id' => $restockRequest?->product?->id,
            'slug' => $restockRequest?->product?->slug,
            'description' => $type == 'restocked' ? translate('This_product_has_restocked') : translate('Your_requested_restock_product_has_been_updated'),
            'image' => getStorageImages(path: $restockRequest?->product?->thumbnail_full_url ?? '', type: 'product'),
            'route' => route('product', $restockRequest?->product?->slug),
            'type' => 'product_restock_update',
            'status' => $type == 'restocked' ? 'product_restocked' : 'product_update',
        ];
        event(new RestockProductNotificationEvent(data: $data));
    }

    public function validateStockClearanceProductDiscount($stockClearanceProduct): bool
    {
        if ($stockClearanceProduct && $stockClearanceProduct['discount_type'] == 'flat' && $stockClearanceProduct->setup && $stockClearanceProduct->setup->discount_type == 'product_wise') {
            $minimumPrice = $stockClearanceProduct->product?->unit_price;
            foreach ((json_decode($stockClearanceProduct->product?->variation, true) ?? []) as $variation) {
                if ($variation['price'] < $minimumPrice) {
                    $minimumPrice = $variation['price'];
                }
            }
            if ($minimumPrice < $stockClearanceProduct['discount_amount']) {
                return false;
            }
        }
        return true;
    }

    /**
     * [AI] Dedicated authorized transition: Confirm availability and list/relist product.
     * Enforces strict seller tenant ownership.
     */
    public function confirmMarketplaceListing(Product $product, int $sellerId): bool
    {
        if ($product->added_by !== 'seller' || (int)$product->user_id !== $sellerId) {
            return false;
        }

        $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
        $now = now();

        // [AI] Write both canonical lifecycle fields AND legacy backcompat field
        $product->marketplace_confirmed_at  = $now;                              // legacy backcompat
        $product->availability_confirmed_at = $now;                              // canonical
        $product->availability_expires_at   = $now->copy()->addDays($confirmationDays); // runtime gate
        $product->marketplace_listing_status = 'listed';
        $product->marketplace_availability   = 'in_stock';
        $product->current_stock              = 1;                                // legacy mirror
        $product->deactivation_reason        = null;
        $product->save();

        cacheRemoveByType(type: 'products');
        return true;
    }

    /**
     * [AI] Dedicated authorized transition: Toggle marketplace availability (in_stock / out_of_stock).
     *
     * in_stock: Renews the lifecycle fields (availability_confirmed_at, availability_expires_at)
     *           and sets current_stock = 1 (legacy mirror only — never used for purchase authority).
     * out_of_stock: Clears availability_expires_at (instant runtime block) and sets current_stock = 0.
     *
     * Strictly: internal POS or inventory current_stock management is NOT performed here.
     */
    public function updateMarketplaceAvailability(Product $product, int $sellerId, string $availability): bool
    {
        if ($product->added_by !== 'seller' || (int)$product->user_id !== $sellerId) {
            return false;
        }

        if (!in_array($availability, ['in_stock', 'out_of_stock'])) {
            return false;
        }

        $now = now();
        $product->marketplace_availability = $availability;

        if ($availability === 'in_stock') {
            $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
            // [AI] Renew both canonical lifecycle fields and legacy backcompat field
            $product->marketplace_confirmed_at  = $now;                              // legacy backcompat
            $product->availability_confirmed_at = $now;                              // canonical
            $product->availability_expires_at   = $now->copy()->addDays($confirmationDays); // runtime gate
            $product->current_stock             = 1;                                 // legacy mirror (never 999)
        } else {
            // [AI] out_of_stock: clear expiry so runtime gate immediately rejects; set legacy mirror to 0
            $product->availability_confirmed_at = $now;
            $product->availability_expires_at   = null;
            $product->current_stock             = 0;                                 // legacy mirror
        }

        $product->save();

        cacheRemoveByType(type: 'products');
        return true;
    }

    /**
     * [AI] Dedicated authorized transition: Update marketplace listing status (listed / unlisted).
     */
    public function updateMarketplaceListingStatus(Product $product, int $sellerId, string $status): bool
    {
        if ($product->added_by !== 'seller' || (int)$product->user_id !== $sellerId) {
            return false;
        }

        if (!in_array($status, ['listed', 'unlisted'])) {
            return false;
        }

        $product->marketplace_listing_status = $status;
        $product->save();

        cacheRemoveByType(type: 'products');
        return true;
    }

    /**
     * [AI] Dedicated authorized transition: Bulk confirm marketplace listings for a vendor.
     */
    public function bulkConfirmMarketplaceListings(array $productIds, int $sellerId): int
    {
        $products = Product::whereIn('id', $productIds)
            ->where('added_by', 'seller')
            ->where('user_id', $sellerId)
            ->get();

        $confirmationDays = function_exists('getMarketplaceConfirmationDays') ? getMarketplaceConfirmationDays() : 7;
        $now = now();
        $count = 0;

        foreach ($products as $product) {
            // [AI] Renew all lifecycle fields (canonical + legacy backcompat + current_stock mirror)
            $product->marketplace_confirmed_at  = $now;                              // legacy backcompat
            $product->availability_confirmed_at = $now;                              // canonical
            $product->availability_expires_at   = $now->copy()->addDays($confirmationDays); // runtime gate
            $product->marketplace_listing_status = 'listed';
            $product->marketplace_availability   = 'in_stock';
            $product->current_stock              = 1;                                // legacy mirror
            $product->deactivation_reason        = null;
            $product->save();
            $count++;
        }

        if ($count > 0) {
            cacheRemoveByType(type: 'products');
        }

        return $count;
    }
}
