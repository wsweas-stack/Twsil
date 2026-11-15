@php
    $fullPage = $fullPage ?? false;
@endphp

<aside
    class="ai-assistant{{ $fullPage ? ' ai-assistant-page' : '' }}"
    data-ai-assistant
    data-full-page="{{ $fullPage ? 'true' : 'false' }}"
    aria-label="{{ __('app.assistant.title') }}"
    data-csrf-token="{{ csrf_token() }}"
    data-endpoint="{{ route('assistant.chat', [], false) }}"
    data-loading-message="{{ __('app.assistant.loading') }}"
    data-error-message="{{ __('app.assistant.error') }}"
    data-unavailable-message="{{ __('app.assistant.unavailable') }}"
    data-not-configured-message="{{ __('app.assistant.not_configured') }}"
>
    @unless ($fullPage)
        <button class="ai-assistant-launch" type="button" data-assistant-open aria-expanded="false" aria-controls="ai-assistant-panel">
            <span class="ai-assistant-launch-mark" aria-hidden="true">T</span>
            <span class="ai-assistant-launch-text">{{ __('app.assistant.open') }}</span>
        </button>
    @endunless
    <section class="ai-assistant-panel" id="ai-assistant-panel" data-assistant-panel @unless ($fullPage) hidden @endunless aria-label="{{ __('app.assistant.title') }}">
        <header class="ai-assistant-header">
            <div class="ai-assistant-brand">
                <span class="ai-assistant-brand-mark" aria-hidden="true">T</span>
                <div class="ai-assistant-brand-copy">
                    <strong>{{ __('app.assistant.title') }}</strong>
                    <span>{{ __('app.assistant.subtitle') }}</span>
                </div>
            </div>
            <div class="ai-assistant-tools">
                @unless ($fullPage)
                    <button class="ai-assistant-minimize" type="button" data-assistant-minimize aria-label="{{ __('app.assistant.minimize', ['default' => 'Minimize']) }}">–</button>
                    <button class="ai-assistant-close" type="button" data-assistant-close aria-label="{{ __('app.assistant.close') }}">×</button>
                @endunless
            </div>
        </header>
        <div class="ai-assistant-messages" data-assistant-messages role="log" aria-live="polite" aria-relevant="additions text">
            <p class="ai-assistant-message assistant-message">{{ __('app.assistant.welcome') }}</p>
        </div>
        <div class="ai-assistant-suggestions" aria-label="{{ __('app.assistant.suggestions') }}">
            @foreach (__('app.pages.chatbot_options') as $key => $option)
                @if (! auth()->check() || $key !== 'account')
                    <button class="ai-assistant-suggestion" type="button" data-assistant-suggestion="{{ $option['question'] }}">{{ $option['question'] }}</button>
                @endif
            @endforeach
        </div>
        <form class="ai-assistant-form" data-assistant-form>
            <label class="sr-only" for="ai-assistant-input">{{ __('app.assistant.message_label') }}</label>
            <textarea id="ai-assistant-input" name="message" rows="2" maxlength="2000" placeholder="{{ __('app.assistant.placeholder') }}" required data-assistant-input></textarea>
            <p class="ai-assistant-error" data-assistant-error role="alert" hidden></p>
            <button class="ai-assistant-send" type="submit" data-assistant-send>{{ __('app.assistant.send') }}</button>
        </form>
    </section>
</aside>
