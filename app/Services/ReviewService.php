<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    public function submit(User $customer, Shipment $shipment, int $rating, ?string $comment): Review
    {
        abort_unless(
            $customer->hasRole(UserRoles::Customer->value)
                && $shipment->customer_id === $customer->id,
            404,
        );

        return DB::transaction(function () use ($customer, $shipment, $rating, $comment): Review {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            abort_unless($lockedShipment->status === ShipmentStatus::Delivered, 409);
            abort_if(Review::query()->where('shipment_id', $lockedShipment->id)->exists(), 409);

            return Review::query()->create([
                'shipment_id' => $lockedShipment->id,
                'user_id' => $customer->id,
                'rating' => $rating,
                'comment' => $comment,
                'status' => 'pending',
            ]);
        });
    }

    public function moderate(User $actor, Review $review, string $status): Review
    {
        abort_unless(
            $actor->hasRole(UserRoles::Admin->value) && $actor->can('reviews.moderate'),
            403,
        );
        abort_unless(in_array($status, ['approved', 'hidden'], true), 422);

        $review->status = $status;
        $review->save();

        return $review;
    }

    public function delete(User $actor, Review $review): void
    {
        abort_unless(
            $actor->hasRole(UserRoles::Admin->value) && $actor->can('reviews.moderate'),
            403,
        );

        $review->delete();
    }
}