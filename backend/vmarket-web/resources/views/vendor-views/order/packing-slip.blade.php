<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Packing Slip') }} - #{{ $order->id }}</title>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            padding: 20px;
        }
        .slip-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .corridor-badge {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .otp-box {
            border: 2px dashed #4f46e5;
            background-color: #f5f3ff;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .otp-code {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 6px;
            color: #4f46e5;
        }
        .table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .privacy-notice {
            background-color: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 12px;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .slip-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="slip-container">
    <!-- Action Bar (No Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary font-weight-bold px-4">
            <i class="tio-print mr-1"></i> {{ translate('Print Packing Slip') }}
        </button>
        <button onclick="window.close()" class="btn btn-secondary px-3">
            {{ translate('Close') }}
        </button>
    </div>

    <!-- Header Section -->
    <div class="row align-items-center mb-4 pb-3 border-bottom">
        <div class="col-6">
            <h3 class="font-weight-bold mb-1 text-primary">{{ $companyName ?? 'Victorious MARKET' }}</h3>
            <p class="text-muted mb-0 fs-13 font-weight-bold">{{ translate('VENDOR FULFILLMENT & PACKING MANIFEST') }}</p>
            <p class="text-muted mb-0 fs-12">{{ translate('Shop:') }} {{ $order->seller->shop->name ?? 'Vendor Merchant' }}</p>
        </div>
        <div class="col-6 text-right">
            <h4 class="font-weight-bold mb-0">#{{ $order->id }}</h4>
            <p class="text-muted mb-0 fs-12">{{ date('d M, Y - h:i A', strtotime($order->created_at)) }}</p>
            <span class="badge badge-info mt-1">{{ translate('Order Status:') }} {{ strtoupper(str_replace('_', ' ', $order->order_status)) }}</span>
        </div>
    </div>

    <!-- Logistics Corridor & Handshake -->
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="corridor-badge w-100 text-center">
                <i class="tio-map mr-1"></i> 
                {{ $order->originHub?->name ?? ($order->seller?->shop?->deliveryHub?->name ?? 'Plaza / Central Sorting Point') }}
                &nbsp;➔&nbsp;
                {{ $order->destinationHub?->name ?? 'General Area Landmark' }}
                @if($order->destinationHub?->city)
                    ({{ $order->destinationHub->city->name }})
                @endif
            </div>
            <div class="privacy-notice">
                <i class="tio-lock mr-1"></i> <strong>{{ translate('Vendor-Buyer Isolation:') }}</strong>
                {{ translate('Buyer contact & payment details are managed securely by Vmarket Dispatch Logistics.') }}
            </div>
        </div>
        <div class="col-md-5">
            <div class="otp-box">
                <div class="text-muted fs-11 font-weight-bold text-uppercase mb-1">{{ translate('Rider Pickup Verification Code') }}</div>
                <div class="otp-code">{{ $order->pickup_verification_code ?? '----' }}</div>
                <small class="text-muted fs-11">{{ translate('Confirm this OTP with Vmarket Rider upon collection') }}</small>
            </div>
        </div>
    </div>

    <!-- Itemized List Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>{{ translate('Product / Item Name') }}</th>
                    <th>{{ translate('Variation / Specs') }}</th>
                    <th class="text-center" style="width: 80px;">{{ translate('Qty') }}</th>
                    <th class="text-right" style="width: 140px;">{{ translate('Desired Payout (₦)') }}</th>
                    <th class="text-right" style="width: 140px;">{{ translate('Total Payout (₦)') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalVendorPayout = 0;
                @endphp
                @foreach($order->details as $key => $detail)
                    @php
                        $product = json_decode($detail->product_details, true);
                        $variation = json_decode($detail->variation, true);
                        $qty = $detail->qty;
                        $unitPayout = $product['purchase_price'] ?? ($product['unit_price'] ?? 0);
                        $itemTotal = $unitPayout * $qty;
                        $totalVendorPayout += $itemTotal;
                    @endphp
                    <tr>
                        <td class="text-center font-weight-bold">{{ $key + 1 }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $product['name'] ?? 'Product' }}</div>
                            <small class="text-muted">{{ translate('SKU:') }} {{ $product['code'] ?? 'N/A' }}</small>
                        </td>
                        <td>
                            @if(!empty($detail->variant))
                                <span class="badge badge-light border font-weight-bold">{{ $detail->variant }}</span>
                            @else
                                <span class="text-muted">{{ translate('Standard') }}</span>
                            @endif
                        </td>
                        <td class="text-center font-weight-bold fs-15">{{ $qty }}</td>
                        <td class="text-right font-weight-bold text-dark">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $unitPayout), currencyCode: getCurrencyCode()) }}
                        </td>
                        <td class="text-right font-weight-bold text-success">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $itemTotal), currencyCode: getCurrencyCode()) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-light">
                    <td colspan="4" class="text-right font-weight-bold">{{ translate('Total Vendor Payout Expected:') }}</td>
                    <td colspan="2" class="text-right font-weight-bold text-success fs-16">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $totalVendorPayout), currencyCode: getCurrencyCode()) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Packaging Instructions & Signature -->
    <div class="row pt-3 border-top text-muted fs-12">
        <div class="col-8">
            <h6 class="font-weight-bold text-dark mb-1">{{ translate('Packaging Checklist & Instructions:') }}</h6>
            <ul class="pl-3 mb-0">
                <li>{{ translate('Inspect all items for size, color, and quality before sealing.') }}</li>
                <li>{{ translate('Slide this packing manifest inside the parcel.') }}</li>
                <li>{{ translate('Hand package over ONLY to the assigned Victorious MARKET dispatch rider.') }}</li>
            </ul>
        </div>
        <div class="col-4 text-center">
            <div style="border-bottom: 1px solid #cbd5e1; height: 40px; margin-bottom: 5px;"></div>
            <span class="font-weight-bold">{{ translate('Merchant Signature / Stamp') }}</span>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        // Auto-focus print dialog on open
        // window.print();
    };
</script>

</body>
</html>
