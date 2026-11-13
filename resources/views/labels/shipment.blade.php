<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>Shipment Label - {{ $shipment->tracking_number }}</title>
    <style>
        @page { margin: 8mm; }
        body { color: #1e293b; font-family: DejaVu Sans, sans-serif; font-size: 10px; line-height: 1.4; }
        .header { border-bottom: 2px solid #d71920; padding-bottom: 6px; margin-bottom: 8px; }
        .brand { color: #d71920; font-size: 14px; font-weight: bold; letter-spacing: 0.5px; }
        .subtitle { color: #64748b; font-size: 8px; text-transform: uppercase; margin-top: 2px; }
        .tracking-box { background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px; margin: 8px 0; text-align: center; border-radius: 4px; }
        .tracking-title { color: #64748b; font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
        .tracking { font-size: 20px; font-weight: bold; letter-spacing: 1.5px; color: #0f172a; font-family: monospace; }
        .row { display: table; width: 100%; margin-bottom: 8px; }
        .column { display: table-cell; width: 50%; vertical-align: top; padding: 4px; }
        .card { background: #ffffff; border: 1px solid #e2e8f0; padding: 6px 8px; border-radius: 4px; }
        .label { color: #d71920; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; border-bottom: 1px solid #f1f5f9; padding-bottom: 2px; }
        .value { font-size: 9px; color: #334155; }
        .destination-box { margin: 8px 0; padding: 8px; background: #0f172a; color: #ffffff; text-align: center; font-size: 16px; font-weight: bold; border-radius: 4px; letter-spacing: 1px; }
        .qr-section { text-align: center; margin: 8px 0 4px; }
        .qr { display: block; width: 38mm; height: 38mm; margin: 0 auto; border: 1px solid #e2e8f0; padding: 2px; background: #ffffff; }
        .token-box { margin-top: 6px; padding: 4px; background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 3px; text-align: center; }
        .token-label { font-size: 7px; color: #64748b; font-weight: bold; text-transform: uppercase; }
        .token-value { font-family: monospace; font-size: 7px; color: #0f172a; word-break: break-all; margin-top: 2px; }
        .footer { margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 6px; color: #64748b; text-align: center; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">TAWSILI LOGISTICS · توصيلي</div>
        <div class="subtitle">Official Shipment & Delivery Label · ملصق الشحنة الرسمي</div>
    </div>

    <div class="tracking-box">
        <div class="tracking-title">Tracking Number / رقم التتبع</div>
        <div class="tracking">{{ $shipment->tracking_number }}</div>
    </div>

    <div class="row">
        <div class="column">
            <div class="card">
                <div class="label">Sender / المرسل</div>
                <div class="value">
                    <strong>{{ $shipment->sender_name }}</strong><br>
                    {{ $shipment->sender_phone }}<br>
                    {{ $shipment->sender_city }} - {{ $shipment->sender_address }}
                </div>
            </div>
        </div>
        <div class="column">
            <div class="card">
                <div class="label">Receiver / المستلم</div>
                <div class="value">
                    <strong>{{ $shipment->receiver_name }}</strong><br>
                    {{ $shipment->receiver_phone }}<br>
                    {{ $shipment->receiver_city }} - {{ $shipment->receiver_address }}
                </div>
            </div>
        </div>
    </div>

    <div class="destination-box">
        DESTINATION: {{ strtoupper($shipment->receiver_city) }}
    </div>

    <div class="qr-section">
        <img class="qr" src="{{ $qrDataUri }}" alt="Shipment QR Code">
        @if ($shipment->qrCode?->token)
            <div class="token-box">
                <span class="token-label">Pickup Token (رمز تأكيد الاستلام):</span><br>
                <span class="token-value">{{ $shipment->qrCode->token }}</span>
            </div>
        @endif
    </div>

    <div class="footer">
        {{ $shipment->description }} | Handle with Care | {{ now()->format('Y-m-d') }}
    </div>
</body>
</html>
