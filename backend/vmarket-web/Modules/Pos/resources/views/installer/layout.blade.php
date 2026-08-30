<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Install') – Vmarket POS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: #e2e8f0;
        }

        .installer-wrap {
            width: 100%;
            max-width: 640px;
        }

        /* Header */
        .installer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        /* Steps */
        .steps {
            display: flex;
            align-items: center;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: .75rem 1rem;
            margin-bottom: 1.5rem;
            overflow-x: auto;
        }

        .step {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .8rem;
            color: #64748b;
            white-space: nowrap;
        }

        .step.active { color: #818cf8; font-weight: 600; }
        .step.done   { color: #34d399; }

        .step-num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .75rem;
            font-weight: 700;
            background: #334155;
            color: #94a3b8;
        }

        .step.active .step-num { background: #6366f1; color: #fff; }
        .step.done   .step-num { background: #059669; color: #fff; }

        .step-sep {
            flex: 1;
            height: 1px;
            background: #334155;
            margin: 0 .5rem;
            min-width: 12px;
        }

        /* Card */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,.5);
        }

        .card-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: .4rem;
        }

        .card-subtitle {
            font-size: .875rem;
            color: #94a3b8;
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: .4rem;
        }

        .form-hint {
            font-size: .75rem;
            color: #64748b;
            margin-top: .3rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        select {
            width: 100%;
            padding: .65rem .9rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #f8fafc;
            font-size: .875rem;
            outline: none;
            transition: border-color .15s;
        }

        input:focus, select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .85rem 1.75rem; border-radius: 10px; font-size: .95rem;
            font-weight: 600; cursor: pointer; border: none; text-decoration: none;
            transition: transform .15s, box-shadow .15s, opacity .15s;
        }
        .btn:active { transform: scale(.98); }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #fff;
            box-shadow: 0 4px 20px rgba(59,130,246,.3);
        }
        .btn-primary:hover { opacity: .9; box-shadow: 0 6px 24px rgba(59,130,246,.4); }
        .btn-secondary {
            background: rgba(51,65,85,.5); color: #94a3b8;
            border: 1px solid #334155;
        }
        .btn-full { width: 100%; }

        .btn-row { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .btn-row .btn { flex: 1; }

        /* Requirement rows */
        .req-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: .75rem 0; border-bottom: 1px solid #1e293b;
        }
        .req-item:last-child { border-bottom: none; }
        .req-name { font-size: .9rem; color: #e2e8f0; }
        .badge { padding: .25rem .65rem; border-radius: 6px; font-size: .75rem; font-weight: 600; }
        .badge-ok   { background: rgba(34,197,94,.15); color: #4ade80; }
        .badge-fail { background: rgba(248,113,113,.15); color: #f87171; }

        /* Progress bar */
        .progress-wrap { margin: 1.5rem 0; }
        .progress-label {
            display: flex; justify-content: space-between;
            font-size: .8rem; color: #94a3b8; margin-bottom: .5rem;
        }
        .progress-bar {
            width: 100%; height: 8px; background: #1e293b; border-radius: 99px; overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 99px;
            transition: width .5s ease;
        }

        /* Success icon */
        .success-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; margin: 0 auto 1.5rem;
            box-shadow: 0 0 40px rgba(34,197,94,.3);
        }
        .text-center { text-align: center; }
        .mt-1 { margin-top: .5rem; }
        .mt-2 { margin-top: 1rem; }

        /* Spinner */
        .spinner {
            width: 40px; height: 40px; border: 4px solid #334155;
            border-top-color: #3b82f6; border-radius: 50%;
            animation: spin 1s linear infinite; margin: 0 auto 1rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .log-box {
            background: #0f172a; border: 1px solid #1e293b; border-radius: 10px;
            padding: 1rem; font-family: monospace; font-size: .8rem;
            color: #94a3b8; max-height: 160px; overflow-y: auto; margin-top: 1rem;
        }
    </style>
</head>
<body>
<div class="installer-wrap">

    {{-- Header --}}
    <div class="installer-header">
        <div class="logo">
            <div class="logo-icon">📦</div>
            <div>
                <div style="font-weight:700;font-size:1.1rem;color:#f8fafc;">Vmarket POS</div>
                <div style="font-size:.75rem;color:#64748b;">Installation Wizard</div>
            </div>
        </div>
    </div>

    {{-- Step Indicator --}}
    @php
        $steps = ['Welcome','Requirements','Database','Admin','Install','Done'];
        $currentStep = $currentStep ?? 1;
    @endphp
    <div class="steps">
        @foreach($steps as $i => $label)
            @php $n = $i + 1; @endphp
            @if($i > 0)<div class="step-sep"></div>@endif
            <div class="step {{ $n < $currentStep ? 'done' : ($n == $currentStep ? 'active' : '') }}">
                <div class="step-num">{{ $n < $currentStep ? '✓' : $n }}</div>
                <span>{{ $label }}</span>
            </div>
        @endforeach
    </div>

    {{-- Card content --}}
    <div class="card">
        @yield('content')
    </div>

</div>
</body>
</html>
