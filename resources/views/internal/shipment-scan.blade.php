@extends('layouts.public')

@section('title', __('app.shipments.scan_title'))

@section('content')
    <div class="account-shell" style="max-width: 760px; margin: 0 auto;">
        <header class="account-heading" style="margin-bottom: 24px;">
            <span class="eyebrow">{{ __('app.auth.staff_area') }}</span>
            <h1 style="font-size: 26px; margin-top: 6px;">{{ __('app.shipments.scan_title') }}</h1>
            <p style="color: #64748b; margin-top: 4px;">{{ __('app.shipments.scan_intro') }}</p>
        </header>

        @if ($errors->any())
            <div class="status-message error" role="alert" style="margin-bottom: 20px;">
                <strong>{{ __('app.shipments.scan_error') }}</strong>
                @foreach ($errors->all() as $error)
                    <p style="margin: 4px 0 0;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div class="status-message" role="status" style="margin-bottom: 20px;">
                {{ session('status') }}
            </div>
        @endif

        <div class="account-content" style="padding: 28px; border-radius: 10px; border: 1px solid #e2e8f0; background: #ffffff;">
            <!-- Dropzone for QR image or paste -->
            <div id="qr-dropzone" style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 32px 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: border-color 0.2s ease, background-color 0.2s ease;">
                <div style="width: 48px; height: 48px; margin: 0 auto 12px; background: #fee2e2; border-radius: 8px; display: grid; place-items: center; color: #d71920;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                </div>
                <strong style="display: block; font-size: 15px; color: #0f172a; margin-bottom: 6px;">اختر صورة رمز QR أو اسحبها هنا</strong>
                <p style="color: #64748b; font-size: 13px; margin: 0 0 14px;">يمكنك أيضاً التقاط لقطة شاشة للرمز من ملف الـ PDF والضغط على <kbd style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-family: monospace;">Ctrl + V</kbd> للصقها مباشرة.</p>
                <input type="file" id="qr-file-input" accept="image/*" capture="environment" style="display: none;">
                <button type="button" class="button-primary" style="width: auto; padding: 8px 22px; font-size: 13px;" onclick="document.getElementById('qr-file-input').click()">تحديد ملف الصورة</button>
            </div>

            <div id="qr-decode-status" style="margin-top: 14px; font-size: 13px; text-align: center; color: #64748b; min-height: 20px;"></div>

            <!-- Form -->
            <form method="post" action="{{ route('shipments.scan') }}" id="qr-scan-form" style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
                @csrf
                <div class="form-field">
                    <label for="token" style="font-weight: 600; font-size: 13px; color: #0f172a;">{{ __('app.shipments.qr_token') }}</label>
                    <div style="display: flex; gap: 8px; margin-top: 6px;">
                        <input class="form-control" id="token" name="token" value="{{ old('token', request('token')) }}" minlength="64" maxlength="64" autocomplete="off" placeholder="رمز الاستلام المكون من 64 خانة..." required style="font-family: monospace; font-size: 13px;">
                        <button type="button" class="button-secondary" style="width: auto; white-space: nowrap; padding: 0 16px; font-size: 13px;" onclick="pasteTokenFromClipboard()">لصق</button>
                    </div>
                    @error('token') <p class="field-error" style="color: #dc2626; margin-top: 4px;">{{ $message }}</p> @enderror
                </div>
                <button class="button-primary" id="submit-scan-btn" style="width: 100%; margin-top: 14px;" type="submit">
                    {{ __('app.shipments.scan_submit') }}
                </button>
            </form>

            <!-- Quick Selection for Assigned Shipments -->
            @php
                $assignedShipments = \App\Models\Shipment::query()
                    ->where('status', \App\Enums\ShipmentStatus::Assigned)
                    ->with('qrCode')
                    ->latest()
                    ->limit(5)
                    ->get();
            @endphp

            @if ($assignedShipments->isNotEmpty())
                <div style="margin-top: 28px; padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <span style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 10px;">شحنات مسندة جاهزة للاستلام:</span>
                    <div style="display: grid; gap: 8px;">
                        @foreach ($assignedShipments as $s)
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <div>
                                    <strong style="color: #d71920; font-family: monospace; font-size: 14px;">{{ $s->tracking_number }}</strong>
                                    <span style="color: #64748b; font-size: 13px; margin-inline-start: 8px;">{{ $s->receiver_name }} ({{ $s->receiver_city }})</span>
                                </div>
                                @if ($s->qrCode?->token)
                                    <button type="button" class="button-secondary" style="width: auto; padding: 6px 14px; font-size: 12px;" onclick="fillAndSubmitToken('{{ $s->qrCode->token }}')">
                                        تأكيد الاستلام
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- jsQR Fast Standalone QR Decoder -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

    <script>
        function fillAndSubmitToken(token) {
            const tokenInput = document.getElementById('token');
            tokenInput.value = token.trim();
            document.getElementById('qr-scan-form').submit();
        }

        async function pasteTokenFromClipboard() {
            try {
                const text = await navigator.clipboard.readText();
                if (text) {
                    processDecodedText(text);
                }
            } catch {
                alert('يرجى لصق الرمز يدوياً داخل الحقل');
            }
        }

        function processDecodedText(text) {
            const raw = text.trim();
            let token = raw;

            if (raw.includes('token=')) {
                try {
                    const url = new URL(raw, window.location.origin);
                    const t = url.searchParams.get('token');
                    if (t) token = t;
                } catch {
                    const match = raw.match(/token=([A-Za-z0-9]+)/);
                    if (match) token = match[1];
                }
            }

            const tokenInput = document.getElementById('token');
            tokenInput.value = token;

            const decodeStatus = document.getElementById('qr-decode-status');
            if (decodeStatus) {
                decodeStatus.innerHTML = '<span style="color: #15803d; font-weight: 600;">تم استخراج الرمز بنجاح. جاري تأكيد الاستلام...</span>';
            }

            setTimeout(() => {
                document.getElementById('qr-scan-form').submit();
            }, 300);
        }

        function decodeImageFile(file) {
            const decodeStatus = document.getElementById('qr-decode-status');
            decodeStatus.innerHTML = '<span style="color: #2563eb;">جاري فحص الصورة وقراءة الرمز...</span>';

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    if (window.jsQR) {
                        const code = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: "dontInvert"
                        }) || jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: "onlyInvert"
                        });

                        if (code && code.data) {
                            processDecodedText(code.data);
                            return;
                        }
                    }

                    decodeStatus.innerHTML = '<span style="color: #dc2626;">تعذر التعرف على رمز QR صالح في الصورة. يرجى التأكد من وضوح الرمز أو نسخ رمز الاستلام ولصقه في الحقل أدناه.</span>';
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        document.getElementById('qr-file-input').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                decodeImageFile(e.target.files[0]);
            }
        });

        const dropzone = document.getElementById('qr-dropzone');
        ['dragenter', 'dragover'].forEach(name => {
            dropzone.addEventListener(name, (e) => {
                e.preventDefault();
                dropzone.style.borderColor = '#d71920';
                dropzone.style.background = '#fef2f2';
            });
        });
        ['dragleave', 'drop'].forEach(name => {
            dropzone.addEventListener(name, (e) => {
                e.preventDefault();
                dropzone.style.borderColor = '#cbd5e1';
                dropzone.style.background = '#f8fafc';
            });
        });
        dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                decodeImageFile(e.dataTransfer.files[0]);
            }
        });

        window.addEventListener('paste', (e) => {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (let item of items) {
                if (item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    decodeImageFile(blob);
                    break;
                } else if (item.type === 'text/plain') {
                    item.getAsString((text) => {
                        if (text.length >= 64) {
                            processDecodedText(text);
                        }
                    });
                }
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const urlToken = urlParams.get('token');
            if (urlToken && urlToken.length === 64) {
                document.getElementById('token').value = urlToken;
                document.getElementById('qr-scan-form').submit();
            }
        });
    </script>
@endsection
