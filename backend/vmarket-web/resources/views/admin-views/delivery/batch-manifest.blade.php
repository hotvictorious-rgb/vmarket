<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('Dispatch Manifest') }} - {{ $batchId }}</title>
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            padding: 24px;
        }
        .manifest-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .corridor-header {
            background: #0f172a;
            color: #ffffff;
            padding: 16px 24px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table td {
            vertical-align: middle;
            font-size: 13px;
        }
        .checkbox-cell {
            width: 32px;
            height: 32px;
            border: 2px solid #cbd5e1;
            border-radius: 4px;
            display: inline-block;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .manifest-container {
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

<div class="manifest-container">
    <!-- Action Bar (No Print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary font-weight-bold px-4">
            <i class="tio-print mr-1"></i> {{ translate('Print Dispatch Manifest') }}
        </button>
        <button onclick="window.close()" class="btn btn-secondary px-3">
            {{ translate('Close') }}
        </button>
    </div>

    <!-- Header Section -->
    <div class="row align-items-center mb-3 pb-3 border-bottom">
        <div class="col-6">
            <h3 class="font-weight-bold text-primary mb-1">{{ $companyName }}</h3>
            <h5 class="font-weight-bold text-dark mb-0">{{ translate('CORRIDOR BATCH DISPATCH MANIFEST') }}</h5>
            <small class="text-muted">{{ translate('Central Logistics & Rider Route Sheet') }}</small>
        </div>
        <div class="col-6 text-right">
            <div class="font-weight-bold fs-15 text-dark">{{ translate('Batch ID:') }} {{ $batchId }}</div>
            <div class="text-muted fs-12">{{ date('d M, Y - h:i A') }}</div>
            <span class="badge badge-success mt-1">{{ count($orders) }} {{ translate('PARCELS') }}</span>
        </div>
    </div>

    <!-- Corridor & Rider Information -->
    <div class="corridor-header">
        <div class="row align-items-center">
            <div class="col-md-7">
                <div class="fs-12 text-uppercase text-muted" style="color: #94a3b8 !important;">{{ translate('Corridor Vector') }}</div>
                <div class="fs-18 font-weight-bold">
                    {{ $originName }} &nbsp;➔&nbsp; {{ $destName }} ({{ $destCity }})
                </div>
            </div>
            <div class="col-md-5 text-md-right mt-2 mt-md-0">
                <div class="fs-12 text-uppercase text-muted" style="color: #94a3b8 !important;">{{ translate('Assigned Delivery Rider') }}</div>
                <div class="fs-16 font-weight-bold text-warning">
                    @if($deliveryMan)
                        <i class="tio-user mr-1"></i> {{ $deliveryMan->f_name }} {{ $deliveryMan->l_name }} ({{ $deliveryMan->phone }})
                    @else
                        <span class="badge badge-warning">{{ translate('Unassigned / Pool') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th style="width: 100px;">{{ translate('Order ID') }}</th>
                    <th>{{ translate('Customer & Delivery Note') }}</th>
                    <th>{{ translate('Package Contents') }}</th>
                    <th class="text-center" style="width: 110px;">{{ translate('Payment') }}</th>
                    <th class="text-right" style="width: 110px;">{{ translate('Rider Fee') }}</th>
                    <th class="text-center" style="width: 70px;">{{ translate('OTP') }}</th>
                </tr>
            </thead>
            <tbody>
                @php($totalRiderFee = 0)
                @foreach($orders as $key => $ord)
                    @php
                        $riderCharge = $ord->deliveryman_charge ?? ($ord->destinationHub->rider_delivery_fee ?? 500);
                        $totalRiderFee += $riderCharge;
                    @endphp
                    <tr>
                        <td class="text-center font-weight-bold">{{ $key + 1 }}</td>
                        <td class="font-weight-bold text-primary">#{{ $ord->id }}</td>
                        <td>
                            <div class="font-weight-bold">
                                {{ $ord->recipient_name ?? ($ord->customer ? ($ord->customer->f_name . ' ' . $ord->customer->l_name) : 'Customer') }}
                            </div>
                            @if(!empty($ord->house_street_note))
                                <small class="text-muted d-block"><i class="tio-poi"></i> {{ $ord->house_street_note }}</small>
                            @endif
                        </td>
                        <td>
                            <div class="fs-12">
                                @foreach($ord->details->take(2) as $det)
                                    @php($p = json_decode($det->product_details, true))
                                    <div>• {{ $det->qty }}x {{ Str::limit($p['name'] ?? 'Item', 24) }}</div>
                                @endforeach
                                @if($ord->details->count() > 2)
                                    <small class="text-muted">+{{ $ord->details->count() - 2 }} {{ translate('more items') }}</small>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $ord->payment_status == 'paid' ? 'badge-soft-success' : 'badge-soft-danger' }}">
                                {{ strtoupper($ord->payment_status ?? 'UNPAID') }}
                            </span>
                            <div class="fs-11 text-muted">{{ strtoupper(str_replace('_', ' ', $ord->payment_method ?? 'COD')) }}</div>
                        </td>
                        <td class="text-right font-weight-bold text-success">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $riderCharge), currencyCode: getCurrencyCode()) }}
                        </td>
                        <td class="text-center">
                            <div class="checkbox-cell"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-light">
                    <td colspan="5" class="text-right font-weight-bold">{{ translate('Total Rider Batch Earnings:') }}</td>
                    <td class="text-right font-weight-bold text-success fs-15">
                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $totalRiderFee), currencyCode: getCurrencyCode()) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Handover Signatures -->
    <div class="row pt-4 border-top text-muted fs-12 mt-4">
        <div class="col-6">
            <div class="text-muted mb-4">{{ translate('I have inspected and collected all listed parcels in good condition:') }}</div>
            <div style="border-bottom: 1px solid #cbd5e1; height: 30px; width: 80%;"></div>
            <span class="font-weight-bold text-dark">{{ translate('Rider Signature & Date') }}</span>
        </div>
        <div class="col-6 text-right">
            <div class="text-muted mb-4">{{ translate('Authorized Hub Dispatch Officer:') }}</div>
            <div style="border-bottom: 1px solid #cbd5e1; height: 30px; width: 80%; margin-left: auto;"></div>
            <span class="font-weight-bold text-dark">{{ translate('Hub Officer Signature & Stamp') }}</span>
        </div>
    </div>
</div>

</body>
</html>
