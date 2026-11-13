document.querySelectorAll('[data-ai-assistant]').forEach((assistant) => {
    const panel = assistant.querySelector('[data-assistant-panel]');
    const launchButton = assistant.querySelector('[data-assistant-open]');
    const closeButton = assistant.querySelector('[data-assistant-close]');
    const form = assistant.querySelector('[data-assistant-form]');
    const input = assistant.querySelector('[data-assistant-input]');
    const sendButton = assistant.querySelector('[data-assistant-send]');
    const messages = assistant.querySelector('[data-assistant-messages]');
    const error = assistant.querySelector('[data-assistant-error]');
    const fullPage = assistant.dataset.fullPage === 'true';

    const open = () => {
        panel.hidden = false;
        document.querySelectorAll('[data-assistant-open]').forEach((button) => {
            button.setAttribute('aria-expanded', 'true');
        });
        input.focus();
    };

    const close = () => {
        panel.hidden = true;
        document.querySelectorAll('[data-assistant-open]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
        });
        launchButton?.focus();
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

    closeButton?.addEventListener('click', close);

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    panel.addEventListener('keydown', (event) => {
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
            input.focus();
        }
    });
});
