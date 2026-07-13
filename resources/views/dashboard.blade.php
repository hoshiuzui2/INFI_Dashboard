@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')

<!-- ================= EXECUTIVE KPI DASHBOARD ================= -->
<div class="exec-cards">

<style>
    /* Core KPI colors */
    .text-blue { color: #0d6efd !important; }
    .text-green { color: #198754 !important; }
    .text-red { color: #dc3545 !important; }

    /* Business KPIs */
    .text-inventory { color: #fd7e14 !important; }     /* Orange */
    .text-gross-profit { color: #0f766e !important; }  /* Teal */

    .exec-cards .card {
        border-radius: 12px;
        min-height: 150px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.04);
        padding: 1.1rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .card-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .label {
        font-size: .95rem;
        color: #6c757d;
    }

    .metric {
        font-weight: 800;
        font-size: 1.8rem;
        line-height: 1;
    }

    .muted-small {
        color: #6c757d;
        font-size: .88rem;
    }

    .kpi-icon {
        font-size: 1.4rem;
        opacity: .35;
    }

    .card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
</style>

<!-- ================= ROW 1 ================= -->
<div class="row g-4">

    <!-- Sales -->
    <div class="col-md-4">
        <a href="{{ route('sales') }}" class="card-link">
            <div class="card h-100">
                <div class="card-header-row">
                    <div class="label">Sales</div>
                    <i class="bi bi-graph-up-arrow kpi-icon text-blue"></i>
                </div>
                <div class="metric text-blue">
                    {{ $data['salesBookings'] ?? '₱0' }}
                </div>
                <small class="muted-small text-blue">↑ Sales Performance</small>
            </div>
        </a>
    </div>

    <!-- Accounts Receivable -->
    <div class="col-md-4">
        <a href="{{ route('ar.index') }}" class="card-link">
            <div class="card h-100">
                <div class="card-header-row">
                    <div class="label">Total Accounts Receivable</div>
                    <i class="bi bi-receipt kpi-icon text-green"></i>
                </div>
                <div class="metric text-green">
                    {{ $data['totalAR'] ?? '₱0' }}
                </div>
                <small class="muted-small text-green">Pending Invoices</small>
            </div>
        </a>
    </div>

    <!-- Accounts Payable -->
    <div class="col-md-4">
        <a href="{{ route('ap.index') }}" class="card-link">
            <div class="card h-100">
                <div class="card-header-row">
                    <div class="label">Total Accounts Payable</div>
                    <i class="bi bi-wallet2 kpi-icon text-red"></i>
                </div>
                <div class="metric text-red">
                    {{ $data['totalAP'] ?? '₱0' }}
                </div>
                <small class="muted-small">Outstanding Payments</small>
            </div>
        </a>
    </div>

</div>

<!-- ================= ROW 2 ================= -->
<div class="row g-4 mt-3 justify-content-center">

   <div class="col-md-4">
    <a href="{{ route('inventory.index') }}" class="card-link">
        <div class="card h-100">
            <div class="card-header-row">
                <div class="label">Inventory</div>
                <i class="bi bi-box-seam kpi-icon text-inventory"></i>
            </div>
            <div class="metric text-inventory">
                {{ $data['inventory'] ?? '₱0' }}
            </div>
            <small class="muted-small">Current Stock Value</small>
        </div>
    </a>
</div>


    <!-- Gross Profit -->
  <div class="col-md-4">
    <a href="{{ route('gross-profit.index') }}" class="card-link">
        <div class="card h-100">
            <div class="card-header-row">
                <div class="label">Gross Profit</div>
                <i class="bi bi-percent kpi-icon text-gross-profit"></i>
            </div>
            <div class="metric text-gross-profit">
                {{ $data['grossProfit'] ?? '₱0' }}
            </div>
            <small class="muted-small">Revenue − COGS</small>
        </div>
    </a>
</div>


</div>

</div>
@endsection
