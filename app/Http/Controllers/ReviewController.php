<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Models\Shipment;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function publicIndex(): View
    {
        return view('reviews.index', [
            'reviews' => Review::query()->approved()->with('user:id,name')->latest()->paginate(12),
            'averageRating' => round((float) Review::query()->approved()->avg('rating'), 1),
            'reviewCount' => Review::query()->approved()->count(),
        ]);
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole(UserRoles::Customer->value), 403);

        return view('account.reviews.index', [
            'reviews' => Review::query()
                ->where('user_id', $request->user()->id)
                ->with('shipment:id,tracking_number')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function store(StoreReviewRequest $request, Shipment $shipment, ReviewService $reviews): RedirectResponse
    {
        $validated = $request->validated();
        $reviews->submit(
            $request->user(),
            $shipment,
            (int) $validated['rating'],
            $validated['comment'] ?? null,
        );

        return back()->with('status', __('app.reviews.submitted'));
    }
}