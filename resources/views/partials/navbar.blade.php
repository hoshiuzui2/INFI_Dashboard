<!-- resources/views/partials/navbar.blade.php -->

<div class="dashboard-header">
    <div class="logo-area">
        <img src="{{ asset('images/logo.png') }}" alt="Company Logo">
        <div class="title-group">
            <h2>Executive Dashboard</h2>

            <!-- ✅ Dynamic Subtitle based on current route -->
            @php
                $routeName = Route::currentRouteName();
                $subtitle = 'Company Overview';

                $map = [
                    'dashboard' => 'Company Overview',
                    'sales' => 'Sales Analytics',
                    'sales.realtime' => 'Sales Analytics',
                    'ar.index' => 'Accounts Receivable',
                    'ar.realtime' => 'Accounts Receivable',
                    'ar.customer.details' => 'Accounts Receivable',
                    'ap.index' => 'Accounts Payable',
                    'ap.data'  => 'Accounts Payable',
                    'ap.customer.details' => 'Accounts Payable',
                ];

                if ($routeName && isset($map[$routeName])) {
                    $subtitle = $map[$routeName];
                } else {
                    $first = explode('/', request()->path())[0] ?? '';
                    if ($first) $subtitle = ucfirst($first);
                }
            @endphp

            <small>{{ $subtitle }} | {{ now()->format('F d, Y') }}</small>
        </div>
    </div>

    {{-- 🔴 LIVE CAMERA INDICATOR (Dashboard Only) --}}
    @if(request()->routeIs('dashboard') || request()->is('/'))
        <span class="live-indicator">
            <span class="live-dot"></span>
            LIVE
        </span>
    @endif
</div>

<!-- ================= CUSTOM COLORS + LIVE INDICATOR ================= -->
<style>
    /* Inventory = ORANGE */
    .bg-inventory { background-color: #fd7e14 !important; }
    .border-inventory { border-color: #fd7e14 !important; }
    .text-inventory { color: #fd7e14 !important; }

    /* Gross Profit = TEAL */
    .bg-teal { background-color: #0f766e !important; }
    .border-teal { border-color: #0f766e !important; }
    .text-teal { color: #0f766e !important; }
/* 🔴 LIVE CAMERA INDICATOR — TRANSPARENT */
.live-indicator {
    background: transparent;
    color: #000; /* black LIVE text */

    padding: 0;
    border-radius: 0;
    font-weight: 700;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: none;
}

    .live-dot {
        width: 10px;
        height: 10px;
        background: red;
        border-radius: 50%;
        animation: liveBlink 1.2s infinite;
    }

    @keyframes liveBlink {
        0%   { opacity: 1; }
        50%  { opacity: 0.3; }
        100% { opacity: 1; }
    }
</style>

<!-- ================= UNIFIED NAVIGATION BUTTONS ================= -->
<div class="badge-group mb-4">

    <!-- 🟡 Overview -->
    <a href="{{ route('dashboard') }}"
       class="badge text-decoration-none px-3 py-2 border rounded-pill
       {{ request()->routeIs('dashboard') || request()->is('/')
            ? 'border-warning text-warning bg-transparent'
            : 'bg-warning text-white border-warning' }}">
        Overview
    </a>

    <!-- 🔵 Sales -->
    <a href="{{ route('sales') }}"
       class="badge text-decoration-none px-3 py-2 border rounded-pill
       {{ request()->routeIs('sales')
            ? 'border-primary text-primary bg-transparent'
            : 'bg-primary text-white border-primary' }}">
        Sales
    </a>

    <!-- 🟢 Accounts Receivable -->
    <a href="{{ route('ar.index') }}"
       class="badge text-decoration-none px-3 py-2 border rounded-pill
       {{ request()->routeIs('ar.*')
            ? 'border-success text-success bg-transparent'
            : 'bg-success text-white border-success' }}">
        Accounts Receivable
    </a>

    <!-- 🔴 Accounts Payable -->
    <a href="{{ route('ap.index') }}"
       class="badge text-decoration-none px-3 py-2 border rounded-pill
       {{ request()->routeIs('ap.*')
            ? 'border-danger text-danger bg-transparent'
            : 'bg-danger text-white border-danger' }}">
        Accounts Payable
    </a>

    <!-- 🟠 Inventory -->
    <a href="{{ route('inventory.index') }}"
       class="badge text-decoration-none px-3 py-2 border rounded-pill
       {{ request()->routeIs('inventory.*')
            ? 'border-inventory text-inventory bg-transparent'
            : 'bg-inventory text-white border-inventory' }}">
        Inventory
    </a>

    <!-- 🟦 Gross Profit -->
    <a href="{{ route('gross-profit.index') }}"
       class="badge text-decoration-none px-3 py-2 border rounded-pill
       {{ request()->routeIs('gross-profit.*')
            ? 'border-teal text-teal bg-transparent'
            : 'bg-teal text-white border-teal' }}">
        Gross Profit
    </a>

</div>
