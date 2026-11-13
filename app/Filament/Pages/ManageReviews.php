<?php

namespace App\Filament\Pages;

use App\Enums\UserRoles;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageReviews extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.manage-reviews';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('app.admin_nav.reviews');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasInternalAccess()
            && $user->hasRole(UserRoles::Admin->value)
            && $user->can('reviews.moderate');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Review::query()->with(['user', 'shipment'])->latest())
            ->columns([
                TextColumn::make('shipment.tracking_number')->label(__('app.reviews.shipment'))->searchable(),
                TextColumn::make('user.name')->label(__('app.reviews.customer'))->searchable(),
                TextColumn::make('rating')->label(__('app.reviews.rating'))->badge(),
                TextColumn::make('comment')->label(__('app.reviews.comment'))->wrap()->placeholder('—'),
                TextColumn::make('status')->label(__('app.reviews.status'))->badge(),
                TextColumn::make('created_at')->label(__('app.finance.date'))->date(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('app.reviews.approve'))
                    ->color('success')
                    ->visible(fn (Review $record): bool => $record->status !== 'approved')
                    ->action(fn (Review $record) => app(ReviewService::class)->moderate($this->actor(), $record, 'approved')),
                Action::make('hide')
                    ->label(__('app.reviews.hide'))
                    ->color('warning')
                    ->visible(fn (Review $record): bool => $record->status !== 'hidden')
                    ->action(fn (Review $record) => app(ReviewService::class)->moderate($this->actor(), $record, 'hidden')),
                Action::make('delete')
                    ->label(__('app.reviews.delete'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => app(ReviewService::class)->delete($this->actor(), $record)),
            ])
            ->emptyStateHeading(__('app.reviews.empty'));
    }

    private function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}