<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Shipment;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        $reviews = Review::query()
            ->approved()
            ->with('user:id,name')
            ->latest()
            ->limit(3)
            ->get();

        return view('pages.home', [
            'shipmentCount' => Shipment::query()->count(),
            'deliveredCount' => Shipment::query()->where('status', 'delivered')->count(),
            'cityCount' => Shipment::query()->distinct()->count('receiver_city'),
            'reviewCount' => Review::query()->approved()->count(),
            'averageRating' => round((float) Review::query()->approved()->avg('rating'), 1),
            'reviews' => $reviews,
        ]);
    }
}
