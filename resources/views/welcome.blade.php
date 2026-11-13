@php
    $shipmentCount = $shipmentCount ?? (\class_exists(\App\Models\Shipment::class) ? \App\Models\Shipment::query()->count() : 1250);
    $deliveredCount = $deliveredCount ?? (\class_exists(\App\Models\Shipment::class) ? \App\Models\Shipment::query()->where('status', 'delivered')->count() : 1180);
    $cityCount = $cityCount ?? (\class_exists(\App\Models\Shipment::class) ? \App\Models\Shipment::query()->distinct()->count('receiver_city') : 28);
    $reviewCount = $reviewCount ?? (\class_exists(\App\Models\Review::class) ? \App\Models\Review::query()->approved()->count() : 450);
    $averageRating = $averageRating ?? (\class_exists(\App\Models\Review::class) ? round((float) \App\Models\Review::query()->approved()->avg('rating'), 1) : 4.9);
    $reviews = $reviews ?? (\class_exists(\App\Models\Review::class) ? \App\Models\Review::query()->approved()->with('user:id,name')->latest()->limit(3)->get() : collect());
@endphp
@include('pages.home')
