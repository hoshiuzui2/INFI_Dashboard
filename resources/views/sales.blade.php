    @extends('layouts.app')

    @section('title', 'Sales Summary')

    @section('content')

    <style>
    body {
        background-color: #f9fbfd;
        font-family: "Inter", system-ui, sans-serif;
    }

    /* 🎯 Filters */
    #filterForm {
        margin-bottom: 1.2rem;
    }
    #filterForm .form-select,
    #filterForm .btn {
        height: 46px;
        font-size: 0.9rem;
        border-radius: 10px;
    }
    #filterForm .col-md-3 {
        display: flex;
        align-items: center;
    }
    /* === Ensure filter controls are clickable normally, but don't force them on top of popovers === */
    #filterForm {
        position: relative;
        z-index: 0; /* neutral — popover/backdrop control stacking explicitly */
    }
    #filterForm .form-select,
    #filterForm .btn {
        position: relative;
        z-index: 0; /* neutral */
        pointer-events: auto;
    }


    /* ===== FORCE native select controls to avoid flicker ===== */
    #filterForm .form-select,
    #filterForm select {
        -webkit-appearance: menulist-button !important;
        -moz-appearance: menulist !important;
        appearance: auto !important;
        background-image: none !important;
        box-shadow: none !important;
        transition: none !important;
        animation: none !important;
        outline: none !important;
    }

    /* ensure the select expand arrow shows in IE/Edge if needed */
    #filterForm .form-select::-ms-expand { display: block; }

    /* small safety: don't force layer promotion on the entire modal/backdrop */
    .modal, .modal-backdrop, .modal .modal-dialog {
        will-change: auto !important;
        transform: none !important;
        backface-visibility: hidden !important;
        transition: none !important;
    }

    /* ===== Unified Loading Overlay (authoritative) =====
    This block is the only definition for #loadingOverlay used by the page.
    When inactive pointer-events: none so it won't steal clicks (chart & popover are clickable).
    */
    #loadingOverlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(255,255,255,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        /* keep loader below modal/backdrop so popovers/modals are reachable */
        z-index: 1030 !important;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.18s ease;
        pointer-events: none; /* default: don't block clicks */
    }
    #loadingOverlay.active {
        visibility: visible;
        opacity: 1;
        pointer-events: auto; /* when active, block page interactions */
        z-index: 1030 !important;
    }

    .spinner-border {
        width: 2.3rem;
        height: 2.3rem;
        color: #0d6efd;
    }

    /* authoritative z-index ordering:
    loader: 1030  (under popover/backdrop)
    backdrop: 1045 (above loader, under popover)
    popover: 1055  (above backdrop)
    */
   

    /* ⚙️ Cards Layout */
    .card {
        border-radius: 14px;
        border: none;
        background: #fff;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        transition: 0.2s ease;
    }
    /* lighter hover */
    .card:hover {
        box-shadow: 0 4px 16px rgba(13,110,253,0.1);
    }

    /* 📏 Balanced Card Heights */
    .summary-row .card {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        text-align: center;
        padding: 0.9rem 0.8rem;
        min-height: 190px;
        box-sizing: border-box;
    }
    /* Make drill cards identical to summary cards */
.drill-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    text-align: center;
    padding: 0.9rem 0.8rem;
    min-height: 190px;
    border-left: 3px solid #0d6efd !important;
    transition: 0.2s ease;
}

.drill-card:hover {
    box-shadow: 0 4px 16px rgba(13,110,253,0.1);
}
/* Enlarge drilldown icons */
.drill-card i {
    font-size: 3.5rem !important;   /* increase size */
    margin-bottom: 12px;
    transition: transform 0.2s ease;
}

/* Optional subtle hover effect */
.drill-card:hover i {
    transform: scale(1.08);
}
    /* Adjusted Chart Container */
    .chart-container {
        width: 100%;
        height: 70px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 4px 0;
    }
    .chart-container canvas {
        width: 92% !important;
        height: 70px !important;
        animation: fadeInChart 0.9s ease forwards;
        opacity: 0;
    }
    @keyframes fadeInChart {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Make Total Sales card same height as others */
    #summaryCards .col:first-child .card {
        min-height: 190px !important;
        padding-top: 0.8rem !important;
        padding-bottom: 0.6rem !important;
    }
    #summaryCards .col:first-child .chart-container {
        height: 65px !important;
        margin-top: 0.3rem;
        margin-bottom: 0.3rem;
    }
    #summaryCards .col:first-child .chart-container canvas {
        height: 65px !important;
    }
    #summaryCards .col:first-child small.text-muted {
        margin-top: 0 !important;
    }

    /* 🎨 Unified Blue Theme */
    .summary-row .card {
        border-left: 3px solid #0d6efd !important;
    }
 .summary-row .metric {
    color: #0d6efd !important;
    white-space: nowrap;
    min-height: 32px;
}

    .summary-row .label {
        color: #495057;
    }

    /* Footer */
    .refresh-footer {
        text-align: right;
        font-size: 0.82rem;
        color: #6c757d;
        margin-top: 14px;
    }

    /* Drilldown modal tweaks */
    #customerDetailsModal .modal-dialog { max-width: 1100px; }
    #customerDetailsModal .modal-header { background: linear-gradient(90deg,#0d6efd,#2563eb); color: #fff; }
    #customerDetailsModal .summary-chip { font-weight:600; font-size:0.95rem; }
    #customerDetailsModal .table-responsive { max-height: 420px; overflow: auto; }

    /* ================================
    ADD-ON: Improved modal UI (append only)
    ================================ */

    /* Modal container tweaks */
    #customerDetailsModal .modal-dialog { max-width: 1100px; }

    /* Keep modal content overflow hidden and rounded */
    #customerDetailsModal .modal-content { border-radius: 10px; overflow: hidden; }

    /* Header styling */
    #customerDetailsModal .modal-header {
        padding: 18px 22px;
        background: linear-gradient(90deg,#0d6efd,#2563eb);
        color: #ffffff;
        border-bottom: none;
    }

    #customerDetailsModal .modal-title { font-size: 1.12rem; font-weight: 700; }
    #customerDetailsModal #customerDetailsPeriod { display: block; font-size: 0.9rem; opacity: 0.95; margin-top: 4px; }

    #customerDetailsModal .modal-body {
        padding: 18px 20px;
        background: #f7fbff;
    }

    /* Table card tweaks */
    #customerDetailsModal .modal-table-card {
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 6px 16px rgba(13,110,253,0.04);
        margin-bottom: 0.5rem;
    }

    /* Keep the table header sticky */
    #customerDetailsModal .modal-table-card thead th {
        position: sticky;
        top: 0;
        background: #ffffff;
        z-index: 5;
        border-bottom: 1px solid rgba(0,0,0,0.06);
        font-weight: 700;
        padding: 12px 10px;
    }

    /* Table body spacing */
    #customerDetailsModal .modal-table-card tbody td {
        padding: 10px 10px;
        vertical-align: middle;
        font-size: 0.95rem;
    }

    /* Scroll area for table */
    #customerDetailsModal .modal-table-body {
        max-height: 420px;
        overflow: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Empty state / placeholder */
    #customerDetailsEmpty {
        padding: 28px;
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 6px 12px rgba(0,0,0,0.03);
    }

    /* Footer layout in modal */
    #customerDetailsModal .modal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 18px;
    }

    /* Modal loader inside modal (rare) */
    #customerDetailsModal .modal-loader {
        position: absolute;
        inset: 86px 22px 72px;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 50;
    }
    #customerDetailsModal.loading .modal-loader { display: flex; }
    #customerDetailsModal.loading .modal-table-card { opacity: 0.6; pointer-events: none; }

    /* Small responsive tweaks */
    @media (max-width: 768px) {
        #customerDetailsModal .modal-dialog { max-width: 95%; }
        #customerDetailsModal .modal-body { padding: 14px; }
        #customerDetailsModal .modal-title { font-size: 1rem; }
    }

    /* Better select visuals */
    #filterForm .form-select {
        padding: 10px 14px;
        border: 1px solid rgba(13,110,253,0.12);
        background: linear-gradient(180deg,#fff,#fbfdff);
        box-shadow: inset 0 -1px 0 rgba(0,0,0,0.02);
        transition: box-shadow .15s ease, border-color .15s ease;
    }
    #filterForm .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 6px 18px rgba(13,110,253,0.06);
        outline: none;
    }

    /* Table layout improvements */
    #customerDetailsTable { table-layout: auto; width: 100%; border-collapse: separate; }
    #customerDetailsTable th:nth-child(3), #customerDetailsTable td:nth-child(3) { min-width: 240px; max-width: 520px; white-space: normal; word-wrap: break-word; }
    #customerDetailsTable th:nth-child(1), #customerDetailsTable td:nth-child(1), #customerDetailsTable th:nth-child(2), #customerDetailsTable td:nth-child(2) { white-space: nowrap; width: 1%; }
    #customerDetailsTable th:nth-child(6), #customerDetailsTable td:nth-child(6) { white-space: nowrap; text-align: right; }
    #customerDetailsTable tbody td { padding: 10px 12px; font-size: 0.97rem; }
    #customerDetailsTable thead th { position: sticky; top: 0; background: #fff; z-index: 4; }

    #customerDetailsModal .modal-body { padding: 18px 22px; }

    /* Keep native appearance on select */
    #filterForm .form-select {
        -webkit-appearance: menulist-button !important;
        -moz-appearance: menulist !important;
        appearance: auto !important;
        background-repeat: no-repeat;
        background-position: right 12px center;
        pointer-events: auto;
    }

    /* Anti-Flicker Compositing Fixes (only for heavy elements) */
    #customerDetailsModal .chart-container,
    #customerDetailsModal canvas {
        will-change: opacity, transform;
        transform: translateZ(0);
    }

    /* avoid turning modal/backdrop into accelerated layers */
    .modal, .modal-backdrop { will-change: auto; transform: none; }

    /* Remove dialog transitions that clash with native dropdowns */
    .modal .modal-dialog { transition: none !important; }

    /* performance: soften card hover repainting */
    #summaryCards .card { transition: box-shadow 0.12s ease; }

    /* Prevent overlay interference with dropdowns (already handled above) */
    #loadingOverlay { z-index: 1030 !important; pointer-events: none; }
    #loadingOverlay.active { z-index: 1030 !important; pointer-events: auto; }

    /* Disable chart canvas animations inside modal to prevent repaint flicker */
    #customerDetailsModal canvas,
    #customerDetailsModal .chart-container canvas {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
        will-change: auto !important;
    }

    /* PROFESSIONAL CUSTOMER POP-UP (FINAL) */
    .customer-popover {
        position: fixed;
        top: 6vh;
        left: 50%;
        transform: translateX(-50%) scale(.98);
        width: min(1100px, 94%);
        max-height: 82vh;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 18px 60px rgba(0,0,0,0.18);
        overflow: hidden;
        opacity: 0;
        transition: opacity .22s ease, transform .22s cubic-bezier(.2,.9,.2,1);
    }
    .customer-popover.show { opacity: 1; transform: translateX(-50%) scale(1); }

    /* Header */
    .customer-popover .cp-header {
        background: #0d6efd;
        padding: 20px 26px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #fff;
        border-bottom: 1px solid rgba(255,255,255,0.18);
    }
    .customer-popover .cp-title { font-size: 1.25rem; font-weight: 700; }
    .customer-popover .cp-period { font-size: 0.95rem; opacity: .95; margin-left: 12px; }

    /* Buttons */
    .customer-popover .cp-close {
        font-size: 22px;
        background: rgba(255,255,255,0.18);
        border: none;
        color: #fff;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        cursor: pointer;
        transition: background .15s;
    }
    .customer-popover .cp-close:hover { background: rgba(255,255,255,0.28); }
    .customer-popover .cp-export { font-size: 0.9rem; padding: 6px 12px; border-radius: 8px; }

    /* Body */
    .customer-popover .cp-body {
        background: #ffffff;
        padding: 18px 22px;
        max-height: calc(82vh - 140px);
        overflow-y: auto;
    }

    /* Table inside popover */
    .customer-popover .cp-body table { width: 100%; background: #fff; border-radius: 10px; overflow: hidden; }
    .customer-popover .cp-body table thead th {
        background: #f2f7ff;
        font-weight: 700;
        padding: 12px 14px;
        border-bottom: 1px solid #e4e8f0;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .customer-popover .cp-body table tbody td { padding: 10px 14px; font-size: 0.95rem; vertical-align: middle; }

    .customer-popover .cp-footer {
        background: #f1f5ff;
        border-top: 1px solid #d8e2ff;
        padding: 14px 26px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 1rem; font-weight: 600; color: #1b2a4e;
    }
    .cp-footer .total-amount { font-size: 1.15rem; font-weight: 800; color: #0d6efd; }

  

    /* Summary inside popover */
    .customer-popover .cp-summary {
        display: flex;
        gap: 14px;
        align-items: center;
        padding: 12px 20px;
        background: linear-gradient(90deg, rgba(13,110,253,0.04), rgba(13,110,253,0.02));
        border-bottom: 1px solid rgba(13,110,253,0.06);
    }
    .customer-popover .cp-summary .pill {
        display: inline-flex;
        gap: 10px;
        align-items: center;
        padding: 8px 12px;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(13,110,253,0.03);
        font-weight: 600;
        color: #1b2a4e;
        font-size: 0.95rem;
    }
    .customer-popover .cp-summary .pill .label { color: #6c757d; font-weight: 500; font-size: 0.78rem; margin-right: 6px; }
    .customer-popover .cp-summary .pill .value { color: #0d6efd; font-weight: 800; font-size: 1rem; }

    /* make body the only scroller */
    .customer-popover .cp-body { flex: 1 1 auto; overflow-y: auto; padding: 16px 22px; background: #fff; }

    /* keep table header sticky inside popover body */
    .customer-popover .cp-body table thead th { position: sticky; top: 0; z-index: 9; background: #f7fbff; border-bottom: 1px solid #e6eefc; }

    .card-header i {
  opacity: 0.9;
  font-size: 1.05rem;
  vertical-align: middle;
}

.popover-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.35);
    z-index: 1045; /* below popover */
}

.customer-popover {
    z-index: 1055; /* above backdrop */
}

.popover-backdrop {
    pointer-events: none;
}

.popover-backdrop.show {
    pointer-events: auto;
}
    </style>

    <!-- Spinner -->
    <div id="loadingOverlay">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
    </div>

    <!-- Filters -->
    <form id="filterForm" class="row g-3 align-items-center">
        <div class="col-md-3">
            <select name="year" class="form-select" id="filterYear">
                <option value="">All Years</option>
                @foreach ($availableYears as $y)
                    <option value="{{ $y }}" {{ (string)$year === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="month" class="form-select" id="filterMonth">
                <option value="">All Months</option>
                @foreach ($availableMonths as $m)
                    <option value="{{ $m }}" {{ (string)$month === (string)$m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button type="button" id="applyFilters" class="btn btn-primary w-100">Apply Filters</button>
        </div>
        <div class="col-md-3">
            <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">Reset</button>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="row row-cols-1 row-cols-md-4 g-3 mb-3 summary-row" id="summaryCards">
        <!-- Total Sales -->
<div class="col">
    <div class="card shadow-sm" id="totalSalesCard" style="cursor:pointer;">
                <div class="label">Total Sales (₱)</div>
                <div class="metric" id="totalSalesValue">{{ $totalSalesFormatted ?? '₱0.00' }}</div>
                <div class="chart-container"><canvas id="yearlySalesMiniChart"></canvas></div>
                <small class="text-muted" id="yearLabel">{{ $year ? "Year: $year" : 'All Years' }}</small>
            </div>
        </div>

        <!-- Monthly Sales -->
        <div class="col">
            <div class="card shadow-sm">
                <div class="label">Monthly Sales</div>
                <div class="chart-container"><canvas id="monthlyClusterChart"></canvas></div>
                <small class="text-muted">Click a bar to filter</small>
            </div>
        </div>

        <!-- Sales per Month -->
        <div class="col">
            <div class="card shadow-sm">
                <div class="label">Sales per Month</div>
                <div class="metric" id="salesPerMonthValue">{{ $salesPerMonthFormatted ?? '₱0.00' }}</div>
                <small class="text-muted" id="salesMonthLabel">{{ $month ? "Month: $month" : 'All Months' }}</small>
            </div>
        </div>

        <!-- Avg Sales per Customer -->
        <div class="col">
            <div class="card shadow-sm">
                <div class="label">Avg Sales per Customer</div>
                <div class="metric" id="avgSalesValue">{{ $avgSalesPerCustomerFormatted ?? '₱0.00' }}</div>
                <small class="text-muted" id="avgLabel">{{ $month ? "Filtered" : "All Time" }}</small>
            </div>
        </div>
    </div>

    <!-- Drilldown Cards -->
<div class="mb-4">
  

  <div>
    <div class="row g-3 text-center">

      <div class="col-md-3">
        <div class="card p-3 h-100 drill-card" data-type="customer">
          <i class="bi bi-people fs-1 text-primary"></i>
          <div class="fw-bold mt-2">Customers</div>
          <small class="text-muted">View customer breakdown</small>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card p-3 h-100 drill-card" data-type="product">
          <i class="bi bi-box-seam fs-1 text-primary"></i>
          <div class="fw-bold mt-2">Products</div>
          <small class="text-muted">Sales by product</small>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card p-3 h-100 drill-card" data-type="agent">
          <i class="bi bi-person-badge fs-1 text-primary"></i>
          <div class="fw-bold mt-2">Sales Agents</div>
          <small class="text-muted">Performance by agent</small>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card p-3 h-100 drill-card" data-type="division">
          <i class="bi bi-diagram-3 fs-1 text-primary"></i>
          <div class="fw-bold mt-2">Divisions</div>
          <small class="text-muted">Division overview</small>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="refresh-footer mb-4">
    <span id="latestRefresh">
        Latest Refresh: {{ now()->format('F d, Y') }}
    </span>
</div>

    <!-- Top Customers -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white text-center">
          <h5 class="m-0">
 <i class="bi bi-trophy me-2"></i>
  Top 10 Customers by Sales
</h5>
        </div>
        <div class="card-body p-0">
            <div class="modal-table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="text-start">Customer Name</th>
                            <th class="text-end">Total (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="topCustomersBody">
                        @foreach ($topCustomers as $index => $customer)
                            <tr class="top-customer-row" data-customer="{{ $customer->{'Customer Name'} }}">
                                <td>{{ $index + 1 }}</td>
                                <td class="text-start">{{ $customer->{'Customer Name'} }}</td>
                                <td class="text-end">₱{{ number_format($customer->TotalAmount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <!-- Total Sales Monthly Breakdown Modal -->
<div class="modal fade"
     id="salesBreakdownModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="salesBreakdownTitle">
    Monthly Sales Breakdown
</h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Sales</th>
                        </tr>
                    </thead>

                    <tbody id="salesBreakdownBody"></tbody>

                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th class="text-end" id="salesBreakdownTotal">
                                ₱0.00
                            </th>
                        </tr>
                    </tfoot>
                </table>

            </div>

        </div>
    </div>
</div>


    @endsection
    @push('scripts')
    <script>
    window.SALES_CONFIG = {
    
        realtime: "{{ route('sales.realtime') }}",
        customerDetails: "{{ route('sales.customer.details') }}",
        productDetails: "{{ route('sales.product.details') }}",
        dateTo: "{{ $dateTo ?? date('Y-m-d') }}",
        salesByMonth: {!! json_encode($salesByMonth ?? []) !!},
        salesByYear: {!! json_encode($salesByYear ?? []) !!},
        topCustomers: {!! json_encode($topCustomers ?? []) !!},
        totalSalesFormatted: "{{ $totalSalesFormatted ?? '₱0.00' }}",
        allTimeTotalFormatted: "{{ $allTimeTotalFormatted ?? ($totalSalesFormatted ?? '₱0.00') }}"
    };
    </script>

    <!-- load compiled sales.js (keep defer) -->
    <script src="{{ asset('js/sales.js') }}" defer></script>
    @endpush
