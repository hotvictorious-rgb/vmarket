<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategorySpecification;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CategorySpecificationController extends Controller
{
    /**
     * Display Category Specifications Management View
     */
    public function index(Request $request): View
    {
        $categories = Category::where('position', 0)->with('childes.childes')->orderBy('priority', 'asc')->get();
        $selectedCategoryId = $request->get('category_id', $categories->first()?->id);

        $specifications = [];
        $selectedCategory = null;

        if ($selectedCategoryId) {
            $selectedCategory = Category::find($selectedCategoryId);
            $specifications = CategorySpecification::where('category_id', $selectedCategoryId)
                ->orderBy('sort_order', 'asc')
                ->get();
        }

        return view('admin-views.category.specifications', compact('categories', 'selectedCategoryId', 'selectedCategory', 'specifications'));
    }

    /**
     * Store new Category Specification
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:150',
            'input_type' => 'required|in:text,number,select,multi_select',
            'options' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'placeholder' => 'nullable|string|max:150',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $optionsArray = null;
        if (in_array($request->input_type, ['select', 'multi_select']) && !empty($request->options)) {
            $optionsArray = array_values(array_filter(array_map('trim', explode(',', $request->options))));
        }

        CategorySpecification::create([
            'category_id' => $request->category_id,
            'name' => trim($request->name),
            'input_type' => $request->input_type,
            'options' => $optionsArray,
            'unit' => $request->unit ? trim($request->unit) : null,
            'placeholder' => $request->placeholder ? trim($request->placeholder) : null,
            'is_required' => $request->has('is_required'),
            'sort_order' => $request->sort_order ?? (CategorySpecification::where('category_id', $request->category_id)->max('sort_order') + 1),
            'is_active' => true,
        ]);

        ToastMagic::success(translate('Specification question added successfully'));
        return back();
    }

    /**
     * Update Category Specification
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'input_type' => 'required|in:text,number,select,multi_select',
            'options' => 'nullable|string',
            'unit' => 'nullable|string|max:50',
            'placeholder' => 'nullable|string|max:150',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $spec = CategorySpecification::findOrFail($id);

        $optionsArray = null;
        if (in_array($request->input_type, ['select', 'multi_select']) && !empty($request->options)) {
            $optionsArray = array_values(array_filter(array_map('trim', explode(',', $request->options))));
        }

        $spec->update([
            'name' => trim($request->name),
            'input_type' => $request->input_type,
            'options' => $optionsArray,
            'unit' => $request->unit ? trim($request->unit) : null,
            'placeholder' => $request->placeholder ? trim($request->placeholder) : null,
            'is_required' => $request->has('is_required'),
            'sort_order' => $request->sort_order ?? $spec->sort_order,
        ]);

        ToastMagic::success(translate('Specification question updated successfully'));
        return back();
    }

    /**
     * Delete Category Specification
     */
    public function delete($id): RedirectResponse
    {
        $spec = CategorySpecification::findOrFail($id);
        $spec->delete();

        ToastMagic::success(translate('Specification removed successfully'));
        return back();
    }

    /**
     * Toggle Specification Status
     */
    public function status(Request $request): JsonResponse
    {
        $spec = CategorySpecification::findOrFail($request->id);
        $spec->is_active = (bool)$request->status;
        $spec->save();

        return response()->json([
            'success' => true,
            'message' => translate('Status updated successfully'),
        ]);
    }

    /**
     * Get Specifications by Category (AJAX for Product Upload Forms)
     * Checks sub-category, sub-sub-category, and falls back to parent category if empty.
     */
    public function getByCategoryAjax($category_id): JsonResponse
    {
        $specs = CategorySpecification::where('category_id', $category_id)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        // If specific sub-category has no custom questions, inherit from parent category
        if ($specs->isEmpty()) {
            $cat = Category::find($category_id);
            if ($cat && $cat->parent_id > 0) {
                $specs = CategorySpecification::where('category_id', $cat->parent_id)
                    ->where('is_active', true)
                    ->orderBy('sort_order', 'asc')
                    ->get();
            }
        }

        return response()->json([
            'status' => true,
            'specifications' => $specs,
        ]);
    }

    /**
     * Gemini AI Auto-Suggest Specification Values from Product Title & Description
     */
    public function aiSuggestSpecs(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'required|integer',
            'product_name' => 'required|string',
            'product_details' => 'nullable|string',
        ]);

        $specs = CategorySpecification::where('category_id', $request->category_id)
            ->where('is_active', true)
            ->get();

        if ($specs->isEmpty()) {
            $cat = Category::find($request->category_id);
            if ($cat && $cat->parent_id > 0) {
                $specs = CategorySpecification::where('category_id', $cat->parent_id)->where('is_active', true)->get();
            }
        }

        if ($specs->isEmpty()) {
            return response()->json(['status' => false, 'message' => translate('No questions configured for this category.')]);
        }

        $questionsList = $specs->map(function ($s) {
            return [
                'name' => $s->name,
                'input_type' => $s->input_type,
                'options' => $s->options,
                'unit' => $s->unit,
            ];
        })->toArray();

        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            // Fallback heuristic extraction if API key is not present
            $suggested = [];
            $title = $request->product_name;
            foreach ($specs as $s) {
                $nameLower = strtolower($s->name);
                if (str_contains($nameLower, 'storage') || str_contains($nameLower, 'capacity')) {
                    if (preg_match('/\b(32|64|128|256|512|1024)\s*GB\b/i', $title, $m)) {
                        $suggested[$s->name] = strtoupper($m[0]);
                    }
                } elseif (str_contains($nameLower, 'ram')) {
                    if (preg_match('/\b(4|6|8|12|16|32)\s*GB\s*RAM\b/i', $title, $m)) {
                        $suggested[$s->name] = strtoupper($m[0]);
                    }
                } elseif (str_contains($nameLower, 'size') && $s->options) {
                    foreach ($s->options as $opt) {
                        if (stripos($title, $opt) !== false) {
                            $suggested[$s->name] = $opt;
                            break;
                        }
                    }
                }
            }
            return response()->json(['status' => true, 'suggestions' => $suggested]);
        }

        $prompt = "You are an intelligent e-commerce product catalog specialist for Victorious MARKET in Nigeria.\n";
        $prompt .= "Given the product title: \"{$request->product_name}\" and description: \"" . substr($request->product_details ?? '', 0, 500) . "\", extract and fill in the values for the following specification questions:\n";
        $prompt .= json_encode($questionsList) . "\n\n";
        $prompt .= "Return ONLY a valid JSON object where keys are the exact question 'name' and values are the extracted values. If you cannot determine an answer, omit it or set null. Output raw JSON only.";

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.1, 'responseMimeType' => 'application/json'],
                ]);

            if ($response->successful()) {
                $jsonText = $response->json('candidates.0.content.parts.0.text');
                $result = json_decode($jsonText, true) ?? [];
                return response()->json(['status' => true, 'suggestions' => $result]);
            }
        } catch (\Throwable $e) {
            // Silence API failure and return empty suggestions
        }

        return response()->json(['status' => true, 'suggestions' => []]);
    }
}
