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
use Modules\TripManagement\Entities\MartProduct;

class VitoMartAdminController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('vito_mart_view');
        $search = $request->search;
        $products = MartProduct::when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        })->orderBy('created_at', 'desc')->paginate(15);

        return view('tripmanagement::admin.mart.index', compact('products', 'search'));
    }

    public function create(): View
    {
        $this->authorize('vito_mart_add');
        $categories = MartCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        return view('tripmanagement::admin.mart.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('vito_mart_add');
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'category', 'price', 'stock', 'description']);
        $data['id'] = Str::uuid();
        $data['is_active'] = true;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('mart/products', 'public');
        }

        MartProduct::create($data);
        Toastr::success(translate('product_added_successfully'));

        return redirect()->route('admin.mart.products.index');
    }

    public function edit(string $id): View
    {
        $this->authorize('vito_mart_edit');
        $product = MartProduct::findOrFail($id);
        $categories = MartCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        return view('tripmanagement::admin.mart.edit', compact('product', 'categories'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('vito_mart_edit');
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:2048',
        ]);

        $product = MartProduct::findOrFail($id);
        $data = $request->only(['name', 'category', 'price', 'stock', 'description']);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('mart/products', 'public');
        }

        $product->update($data);
        Toastr::success(translate('product_updated_successfully'));

        return redirect()->route('admin.mart.products.index');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('vito_mart_delete');
        MartProduct::findOrFail($id)->delete();
        Toastr::success(translate('product_deleted_successfully'));

        return redirect()->route('admin.mart.products.index');
    }

    public function toggleStatus(string $id): RedirectResponse
    {
        $this->authorize('vito_mart_edit');
        $product = MartProduct::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);
        Toastr::success(translate('product_status_updated'));

        return redirect()->route('admin.mart.products.index');
    }

    public function stockAdjust(Request $request, $id)
    {
        $this->authorize('vito_mart_edit');
        $product = MartProduct::findOrFail($id);
        $action = $request->input('action');
        if ($action === 'increment') {
            $product->increment('stock');
        } elseif ($action === 'decrement' && $product->stock > 0) {
            $product->decrement('stock');
        }
        return response()->json(['stock' => $product->fresh()->stock]);
    }
}
