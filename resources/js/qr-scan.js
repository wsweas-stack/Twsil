import { BrowserMultiFormatReader } from '@zxing/browser';

const video = document.getElementById('qr-video');

if (video instanceof HTMLVideoElement) {
    const form = video.closest('.account-content').querySelector('form');
    const tokenInput = document.getElementById('token');
    const startButton = document.getElementById('start-qr-scan');
    const stopButton = document.getElementById('stop-qr-scan');
    const status = document.getElementById('qr-scan-status');
    const reader = new BrowserMultiFormatReader();
    let controls = null;

    function stopScanner() {
        controls?.stop();
        controls = null;
        startButton.disabled = false;
        stopButton.disabled = true;
    }

    startButton.addEventListener('click', async () => {
        startButton.disabled = true;
        status.textContent = startButton.dataset.starting;

        try {
            controls = await reader.decodeFromVideoDevice(undefined, video, (result) => {
                if (!result) return;

                tokenInput.value = result.getText().trim();
                status.textContent = startButton.dataset.found;
                stopScanner();
                form.requestSubmit();
            });
            stopButton.disabled = false;
            status.textContent = startButton.dataset.active;
        } catch {
            stopScanner();
            status.textContent = startButton.dataset.error;
        }
    });

    stopButton.addEventListener('click', () => {
        stopScanner();
        status.textContent = startButton.dataset.idle;
    });
}