<?php

namespace Modules\TripManagement\Http\Controllers\Web;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\TripManagement\Entities\MartPromoCode;

class MartPromoCodeAdminController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('vito_mart_view');
        $search = $request->search;
        $promos = MartPromoCode::when($search, fn ($q) => $q->where('code', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate(paginationLimit())
            ->appends($request->all());

        return view('tripmanagement::admin.mart.promo.index', compact('promos', 'search'));
    }

    public function create(): View
    {
        $this->authorize('vito_mart_add');
        return view('tripmanagement::admin.mart.promo.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('vito_mart_add');
        $data = $this->validateData($request);
        MartPromoCode::create($data);
        Toastr::success(translate('promo_code_added_successfully'));
        return redirect()->route('admin.mart.promo.index');
    }

    public function edit(string $id): View
    {
        $this->authorize('vito_mart_edit');
        $promo = MartPromoCode::findOrFail($id);
        return view('tripmanagement::admin.mart.promo.edit', compact('promo'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorize('vito_mart_edit');
        $promo = MartPromoCode::findOrFail($id);
        $data = $this->validateData($request, $id);
        $promo->update($data);
        Toastr::success(translate('promo_code_updated_successfully'));
        return redirect()->route('admin.mart.promo.index');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('vito_mart_delete');
        MartPromoCode::findOrFail($id)->delete();
        Toastr::success(translate('promo_code_deleted_successfully'));
        return redirect()->route('admin.mart.promo.index');
    }

    public function toggleStatus(string $id): RedirectResponse
    {
        $this->authorize('vito_mart_edit');
        $promo = MartPromoCode::findOrFail($id);
        $promo->update(['is_active' => !$promo->is_active]);
        Toastr::success(translate('status_updated_successfully'));
        return back();
    }

    private function validateData(Request $request, ?string $ignoreId = null): array
    {
        $codeRule = 'required|string|max:50|unique:mart_promo_codes,code';
        if ($ignoreId) {
            $codeRule .= ',' . $ignoreId;
        }

        $validated = $request->validate([
            'code'             => $codeRule,
            'discount_type'    => 'required|in:percent,fixed',
            'discount_value'   => 'required|numeric|min:0.01|max:999999.99',
            'min_order_amount' => 'nullable|numeric|min:0|max:999999.99',
            'max_discount'     => 'nullable|numeric|min:0|max:999999.99',
            'usage_limit'      => 'nullable|integer|min:1|max:1000000',
            'per_user_limit'   => 'nullable|integer|min:1|max:100000',
            'is_active'        => 'nullable|boolean',
            'expires_at'       => 'nullable|date',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['min_order_amount'] = $validated['min_order_amount'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
