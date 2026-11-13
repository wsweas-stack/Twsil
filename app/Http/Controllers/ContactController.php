<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactMessageMail;
use App\Models\SupportMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function submit(ContactMessageRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        SupportMessage::query()->create($validated);

        Mail::to(config('mail.contact.address'))->send(new ContactMessageMail(
            senderName: $validated['name'],
            senderEmail: $validated['email'],
            messageContent: $validated['message'],
        ));

        // Return the visitor to the page the form was submitted from (e.g. home#contact
        // or the standalone contact page) so the success alert shows next to the form.
        $back = preg_replace('/#.*$/', '', url()->previous()) ?: route('contact');

        return redirect()->to($back . '#contact')->with('status', __('app.pages.message_received'));
    }
}