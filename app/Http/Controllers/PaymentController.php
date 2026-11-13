<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use App\Models\Payment;
use App\Models\Shipment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function checkout(Request $request, Shipment $shipment, PaymentService $payments): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRoles::Customer->value), 403);

        $url = $payments->createCheckout($request->user(), $shipment);

        return redirect()->away($url);
    }

    public function result(Request $request): View
    {
        abort_unless($request->user()->hasRole(UserRoles::Customer->value), 403);

        $sessionId = $request->query('session_id');
        $paymentId = $request->query('payment_id');
        $paymentQuery = Payment::query()
            ->with('shipment')
            ->where('user_id', $request->user()->id);

        if (is_string($sessionId) && $sessionId !== '') {
            $paymentQuery->where('stripe_session_id', $sessionId);
        } elseif (is_string($paymentId) && ctype_digit($paymentId)) {
            $paymentQuery->whereKey((int) $paymentId);
        } else {
            $paymentQuery->whereRaw('1 = 0');
        }

        $payment = $paymentQuery->first();

        return view('payments.result', [
            'payment' => $payment,
            'cancelled' => $request->boolean('cancelled'),
        ]);
    }

    public function confirm(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRoles::Customer->value), 403);

        $status = $payments->confirmCheckout($request->user(), $payment);

        return redirect()
            ->route('payments.result', ['payment_id' => $payment->id])
            ->with('payment_status', $status->value);
    }

    public function cancel(Request $request, Payment $payment, PaymentService $payments): RedirectResponse
    {
        abort_unless($request->user()->hasRole(UserRoles::Customer->value), 403);

        $status = $payments->cancelCheckout($request->user(), $payment);

        return redirect()
            ->route('payments.result', ['payment_id' => $payment->id])
            ->with('payment_status', $status->value);
    }
}