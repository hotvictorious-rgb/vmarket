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
}
