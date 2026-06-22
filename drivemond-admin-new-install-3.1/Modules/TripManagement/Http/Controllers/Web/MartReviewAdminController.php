<?php

namespace Modules\TripManagement\Http\Controllers\Web;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\TripManagement\Entities\MartReview;

class MartReviewAdminController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('vito_mart_view');

        $search = $request->search;
        $rating = $request->rating;

        $reviews = MartReview::with(['order', 'customer', 'driver'])
            ->when($rating, fn ($q) => $q->where('rating', $rating))
            ->when($search, function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('order', fn ($o) => $o->where('ref_id', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(paginationLimit())
            ->appends($request->all());

        $averageRating = round((float) MartReview::avg('rating'), 1);

        return view('tripmanagement::admin.mart.reviews.index', compact('reviews', 'search', 'rating', 'averageRating'));
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorize('vito_mart_delete');
        MartReview::findOrFail($id)->delete();
        Toastr::success(translate('review_deleted_successfully'));
        return back();
    }
}
