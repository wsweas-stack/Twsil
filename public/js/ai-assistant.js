document.querySelectorAll('[data-ai-assistant]').forEach((assistant) => {
    const panel = assistant.querySelector('[data-assistant-panel]');
    const launchButton = assistant.querySelector('[data-assistant-open]');
    const minimizeButton = assistant.querySelector('[data-assistant-minimize]');
    const closeButton = assistant.querySelector('[data-assistant-close]');
    const form = assistant.querySelector('[data-assistant-form]');
    const input = assistant.querySelector('[data-assistant-input]');
    const sendButton = assistant.querySelector('[data-assistant-send]');
    const messages = assistant.querySelector('[data-assistant-messages]');
    const error = assistant.querySelector('[data-assistant-error]');
    const suggestions = assistant.querySelector('.ai-assistant-suggestions');
    const fullPage = assistant.dataset.fullPage === 'true';

    const syncSuggestions = () => {
        if (!suggestions) {
            return;
        }

        const hasConversation = messages.querySelectorAll('.user-message').length > 0;
        suggestions.hidden = hasConversation;
    };

    const open = () => {
        if (!panel) {
            return;
        }

        panel.hidden = false;
        panel.classList.remove('is-minimized');
        document.querySelectorAll('[data-assistant-open]').forEach((button) => {
            button.setAttribute('aria-expanded', 'true');
        });
        window.setTimeout(() => input?.focus(), 80);
    };

    const close = () => {
        if (!panel) {
            return;
        }

        panel.hidden = true;
        panel.classList.remove('is-minimized');
        document.querySelectorAll('[data-assistant-open]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });
        launchButton?.focus();
    };

    const minimize = () => {
        if (!panel) {
            return;
        }

        panel.classList.toggle('is-minimized');
        const minimized = panel.classList.contains('is-minimized');
        panel.hidden = minimized;
        document.querySelectorAll('[data-assistant-open]').forEach((button) => {
            button.setAttribute('aria-expanded', minimized ? 'false' : 'true');
        });

        if (!minimized) {
            window.setTimeout(() => input?.focus(), 80);
        } else {
            launchButton?.focus();
        }
    };

    const resizeInput = () => {
        if (!input) {
            return;
        }

        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 130) + 'px';
    };

    const addMessage = (content, role, pending = false) => {
        const message = document.createElement('p');
        message.className = `ai-assistant-message ${role === 'user' ? 'user-message' : 'assistant-message'}`;
        message.textContent = content;

        if (pending) {
            message.dataset.pending = 'true';
        }

        messages.append(message);
        messages.scrollTop = messages.scrollHeight;
        syncSuggestions();
        return message;
    };

    if (launchButton) {
        launchButton.addEventListener('click', () => {
            if (panel.hidden) {
                open();
            } else {
                close();
            }
        });
    }

    minimizeButton?.addEventListener('click', minimize);
    closeButton?.addEventListener('click', close);
    input?.addEventListener('input', resizeInput);

    input?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    panel?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !fullPage) {
            close();
        }
    });

    document.querySelectorAll('[data-assistant-back]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const previousPage = document.referrer ? new URL(document.referrer) : null;

            if (window.history.length > 1 && previousPage?.origin === window.location.origin) {
                event.preventDefault();
                window.history.back();
            }
        });
    });

    if (!fullPage) {
        document.querySelectorAll('[data-assistant-open]:not(.ai-assistant-launch)').forEach((button) => {
            button.addEventListener('click', open);
        });
    }

    assistant.querySelectorAll('[data-assistant-suggestion]').forEach((button) => {
        button.addEventListener('click', () => {
            input.value = button.dataset.assistantSuggestion;
            resizeInput();
            form.requestSubmit();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || sendButton.disabled) {
            return;
        }

        error.hidden = true;
        error.textContent = '';
        const sentMessage = addMessage(message, 'user');
        input.value = '';
        resizeInput();
        sendButton.disabled = true;
        input.disabled = true;
        assistant.querySelectorAll('[data-assistant-suggestion]').forEach((button) => {
            button.disabled = true;
        });

        const pending = addMessage(assistant.dataset.loadingMessage || '…', 'assistant', true);

        try {
            const response = await fetch(assistant.dataset.endpoint, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': assistant.dataset.csrfToken,
                },
                body: JSON.stringify({ message }),
            });

            const responseContentType = response.headers.get('content-type') || '';
            if (!responseContentType.includes('application/json')) {
                throw new Error(assistant.dataset.errorMessage);
            }

            let data;
            try {
                data = await response.json();
            } catch {
                throw new Error(assistant.dataset.errorMessage);
            }

            if (!response.ok) {
                throw new Error(data.code === 'assistant_not_configured'
                    ? assistant.dataset.notConfiguredMessage
                    : response.status === 503
                        ? assistant.dataset.unavailableMessage
                        : assistant.dataset.errorMessage);
            }

            if (typeof data.reply !== 'string' || !data.reply.trim()) {
                throw new Error(assistant.dataset.errorMessage);
            }

            pending.textContent = data.reply;
            delete pending.dataset.pending;
            messages.scrollTop = messages.scrollHeight;
        } catch (requestError) {
            sentMessage.remove();
            pending.remove();
            input.value = message;
            resizeInput();
            error.textContent = requestError instanceof TypeError
                ? assistant.dataset.unavailableMessage
                : requestError.message || assistant.dataset.errorMessage;
            error.hidden = false;
        } finally {
            sendButton.disabled = false;
            input.disabled = false;
            assistant.querySelectorAll('[data-assistant-suggestion]').forEach((button) => {
                button.disabled = false;
            });
            syncSuggestions();
            input.focus();
        }
    });

    syncSuggestions();
    resizeInput();
});
