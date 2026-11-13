<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\PublicSiteController::class, 'home'])->name('home');
Route::view('/about', 'pages.about')->name('about');
Route::view('/services', 'pages.services')->name('services');
Route::view('/contact', 'pages.contact')->name('contact');
Route::post('/contact', [App\Http\Controllers\ContactController::class, 'submit'])
    ->middleware('throttle:5,1')
    ->name('contact.submit');
Route::middleware('auth')->group(function () {
    Route::get('/internal/shipments/scan', [App\Http\Controllers\ShipmentScanController::class, 'form'])->name('shipments.scan.form');
    Route::post('/internal/shipments/scan', [App\Http\Controllers\ShipmentScanController::class, 'scan'])
        ->middleware('throttle:30,1')
        ->name('shipments.scan');
});
Route::view('/help', 'pages.help')->name('help');
Route::view('/assistant', 'pages.assistant')->name('assistant.show');
Route::post('/assistant/chat', App\Http\Controllers\AssistantChatController::class)
    ->middleware('throttle:12,1')
    ->name('assistant.chat');
Route::get('/reviews', [App\Http\Controllers\ReviewController::class, 'publicIndex'])->name('reviews.index');
Route::get('/track', [App\Http\Controllers\TrackingController::class, 'form'])->name('tracking.form');
Route::post('/track', [App\Http\Controllers\TrackingController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('tracking.lookup');
Route::post('/language', [App\Http\Controllers\LanguageController::class, 'update'])->name('language.update');

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [App\Http\Controllers\AuthController::class, 'showRegistration'])->name('register');
    Route::post('/register', [App\Http\Controllers\AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account/profile', [App\Http\Controllers\CustomerProfileController::class, 'edit'])->name('account.profile.edit');
    Route::put('/account/profile', [App\Http\Controllers\CustomerProfileController::class, 'update'])->name('account.profile.update');
    Route::get('/account', [App\Http\Controllers\AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/approval-status', [App\Http\Controllers\AccountController::class, 'approvalStatus'])->name('approval.status');
    Route::get('/account/shipments', [App\Http\Controllers\ShipmentController::class, 'index'])->name('account.shipments.index');
    Route::get('/account/shipments/create', [App\Http\Controllers\ShipmentController::class, 'create'])->name('account.shipments.create');
    Route::post('/account/shipments', [App\Http\Controllers\ShipmentController::class, 'store'])->name('account.shipments.store');
    Route::get('/account/shipments/{shipment}', [App\Http\Controllers\ShipmentController::class, 'show'])->name('account.shipments.show');
    Route::get('/account/shipments/{shipment}/invoice', [App\Http\Controllers\ShipmentController::class, 'invoice'])->name('account.shipments.invoice');
    Route::get('/account/shipments/{shipment}/label', App\Http\Controllers\ShippingLabelController::class)->name('account.shipments.label');
    Route::post('/account/shipments/{shipment}/location', [App\Http\Controllers\ShipmentController::class, 'updateLocation'])->name('account.shipments.location');
    Route::post('/account/shipments/{shipment}/cancel', [App\Http\Controllers\ShipmentController::class, 'cancel'])->name('account.shipments.cancel');
    Route::post('/account/shipments/{shipment}/reviews', [App\Http\Controllers\ReviewController::class, 'store'])->name('account.shipments.reviews.store');
    Route::get('/account/reviews', [App\Http\Controllers\ReviewController::class, 'index'])->name('account.reviews.index');
    Route::get('/account/notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('account.notifications.index');
    Route::post('/account/notifications/{notificationId}/read', [App\Http\Controllers\NotificationController::class, 'markRead'])->name('account.notifications.read');
    Route::post('/account/shipments/{shipment}/checkout', [App\Http\Controllers\PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/payment/result', [App\Http\Controllers\PaymentController::class, 'result'])->name('payments.result');
    Route::post('/payment/{payment}/confirm', [App\Http\Controllers\PaymentController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('payments.confirm');
    Route::post('/payment/{payment}/cancel', [App\Http\Controllers\PaymentController::class, 'cancel'])
        ->middleware('throttle:10,1')
        ->name('payments.cancel');
});
