<?php

namespace Modules\TripManagement\Http\Controllers\Web;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\TripManagement\Entities\MartCategory;

class MartCategoryAdminController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('vito_mart_view');
        $search = $request->search;
        $categories = MartCategory::when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(paginationLimit())
            ->appends($request->all());

        return view('tripmanagement::admin.mart.categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        $this->authorize('vito_mart_add');
        return view('tripmanagement::admin.mart.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('vito_mart_add');
        $data = $this->validateData($request);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('mart/categories', 'public');
        }
        MartCategory::create($data);
        Toastr::success(translate('category_added_successfully'));
        return redirect()->route('admin.mart.categories.index');
    }

    public function edit(string $id): View
    {
        $this->authorize('vito_mart_edit');
        $category = MartCategory::findOrFail($id);
        return view('tripmanagement::admin.mart.categories.edit', compact('category'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('vito_mart_edit');
        $category = MartCategory::findOrFail($id);
        $data = $this->validateData($request, $id);
        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('mart/categories', 'public');
        }
        $category->update($data);
        Toastr::success(translate('category_updated_successfully'));
        return redirect()->route('admin.mart.categories.index');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('vito_mart_delete');
        MartCategory::findOrFail($id)->delete();
        Toastr::success(translate('category_deleted_successfully'));
        return back();
    }

    public function toggleStatus(string $id): RedirectResponse
    {
        $this->authorize('vito_mart_edit');
        $category = MartCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
        Toastr::success(translate('status_updated_successfully'));
        return back();
    }

    private function validateData(Request $request, ?string $ignoreId = null): array
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0|max:100000',
            'is_active'  => 'nullable|boolean',
            'image'      => 'nullable|image|max:2048',
        ]);

        $slug = Str::slug($validated['name']);
        // Ensure slug uniqueness (the column is unique).
        $base = $slug ?: Str::uuid();
        $i = 1;
        while (MartCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return [
            'name'       => $validated['name'],
            'slug'       => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $request->boolean('is_active'),
        ];
    }
}
