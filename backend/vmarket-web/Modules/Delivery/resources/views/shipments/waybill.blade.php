<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Waybill Manifest #{{ $batch->batch_no }} — Victorious MARKET Logistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #fff; color: #000; padding: 20px; }
        .waybill-card { max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 20px; }
        .barcode { font-size: 28px; font-weight: bold; letter-spacing: 4px; font-family: monospace; border: 1px dashed #000; padding: 10px; display: inline-block; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .waybill-card { border: 2px solid #000; width: 100%; }
        }
    </style>
</head>
<body>

<div class="no-print text-center mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">
        🖨️ Print Official Manifest & Waybill
    </button>
    <a href="{{ route('delivery.shipments.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
        Back to Shipments
    </a>
</div>

<div class="waybill-card">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div>
            <h4 class="fw-bold mb-0">VICTORIOUS MARKET LOGISTICS</h4>
            <div class="small">Regional Inter-Hub Linehaul Manifest</div>
        </div>
        <div class="text-end">
            <div class="barcode">*{{ $batch->batch_no }}*</div>
            <div class="small font-monospace">BATCH: {{ $batch->batch_no }}</div>
        </div>
    </div>

    <div class="row g-3 border-bottom pb-3 mb-3">
        <div class="col-6">
            <strong>ORIGIN HUB:</strong><br>
            {{ $batch->originHub->name ?? 'Origin' }}<br>
            <span class="small">{{ $batch->originHub->city->name ?? '' }}, {{ $batch->originHub->city->state->name ?? 'Akwa Ibom' }}</span>
        </div>
        <div class="col-6 text-end">
            <strong>DESTINATION HUB:</strong><br>
            {{ $batch->destinationHub->name ?? 'Destination' }}<br>
            <span class="small">{{ $batch->destinationHub->city->name ?? '' }}, {{ $batch->destinationHub->city->state->name ?? 'Akwa Ibom' }}</span>
        </div>
    </div>

    <div class="row g-3 border-bottom pb-3 mb-3 small">
        <div class="col-4">
            <strong>DRIVER / COURIER:</strong><br>
            {{ $batch->driver ? ($batch->driver->f_name . ' ' . $batch->driver->l_name) : 'Linehaul Driver' }}
        </div>
        <div class="col-4">
            <strong>VEHICLE REG NO:</strong><br>
            {{ $batch->vehicle_no ?? 'N/A' }}
        </div>
        <div class="col-4 text-end">
            <strong>HANDSHAKE TRANSIT OTP:</strong><br>
            <span class="fs-5 fw-bold font-monospace bg-light p-1 border border-dark">{{ $batch->transit_otp ?? '------' }}</span>
        </div>
    </div>

    <h6 class="fw-bold mb-2">CONSOLIDATED PACKAGE MANIFEST ({{ $batch->orders->count() }} Orders):</h6>
    <table class="table table-bordered table-sm small align-middle mb-3">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Order ID</th>
                <th>Customer Name & Phone</th>
                <th>Delivery Address / Landmark</th>
                <th>Vendor Shop</th>
                <th>Amount Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batch->orders as $idx => $order)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="font-monospace fw-bold">#{{ $order->id }}</td>
                    <td>{{ $order->recipient_name ?? ($order->customer->f_name ?? 'Customer') }} ({{ $order->recipient_phone ?? ($order->customer->phone ?? '') }})</td>
                    <td>{{ $order->house_street_note ?? ($order->shipping_address ?? 'Uyo') }}</td>
                    <td>{{ $order->seller->shop->name ?? 'Vmarket' }}</td>
                    <td class="fw-bold">
                        @if($order->payment_status == 'paid')
                            [PAID]
                        @else
                            ₦{{ number_format($order->order_amount, 2) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row mt-4 pt-3 border-top small">
        <div class="col-6">
            <div>Dispatched By: __________________________</div>
            <div class="mt-1">Date: {{ $batch->dispatched_at ? $batch->dispatched_at->format('Y-m-d H:i') : date('Y-m-d H:i') }}</div>
        </div>
        <div class="col-6 text-end">
            <div>Received At Destination Hub: __________________________</div>
            <div class="mt-1">Receiver Signature / Stamp: __________________</div>
        </div>
    </div>
</div>

</body>
</html>
