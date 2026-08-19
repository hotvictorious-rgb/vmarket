<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Waybill Label') }} - #{{ $order->id }}</title>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .label-card {
            width: 384px; /* Standard 4x6 inch (100x150mm) aspect */
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: 8px;
            padding: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .destination-block {
            background: #0f172a;
            color: #ffffff;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            margin: 10px 0;
        }
        .destination-title {
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
        }
        .destination-name {
            font-size: 17px;
            font-weight: 900;
            line-height: 1.2;
        }
        .barcode-simulation {
            font-family: 'Courier New', monospace;
            font-size: 26px;
            letter-spacing: 4px;
            font-weight: 900;
            text-align: center;
            background: #f8fafc;
            padding: 8px;
            border: 1px dashed #cbd5e1;
            margin: 8px 0;
        }
        .otp-badge {
            background: #f1f5f9;
            border: 1.5px dashed #64748b;
            padding: 6px;
            border-radius: 4px;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .label-card {
                border: 2px solid #000000;
                box-shadow: none;
                width: 100%;
                max-width: 4in;
                height: 6in;
                margin: 0;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div>
    <!-- Print Buttons -->
    <div class="text-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary font-weight-bold btn-sm px-4">
            <i class="tio-print mr-1"></i> {{ translate('Print Label') }}
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm px-3">
            {{ translate('Close') }}
        </button>
    </div>

    <div class="label-card">
        <!-- Brand & Order Header -->
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
            <div>
                <h6 class="font-weight-bold mb-0 text-primary">{{ $companyName }}</h6>
                <small class="text-muted font-weight-bold fs-10">{{ translate('PARCEL DISPATCH LABEL') }}</small>
            </div>
            <div class="text-right">
                <h5 class="font-weight-bold mb-0">#{{ $order->id }}</h5>
                <small class="text-muted fs-10">{{ date('d/m/Y - h:i A') }}</small>
            </div>
        </div>

        <!-- Simulated Barcode -->
        <div class="barcode-simulation">
            *VM-{{ $order->id }}-{{ strtoupper(substr(md5($order->id), 0, 4)) }}*
        </div>

        <!-- Destination Landmark / Hub -->
        <div class="destination-block">
            <div class="destination-title">{{ translate('Destination Corridor / Landmark') }}</div>
            <div class="destination-name">
                {{ $order->destinationHub?->name ?? 'General Landmark Corridor' }}
            </div>
            <div class="fs-11 font-weight-bold mt-1 text-warning">
                {{ $order->destinationHub?->city?->name ?? 'Uyo' }}, {{ $order->destinationHub?->city?->state?->name ?? 'Akwa Ibom' }}
            </div>
        </div>

        <!-- Recipient & Delivery Note -->
        <div class="border rounded p-2 mb-2 bg-light">
            <div class="fs-10 text-muted font-weight-bold text-uppercase">{{ translate('Deliver To:') }}</div>
            <div class="font-weight-bold fs-14">
                {{ $order->recipient_name ?? ($order->customer ? ($order->customer->f_name . ' ' . substr($order->customer->l_name, 0, 1) . '.') : 'Customer') }}
            </div>
            @if(!empty($order->house_street_note))
                <div class="fs-11 text-dark mt-1 font-weight-bold">
                    <i class="tio-poi"></i> {{ $order->house_street_note }}
                </div>
            @endif
        </div>

        <!-- Origin Info -->
        <div class="d-flex justify-content-between fs-11 text-muted border-bottom pb-2 mb-2">
            <div>
                <strong>{{ translate('Origin Hub:') }}</strong> 
                {{ $order->originHub?->name ?? ($order->seller?->shop?->deliveryHub?->name ?? 'Plaza Central Hub') }}
            </div>
            <div class="text-right">
                <strong>{{ translate('Channel:') }}</strong> 
                {{ $order->destinationHub?->type == 'motor_park' ? 'Park Waybill' : 'Landmark Dropoff' }}
            </div>
        </div>

        <!-- Verification & Handshake -->
        <div class="row g-2 mb-2">
            <div class="col-6">
                <div class="otp-badge">
                    {{ translate('PICKUP OTP') }}: <strong>{{ $order->pickup_verification_code ?? '----' }}</strong>
                </div>
            </div>
            <div class="col-6">
                <div class="otp-badge" style="border-color: #10b981; color: #065f46; background: #ecfdf5;">
                    <i class="tio-checkmark-circle"></i> {{ translate('OTP ON DELIVERY') }}
                </div>
            </div>
        </div>

        <!-- Security Disclaimer -->
        <div class="text-center text-muted fs-9 pt-1" style="font-size: 9px; line-height: 1.2;">
            🔒 <strong>{{ translate('Vmarket Security Seal:') }}</strong> {{ translate('Do not accept package if seal is torn or opened prior to OTP verification.') }}
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        // window.print();
    };
</script>

</body>
</html>
