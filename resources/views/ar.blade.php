{{-- resources/views/ar.blade.php --}}
@extends('layouts.app')

@section('title','Accounts Receivable')

@section('styles')
<!-- moved icons css here so icons are present immediately -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
:root{
  --primary: #0f766e;
  --bg: #f8fafc;
  --muted: #64748b;
  --card-border: #e6eef0;
  --text: #1f2937;
  --accent: #10b981;
}
body { background: var(--bg); font-family: 'Inter', system-ui, -apple-system, sans-serif; color:var(--text); }

/* KPI cards */.dashboard-card {
  background: #fff;
  border: 1px solid var(--card-border);
  border-radius: 12px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  height: 100%;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  overflow: visible;
  border-top: 4px solid var(--primary);
}
.dashboard-card .kpi-top {
  min-height: 92px;          /* 🔒 aligns KPI value row */
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.label-upper { font-size:0.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; margin-bottom:4px; letter-spacing:0.06em; }
.metric-value {
  font-size: 1.9rem;
  font-weight: 800;
  line-height: 1.1;
  margin: 2px 0;
  color: var(--text);
  letter-spacing: -0.02em;
}

.metric-sub { color:var(--muted); font-size:0.9rem; margin-top:0; }

/* table card */
.table-card { background:#fff; border-radius:12px; border:1px solid var(--card-border); box-shadow:0 2px 6px rgba(0,0,0,0.04); overflow:hidden; display:flex; flex-direction:column; height:100%; position:relative; }
.table-header {
  padding:14px 18px;
  border-bottom:1px solid var(--card-border);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  background:#fff;
  flex-wrap:wrap;
  z-index:15; /* keep header above table for clickable controls */
  pointer-events:auto;
}

/* controls container that holds search, date + buttons aligned (right) */
.controls { display:flex; gap:10px; align-items:center; margin-left:auto; flex-wrap: nowrap; }

/* search - reduced width and predictive suggestions */
.search-box { position:relative; width:240px; } /* reduced width */
.search-box input { width:100%; padding:10px 12px 10px 36px; border-radius:10px; border:1px solid var(--card-border); background:#fff; font-size:0.95rem; }
.search-box i { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none; }
.suggestions { position:absolute; left:0; right:0; top:44px; background:#fff; border:1px solid var(--card-border); border-radius:8px; max-height:240px; overflow:auto; z-index:9999; display:none; box-shadow:0 6px 18px rgba(0,0,0,0.06); }
.suggestions .item { padding:8px 12px; cursor:pointer; font-size:0.95rem; color:var(--text); }
.suggestions .item:hover, .suggestions .item.active { background:#f1f6f6; }

/* table - prevent sideways scroll and avoid value wrap */
.table-responsive {
  max-height:520px;
  overflow-y: auto;
  overflow-x: auto; z-index:1; padding-bottom: 90px; } /* extra bottom padding so footer doesn't cover last rows */
/* ensure table uses fixed layout so numeric columns won't wrap and widths are predictable */
.table {
  min-width: 1350px;   /* 🔥 forces horizontal scroll */
  width:100%;
  table-layout:fixed; border-collapse:collapse; }
.table thead th { position:sticky; top:0; background:#f8fafc; font-size:0.8rem; font-weight:700; color:var(--muted); padding:12px 18px; border-bottom:1px solid var(--card-border); z-index:5; text-align:left; }
.table td {
  padding:12px 18px;
  border-bottom:1px solid #f1f6f6;
  vertical-align:middle;
  font-size:0.95rem;
}

/* 🔒 FINANCIAL NUMBERS MUST NEVER WRAP */
.table td.text-end,
.table th.text-end {
  white-space: nowrap !important;
  word-break: keep-all !important;
  font-variant-numeric: tabular-nums;
}



.table td .cust-name {
  display: -webkit-box;
  -webkit-line-clamp: 2;        /* 🔥 max 2 lines */
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-width: 300px;
  white-space: normal;          /* 🔥 allow wrap */
  line-height: 1.2;
}

/* top list: cleaned up style for professional look */
.top-list-item { display:flex; gap:12px; align-items:flex-start; padding:12px 14px; border-bottom:1px dashed #eef4f4; }
.top-list-item .meta { min-width:0; overflow:hidden; flex:1; }
.top-list-item .meta .title { font-weight:700; font-size:0.95rem; display:block; color:var(--text); line-height:1.1; } /* show full name (wrap) */
.top-list-item .meta .sub { font-size:0.80rem; color:var(--muted); margin-top:6px; } /* muted invoice line */
.top-list-item .amount { text-align:right; min-width:120px; align-self:center; } /* ensure amounts have room and are right-aligned */
.top-list-item .amount .fw-bold { font-weight:700; }

/* buttons */
.btn-refresh { background: var(--primary); color:white; border: 0; padding: 8px 14px; border-radius:8px; box-shadow:none; display:inline-flex; align-items:center; gap:8px; font-weight:600; }
.btn-export { border-radius:8px; padding:7px 10px; }
.form-control-sm { height:38px; }



/* modal improvements */
.modal-header { background: var(--primary); color:white; border-bottom:0; }

/* ================================
   MODAL – TIGHT PROFESSIONAL MODE
   ================================ */

#detailModal .modal-header {
  padding: 14px 20px;
}

#detailModal .modal-body {
  padding: 0;
}

#detailModal .table-responsive {
  max-height: 420px; /* slightly smaller */
}

#detailModal .table thead th {
  padding: 10px 14px;
  font-size: 0.80rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

#detailModal .table td {
  padding: 10px 14px;      /* slightly more breathing space */
  font-size: 0.90rem;
  line-height: 1.35;       /* improves readability */
}

#detailModal .table td.text-end {
  padding-left: 10px;
  padding-right: 16px;
  font-variant-numeric: tabular-nums;
}
/* Reduce striped spacing impact */
#detailModal .table-striped tbody tr {
  line-height: 1.2;
}

/* 🔥 Softer striped background */
#detailModal .table-striped tbody tr:nth-of-type(odd) {
  background-color: #f9fbfb;
}



/* 🔥 Subtle hover effect */
#detailModal .table tbody tr:hover {
  background-color: #eef6f6;
}

/* Optional: tighter title */
#detailModal .modal-title {
  font-size: 1rem;
  font-weight: 600;
}
/* 🔥 FIX MODAL TABLE WIDTH ISSUE */
#detailModal .table {
  min-width: auto !important;
  table-layout: auto !important;
  width: 100% !important;
}

#detailModal .table-responsive {
  overflow-x: hidden !important;
}
/* small helpers */
.badge-count { background:#f3f4f6; border:1px solid var(--card-border); padding:6px 12px; border-radius:999px; font-weight:600; color:var(--text); }
.text-primary-custom { color:var(--primary) !important; font-weight:700; }
.text-success-custom { color:var(--accent) !important; font-weight:700; }

/* Footer / pagination fix: ensure it's above table scroll area and clickable */
.table-card .p-3 { position:relative; z-index:20; background:#fff; }
.btn-group { z-index: 30; pointer-events: auto; }
.btn-group .btn { min-width:100px; border-radius:8px; }

/* ---------- KPI & chart visual tweaks ---------- */

.dashboard-card #kpiTotal.metric-value {
  color: var(--primary) !important; /* dark green */
}


/* Slightly larger KPI canvases so tooltips/hovers are easier to hit */
#kpiSparkline {
  height: 42px !important;
  display: block;
  width: 100% !important;
}

#kpiAgingDonut {
  height: 110px !important;
  width: 110px !important;
  display: block;
}

.critical-item:hover {
  background:#fff7ed;
  border-radius:8px;
}

/* Donut container - make the canvas area square & centered */
.kpi-aging-canvas {
  width: 110px;
  height: 110px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 6px;
}


/* Table numeric spacing fixes - reduce horizontal padding for numeric cells and use tabular numbers */
.table td, .table thead th {
  padding-top:10px;
  padding-bottom:10px;
}
/* 🔥 Tight numeric spacing */
.table td.text-end {
  padding-left: 8px;
  padding-right: 8px;
  white-space: nowrap;
  font-variant-numeric: tabular-nums;
}


/* Make customer name column a little narrower on wide screens so numbers have room */
.table td .cust-name { max-width: 300px; }

/* make KPI small labels slightly bolder for contrast */
.label-upper { font-weight:800; }

/* Donut legend bullets slight adjustment so green shows properly */
.kpi-legend-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:10px; vertical-align:middle; }

.kpi-tooltip {
  cursor: help;
  border-bottom: none; /* REMOVE underline */
}

th.sortable {
  cursor: pointer;
  user-select: none;
}

th.sortable::after {
  content: " ⇅";
  font-size: 0.7rem;
  color: #6b7280;
}

th.sortable.asc::after {
  content: " ↑";
  color: #059669;
}

th.sortable.desc::after {
  content: " ↓";
  color: #059669;
}


.col-overdue {
  width: 150px;
  max-width: 150px;
  white-space: nowrap;
  text-align: right;
  color: #b45309;
  font-weight: 700;
}

.invoice-cell {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  line-height: 1.3;
  padding: 6px 0 !important;
  white-space: normal;   /* 🔥 allow two lines */
}

.invoice-cell .invoice-date {
  font-size: 0.85rem;
  font-weight: 500;
  line-height: 1.2;
}

.invoice-cell .invoice-count {
  font-size: 0.72rem;
  color: #6b7280;
  line-height: 1.1;
}
.table td.invoice-cell {
  min-height: 44px;        /* guarantees 2 lines fit */
  vertical-align: middle; /* keeps visual balance */
}

/* KPI value container fix */
.kpi-value-wrap {
  min-height: 52px;
  display: flex;
  align-items: center;
}

.metric-value {
  white-space: nowrap;
  overflow: visible;   /* 🔥 IMPORTANT */
  text-overflow: clip;
}
.col-pdc {
  width: 130px;        /* 🔥 FIXED width, not min */
  max-width: 130px;
  white-space: nowrap;
  text-align: right;
}
/* Agent column (1st column) */
.table th.col-agent,
.table td.col-agent {
  position: sticky;
  left: 0;
  z-index: 8;
  background: #ffffff;
}

/* Customer Name column (2nd column) */
.table th.col-customer,
.table td.col-customer {
  position: sticky;
  left: 90px; /* MUST equal Agent column width */
  z-index: 7;
  background: #ffffff;
}

/* Header cells need higher z-index */
.table thead th.col-agent,
.table thead th.col-customer {
  z-index: 10;
  background: #f8fafc;
}

/* Optional: subtle divider so frozen edge is visible */
.table td.col-customer::after,
.table th.col-customer::after {
  content: "";
  position: absolute;
  top: 0;
  right: 0;
  width: 1px;
  height: 100%;
  background: #e5e7eb;
}
.table td.col-customer,
.table th.col-customer {
  box-shadow: 6px 0 8px -6px rgba(0, 0, 0, 0.15);
}


.customer-info-body {
  padding: 8px 14px;
  max-height: 360px;
  overflow-y: auto;
  overflow-x: hidden;   /* 🔥 REMOVE LEFT–RIGHT SCROLL */
}

.customer-card {
  padding: 6px 0 8px;
  border-bottom: 1px solid #eef4f4;
}


.customer-card:last-child {
  border-bottom: none;
}
.customer-card + .customer-card {
  margin-top: 2px;           /* subtle rhythm instead of big gaps */
}
.customer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;       /* 🔥 cleaner alignment */
  margin-bottom: 6px;        /* 🔽 tighter */
}

.customer-name {
  font-weight: 700;
  font-size: 0.92rem;
  color: var(--primary);
}

.customer-short {
  font-weight: 500;
  color: var(--muted);
  margin-left: 4px;
}

.customer-sales {
  font-size: 0.75rem;
  color: var(--muted);
  font-weight: 600;
}

  .customer-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

/* Customer Information – table-like layout */

.customer-info-header,
.customer-row {
  display: grid;
  grid-template-columns:
    120px   /* Customer Code */
    100px   /* Salesperson */
    90px    /* Area */
    120px   /* Telephone */
    130px   /* Contact */
    90px    /* Fax */
    140px   /* Fax Contact */
    auto /* Email wraps, does NOT expand container */
    120px; 
  gap: 8px;
  align-items: center;
  width: 100%;
}

.customer-row a,
.customer-row div:last-child {
  word-break: break-word;
  overflow-wrap: anywhere;
  white-space: normal;
}

.customer-info-header div:last-child,
.customer-row div:last-child {
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.customer-info-header {
  position: sticky;
  top: 0;
  z-index: 10;

  background: #ffffff;
  font-size: 0.65rem;
  font-weight: 800;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 8px 0;
  border-bottom: 1px solid var(--card-border);
}

.customer-info-header {
  box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.customer-row {
  font-size: 0.78rem;
  color: var(--text);
  padding: 6px 0;
}

.customer-row a {
  color: var(--primary);
  text-decoration: none;
}

.customer-row a:hover {
  text-decoration: underline;
}


/* responsive tweaks */
@media (max-width: 1200px) {
  .table td .cust-name { max-width: 220px; }
  .search-box { width: 100%; max-width: 240px; }
  .controls { flex-wrap:wrap; gap:8px; margin-left:0; }
}

</style>
@endsection

@section('content')

@php
  // SERVER-SIDE: decode jsonPayload (controller MUST pass $jsonPayload)
  $payloadArr = [];
  if (!empty($jsonPayload)) {
      $payloadArr = json_decode($jsonPayload, true) ?: [];
  }
  $rawRows = $payloadArr['rows'] ?? [];

  // Group by CustomerName (server-side customer-level list & invoice grouping).
$customerList = $payloadArr['meta']['customersAggregated'] ?? [];


  // ✅ Build tooltip text ONCE per customer (server-side)
foreach ($customerList as &$c) {
    if (!empty($c['InvoiceDateList'])) {
        sort($c['InvoiceDateList']); // oldest → newest
$c['InvoiceTooltip'] =
    "Invoices:&#10;• " . implode("&#10;• ", $c['InvoiceDateList']);

    } else {
        $c['InvoiceTooltip'] = 'No open invoices';
    }
}
unset($c);


  // sums: prefer payload meta if available, otherwise compute
  $sums = $payloadArr['meta']['sums'] ?? null;
if (!$sums) {
    $sums = [
        'total'   => 0,
        'gross'   => 0,
        'credits' => 0,
        'net'     => 0,
        'current' => 0,
        'd30'     => 0,
        'd60'     => 0,
        'd90'     => 0,
        'd120'    => 0
    ];

      foreach ($customerList as $c) {
    $sums['total'] += $c['Total'];

if ($c['Total'] >= 0) {
    $sums['gross'] += $c['Total'];
} else {
    $sums['credits'] += abs($c['Total']);
}
          $sums['current'] += $c['Current'];
          $sums['d30'] += $c['d30'];
          $sums['d60'] += $c['d60'];
          $sums['d90'] += $c['d90'];
          $sums['d120'] += $c['Over120'];
    

      }
       $sums['net'] = ($sums['gross'] ?? 0) - ($sums['credits'] ?? 0);
  }
$critList = [];

foreach ($customerList as $c) {

    $overdueAmount = $c['Overdue'] ?? 0;

    if ($overdueAmount <= 0) continue;

    $critList[] = [
        'name'    => $c['CustomerName'],
        'crit'    => $overdueAmount,
        'total'   => $overdueAmount,
        'invoice' => $c['invoices'][0]['Invoice'] ?? null
    ];
}

  usort($critList, fn($a,$b)=> $b['crit'] <=> $a['crit']);
  $critList = array_slice($critList, 0, 5);

  // prepare JS-friendly invoices map and customers list for client-side paging/filter
  $invoicesJson = [];
  foreach ($customerList as $c) {
      $invoicesJson[$c['CustomerName']] = $c['invoices'] ?? [];
  }

  // embed: ensure we always provide 'customers' array for predictive search
  $embed = [];
  if (!empty($jsonPayload)) {
      $decoded = json_decode($jsonPayload, true);
      $embed = is_array($decoded) ? $decoded : [];
  } else {
      $embed = ['meta'=>['sums'=>$sums,'totalRows'=>count($customerList)], 'rows'=>$rawRows];
  }
  $embed['customers'] = array_values(array_column($customerList, 'CustomerName'));
@endphp

{{-- embedded JSON used by JS for charts / client filtering (no network calls) --}}
<script id="ar-raw-data" type="application/json">
{!! json_encode($embed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>

<div class="container-fluid px-4 py-4">

  <!-- KPI CARDS -->
  <div class="row g-4 mb-4">
   <div class="col-xl-3 col-md-6">
  <div class="dashboard-card">

    <!-- HEADER: Label + Year Filter -->
    <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="label-upper mb-0">Net Accounts Receivable</div>

      <select id="sparkYear"
              class="form-select form-select-sm"
              style="width:90px; border-radius:8px;">
        @for ($y = now()->year; $y >= now()->year - 5; $y--)
          <option value="{{ $y }}" {{ request('sparkYear', now()->year) == $y ? 'selected' : '' }}>
            {{ $y }}
          </option>
        @endfor
      </select>
    </div>

    <!-- KPI VALUE -->
    <div>
     <div class="kpi-value-wrap">
<div id="kpiTotal"
     class="metric-value text-success-custom">
  —
</div>

</div>

    

<div class="metric-sub" id="kpiAsOf">
  As of {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}
</div>

    </div>

    <!-- SPARKLINE -->
   <div style="height:42px; margin-top:4px;">
  <canvas id="kpiSparkline"></canvas>
</div>


  </div>
</div>


    <div class="col-xl-3 col-md-6">
      <div class="dashboard-card">
     <div>
  <div class="label-upper text-primary-custom">Current (0–29 days)</div>

  <!-- 🔒 FIX: reserve vertical space & center value -->
  <div class="kpi-value-wrap">
    <div id="kpiCurrent"
         class="metric-value text-primary-custom kpi-tooltip">
      —
    </div>
  </div>
</div>

        <div>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="metric-sub">Portfolio Health</div>
            <div id="txtCurrentPct" class="metric-sub">0%</div>
          </div>
          <div class="progress-thin" style="margin-top:8px;">
            <div id="progCurrent" class="progress-bar-custom" style="height:8px; width:0%; background:linear-gradient(90deg,var(--primary),var(--accent)); border-radius:999px;"></div>
          </div>
        </div>
                   <div style="height:42px; margin-top:6px;">
  <canvas id="kpiCurrentSpark"></canvas>
</div>

<div class="metric-sub text-muted" style="font-size:0.75rem;">
  Month-end current balance trend
</div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="dashboard-card" id="overdueCard" style="cursor:pointer;">
        <div>
    @php
$overdueTotal = $sums['trueOverdue'] ?? 0;
@endphp

<div class="label-upper">Overdue</div>
<div id="kpiCritical" class="metric-value text-primary-custom">—</div>

        </div>
        <div>
          <div class="metric-sub">Risk Level</div>
          <div class="progress-thin" style="margin-top:8px;">
            <div id="progCritical" class="progress-bar-custom" style="height:8px; width:0%; background:linear-gradient(90deg,var(--primary),#34d399); border-radius:999px;"></div>
          </div>
          <div class="metric-sub mt-2">Requires immediate action</div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="dashboard-card">
        <div class="label-upper">Aging Mix</div>
        <div class="d-flex align-items-center gap-3">
         <!-- Replace the inline-width div with this -->
<div class="kpi-aging-canvas">
  <canvas id="kpiAgingDonut" aria-label="Aging mix donut chart" role="img"></canvas>
</div>

          <div style="font-size:0.9rem; color:var(--muted);">

<div class="mb-2">
  <span class="kpi-legend-dot" style="background:#0f766e"></span> Current
</div>
<div class="mb-2">
  <span class="kpi-legend-dot" style="background:#34d399"></span> 30–60 Days
</div>
<div class="mb-2">
  <span class="kpi-legend-dot" style="background:#6ee7b7"></span> 60–90 Days
</div>
<div class="mb-2">
  <span class="kpi-legend-dot" style="background:#a3e635"></span> 90–120 Days
</div>
<div>
  <span class="kpi-legend-dot" style="background:#facc15"></span> 120+ Days
</div>


          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MAIN ROW -->
  <div class="row g-4">
    <div class="col-xl-3 col-lg-4 d-flex flex-column gap-4">
      <div class="table-card h-100">
        <div class="table-header">
          <div class="fw-bold">Top 5 Overdue</div>
        </div>

        <div id="topDebtorsList" style="flex:1; overflow:auto; padding:12px;">
          @if(empty($critList))
            <div class="text-muted text-center py-4 small">No critical invoices</div>
          @else
            @foreach($critList as $it)
<div class="top-list-item overdue-item"
     style="cursor:pointer"
     data-customer="{{ $it['name'] }}"
     data-overdue="{{ $it['total'] }}">

  <div style="display:flex; align-items:center; gap:12px; min-width:0; flex:1;">
    <div class="meta">
      <div class="title" title="{{ $it['name'] }}">{{ $it['name'] }}</div>
      <div class="sub">
        Invoice: <span class="fw-bold">{{ $it['invoice'] ?? '-' }}</span>
      </div>
    </div>
  </div>

  <div class="amount">
    <div class="fw-bold">
      {{ $it['total'] ? '₱'.number_format($it['total'],2) : '-' }}
    </div>
  </div>


</div>

            @endforeach
          @endif
        </div>
      </div>
    </div>


    <div class="col-xl-9 col-lg-8">
      <div class="table-card h-100">
        <div class="table-header">
          <div class="d-flex align-items-center gap-3">
            <div class="fw-bold">Detailed Customer Aging</div>
            <span class="badge-count">{{ count($customerList) }}</span>
          </div>

          {{-- Controls container: search + date + refresh + export aligned on the right --}}
          <div class="controls">
            <div class="search-box" id="searchBox">
              <i class="bi bi-search"></i>
              <input id="tableSearch" type="text"
       placeholder="Search customer or agent..."
       autocomplete="off"
       aria-label="Search customers or agents" />

              <div id="suggestions" class="suggestions" role="listbox" aria-label="Customer suggestions"></div>
            </div>

            <form id="filterForm" action="{{ route('ar.index') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
              <input type="date" name="dateTo" value="{{ $dateTo }}" class="form-control form-control-sm" style="border-radius:8px; border-color:var(--card-border);">
              <button type="submit" class="btn btn-refresh btn-sm" title="Refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </form>
              <a href="{{ route('ar.index', ['export'=>'csv','dateTo'=>$dateTo]) }}" class="btn btn-outline-secondary btn-export btn-sm">Export</a>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table mb-0">
  <thead>
<tr>
  <th class="sortable col-agent" style="width:90px" data-key="Agent">
    Agent
  </th>

  <th class="col-customer" style="width:260px">
    Customer Name
  </th>

  <th style="width:150px">Oldest Invoice Date</th>

  <th class="text-end sortable" style="width:110px" data-key="Current">Current</th>
  <th class="text-end sortable" style="width:110px" data-key="d30">30 Days</th>
  <th class="text-end sortable" style="width:110px" data-key="d60">60 Days</th>
  <th class="text-end sortable" style="width:110px" data-key="d90">90+ Days</th>
  <th class="text-end sortable" style="width:110px" data-key="Over120">120+ Days</th>

  <th class="text-end sortable col-overdue" style="width:150px" data-key="Overdue">
    Overdue Amount
  </th>

  <th class="text-end sortable col-pdc" style="width:140px" data-key="PDC">
    PDC
  </th>
  <th style="width:140px">
  PDC Date
</th>
  <th class="text-end sortable col-total" style="width:160px" data-key="Total">
    Total Balance
  </th>
</tr>
</thead>


            <tbody id="tableBody">
              {{-- Server-side initial render (so page ALWAYS shows data immediately) --}}
              @forelse($customerList as $c)
                @php $critVal = ($c['d90'] ?? 0) + ($c['Over120'] ?? 0); @endphp
                <tr data-customer="{{ htmlspecialchars($c['CustomerName'], ENT_QUOTES) }}">
  {{-- AGENT --}}
<td class="text-muted fw-semibold col-agent">
  {{ $c['Agent'] ?? '—' }}
</td>

<td class="fw-bold text-primary-custom col-customer">
  <span class="cust-name" title="{{ $c['CustomerName'] }}">
    {{ $c['CustomerName'] }}
  </span>
</td>


<td class="text-muted invoice-cell"
    title="{{ $c['InvoiceTooltip'] }}">
  <div class="invoice-date">
    {{ $c['OldestInvoiceDate']
        ? \Carbon\Carbon::parse($c['OldestInvoiceDate'])->format('M d, Y')
        : '—'
    }}
  </div>
  <div class="invoice-count">
    {{ $c['InvoiceCount'] }} open invoice{{ $c['InvoiceCount'] !== 1 ? 's' : '' }}
  </div>
</td>


{{-- ✅ Current --}}
<td class="text-end nowrap text-muted"
    title="{{ $c['Current'] ? '₱'.number_format($c['Current'],2) : '-' }}">
  {{ $c['Current'] ? '₱'.number_format($c['Current'],2) : '-' }}
</td>


                  <td class="text-end nowrap text-muted" title="{{ $c['d30'] ? '₱'.number_format($c['d30'],2) : '-' }}">{{ $c['d30'] ? '₱'.number_format($c['d30'],2) : '-' }}</td>
                  <td class="text-end nowrap text-muted" title="{{ $c['d60'] ? '₱'.number_format($c['d60'],2) : '-' }}">{{ $c['d60'] ? '₱'.number_format($c['d60'],2) : '-' }}</td>
             <td class="text-end nowrap">
  {{ $c['d90']>0 ? '₱'.number_format($c['d90'],2) : '-' }}
</td>

<td class="text-end nowrap {{ $c['Over120']>0 ? 'text-success-custom fw-bold' : 'text-muted' }}">
  {{ $c['Over120']>0 ? '₱'.number_format($c['Over120'],2) : '-' }}
</td>
@php
  $overdueAmount = $c['Overdue'] ?? 0;
@endphp
<td class="text-end fw-bold col-overdue"
    title="{{ $overdueAmount > 0 ? '₱'.number_format($overdueAmount,2) : '—' }}">
  {{ $overdueAmount > 0 ? '₱'.number_format($overdueAmount, 2) : '—' }}
</td>

{{-- ✅ PDC --}}
<td class="text-end fw-bold col-pdc"
    title="{{ $c['PDC'] > 0 ? '₱'.number_format($c['PDC'],2) : '—' }}">
  {{ $c['PDC'] > 0 ? '₱'.number_format($c['PDC'],2) : '—' }}
</td>

<td class="text-center">
  {{ !empty($c['AvailableDate'])
      ? \Carbon\Carbon::parse($c['AvailableDate'])->format('M d, Y')
      : '—'
  }}
</td>

<td class="text-end nowrap fw-bold text-dark col-total">
  {{ $c['Total'] ? '₱'.number_format($c['Total'],2) : '-' }}
</td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">No data available. Check controller returns $jsonPayload.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="p-3 border-top d-flex justify-content-between align-items-center">
          <span id="pageInfo" class="text-muted small">Showing server-rendered results</span>
          <div class="btn-group" role="group" aria-label="Pagination">
            <button id="prevPage" class="btn btn-sm btn-outline-secondary">Previous</button>
            <button id="nextPage" class="btn btn-sm btn-outline-secondary">Next</button>
          </div>
        </div>
      </div>
    </div>
</div>
 

<div class="row g-4 mt-1">
  <div class="col-12">
    <div class="table-card">
      <div class="table-header">
        <div class="fw-bold">Customer Information</div>
      </div>

      <div class="customer-info-body">
        {{-- Column headers --}}
<div class="customer-info-header">
  <div>Customer Code</div>
  <div>Salesperson</div>
  <div>Area</div>
  <div>Telephone</div>
  <div>Contact</div>
  <div>Fax</div>
  <div>Fax Contact</div>
  <div>Email</div>
  <div class="text-end">Credit Limit</div>
</div>

        @forelse($arCustomers as $cust)
          <div class="customer-card">

            <div class="customer-header">
              <div class="customer-name">
                {{ $cust->CustomerName }}
                @if(!empty($cust->ShortName))
                  <span class="customer-short">({{ $cust->ShortName }})</span>
                @endif
              </div>
             
            </div>
<div class="customer-row">
  <div class="fw-semibold">
    {{ $cust->Customer ?? '—' }}
  </div>
  <div>{{ $cust->SalesPerson ?? '—' }}</div>
  <div>{{ $cust->Area ?? '—' }}</div>
  <div>{{ $cust->Telephone ?? '—' }}</div>
  <div>{{ $cust->Contact ?? '—' }}</div>
  <div>{{ $cust->Fax ?? '—' }}</div>
  <div>{{ $cust->FaxContact ?? '—' }}</div>

  <div>
    @if(!empty($cust->Email))
      <a href="mailto:{{ $cust->Email }}">{{ $cust->Email }}</a>
    @else
      —
    @endif
  </div>

  {{-- ✅ CREDIT LIMIT --}}
  <div class="text-end fw-semibold">
    {{ $cust->CreditLimit > 0
        ? '₱'.number_format($cust->CreditLimit, 2)
        : '—'
    }}
  </div>
</div>


          </div>
        @empty
          <div class="text-muted text-center py-4 small">
            No customer master data available
          </div>
        @endforelse
      </div>
    </div>
  </div>
</div>



<!-- Aging Mix External Tooltip -->
<div id="agingTooltip"
     style="
       position: fixed;
       z-index: 99999;
       background: rgba(17,24,39,0.95);
       color: #fff;
       padding: 14px 18px;
       border-radius: 10px;
       font-size: 13px;
       font-weight: 500;
       pointer-events: none;
       white-space: nowrap;
       display: none;
       box-shadow: 0 10px 28px rgba(0,0,0,0.25);
     ">
</div>

<!-- details modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Customer Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div id="criticalSummary"
     class="p-3 border-bottom"
     style="background:#fff7ed;">
  <!-- injected by JS -->
</div>
        <div class="table-responsive" style="max-height:480px;">
          <table class="table table-striped mb-0">
            <thead class="bg-light">
  <tr>
    <th>Invoice</th>
    <th>Invoice Date</th>
    <th>Payment Terms</th>
    <th class="text-end">Amount</th>
    <th class="text-end">Balance</th>
    <th class="text-end">PDC</th>
    <th>PDC Date</th>
    <th class="text-end">Age</th>
  </tr>
</thead>

            <tbody id="modalBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ✅ ADD HERE -->
<div class="modal fade" id="overdueModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Overdue Breakdown</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="d-flex justify-content-between align-items-center mb-3">

  <div class="d-flex gap-2">
    <button class="btn btn-sm btn-outline-primary active" id="btnByCustomer">By Customer</button>
    <button class="btn btn-sm btn-outline-secondary" id="btnByInvoice">By Invoice</button>
  </div>

  <!-- ✅ EXPORT BUTTON -->
  <a href="{{ route('ar.export.overdue', ['dateTo' => $dateTo]) }}"
     class="btn btn-sm btn-success">
     Export CSV
  </a>

</div>

        <div class="table-responsive">
          <table class="table table-striped">
            <thead id="overdueHead"></thead>
            <tbody id="overdueBody"></tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection
@push('scripts') 
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // ---------- Utility helpers ----------
    const moneyFmt = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    const formatMoney = (n) => (n || n === 0) ? moneyFmt.format(Number(n)) : '-';
    const formatBig = (n) => {
  if (n === null || n === undefined) return '—';

  // 🔒 EXECUTIVE RULE: Total Outstanding can NEVER be negative


  if (n === 0) return '₱0.00';

  if (n >= 1_000_000_000) {
    return '₱' + (n / 1_000_000_000).toFixed(2) + 'B';
  }

  if (n >= 1_000_000) {
    return '₱' + (n / 1_000_000).toFixed(2) + 'M';
  }

  return formatMoney(n);
};



    const formatCompact = (n) => {
  if (n === null || n === undefined) return '---';
  n = Number(n);

  if (n < 1_000) return formatMoney(n);
  if (n < 1_000_000) return '₱' + (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K';
  if (n < 1_000_000_000) return '₱' + (n / 1_000_000).toFixed(2).replace(/\.00$/, '') + 'M';
  return '₱' + (n / 1_000_000_000).toFixed(2).replace(/\.00$/, '') + 'B';
};

    const escHtml = s => (s === null || s === undefined) ? '' : String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    const escAttr = s => escHtml(s).replace(/"/g,'&quot;');

    // ---------- DOM elements ----------
    const rawDataEl = document.getElementById('ar-raw-data');
    const tbody = document.getElementById('tableBody');
    const searchEl = document.getElementById('tableSearch');
    const suggestionsEl = document.getElementById('suggestions');
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    const pageInfo = document.getElementById('pageInfo');
    const filterForm = document.getElementById('filterForm');
    const dateInput = document.querySelector('#filterForm input[type="date"]');

    // KPI elements
    const kpiTotal = document.getElementById('kpiTotal');
    const kpiCurrent = document.getElementById('kpiCurrent');
    const kpiCritical = document.getElementById('kpiCritical');
    const kpiAsOf = document.getElementById('kpiAsOf');
    const progCurrent = document.getElementById('progCurrent');
    const progCritical = document.getElementById('progCritical');
    const txtCurrentPct = document.getElementById('txtCurrentPct');

    // canvases
    const sparkCanvas = document.getElementById('kpiSparkline');
    const donutCanvas = document.getElementById('kpiAgingDonut');

    const currentSparkCanvas = document.getElementById('kpiCurrentSpark');

    // If Chart.js is loaded in the layout (preferred), we rely on window.Chart. Do NOT re-load Chart in this view.
    // set pixel sizes (Chart.js requires real pixel sizes to draw correctly)

 

   const STATE = {
  chartSpark: null,
  chartCurrentSpark: null,
  chartDonut: null,
  pageSize: 50,
  currentPage: 1,

  allCustomers: [],
  filteredCustomers: [],
  originalOrder: [],   // ✅ ADD THIS

  allRowsByName: {},
  customersList: [],

  sortState: {         // ✅ ADD THIS
    key: null,
    dir: null          // 'asc' | 'desc' | null
  }
};

// ✅ ADD THIS LINE HERE
let sparkFetchController = null;

    // ---------- Parse embedded payload safely ----------
    function parsePayload() {
      try { return JSON.parse(rawDataEl.textContent || '{}'); } catch (e) { console.error('Invalid embedded JSON for AR page', e); return {}; }
    }




    // ---------- Charts ----------
    function destroyChart(instance) {
      if (!instance) return;
      try { instance.destroy(); } catch(e) { /* ignore */ }
    }
function initCharts(sums) {
destroyChart(STATE.chartSpark);
destroyChart(STATE.chartCurrentSpark); // ← ADD THIS
destroyChart(STATE.chartDonut);

  // 🔒 ADD THIS GUARD HERE
if (
  !window.AR_SPARK_LABELS ||
  !window.AR_SPARK_LABELS.length ||
  !window.AR_SPARK_VALUES ||
  !window.AR_SPARK_VALUES.length
) {
    console.warn('Sparkline not rendered: missing data');
    return;
  }

  try {
    if (window.Chart) {
      // --- Sparkline (larger points + tooltip) ---
      const ctxS = sparkCanvas?.getContext ? sparkCanvas.getContext('2d') : null;
      if (ctxS) {
        // ensure canvas pixel size matches client size
        sparkCanvas.width = Math.max(300, sparkCanvas.clientWidth);
        sparkCanvas.height = 80;

        STATE.chartSpark = new Chart(ctxS, {
  type: 'line',
  data: {
    labels: window.AR_SPARK_LABELS,
    datasets: [{
      data: window.AR_SPARK_VALUES,
      borderColor: '#0f766e',
      borderWidth: 2,
      fill: true,
      backgroundColor: 'rgba(15,118,110,0.08)',
      pointRadius: 3,
      hoverRadius: 8,
      pointHoverBorderWidth: 2,
      tension: 0.36,
      spanGaps: true
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        enabled: true,
        mode: 'nearest',
        intersect: false,
        callbacks: {
          label: (ctx) => 'AR Balance: ' + formatMoney(ctx.raw || 0)
        }
      }
    },
    interaction: { mode: 'index', intersect: false },
    scales: { x: { display: false }, y: { display: false } }
  }
});

      }
// --- Current (0–29 days) Sparkline ---
const ctxC = currentSparkCanvas?.getContext ? currentSparkCanvas.getContext('2d') : null;

if (ctxC) {
  currentSparkCanvas.width = Math.max(300, currentSparkCanvas.clientWidth);
  currentSparkCanvas.height = 60;
STATE.chartCurrentSpark = new Chart(ctxC, {
  type: 'line',
  data: {
    labels: window.AR_SPARK_LABELS,
datasets: [{
  data: window.CURRENT_SPARK_VALUES,

      borderColor: '#10b981',
      borderWidth: 2.5,
      fill: true,
      backgroundColor: 'rgba(16,185,129,0.12)',

      // 🔥 FIX
      pointRadius: 0,
      pointHoverRadius: 8,
      pointHitRadius: 12,
      tension: 0.35
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'nearest',
      intersect: false
    },
    plugins: {
      legend: { display: false },
tooltip: {
  enabled: true,
  callbacks: {
title: (items) => {
  const idx = items[0]?.dataIndex ?? 0;
  return window.AR_SPARK_LABELS?.[idx] || 'Month-end';
},
    label: (ctx) => {
      // REAL accounting value (no K/M/B)
      return 'Amount: ' + formatMoney(ctx.raw || 0);
    }
  }
}


    },
    scales: { x: { display: false }, y: { display: false } }
  }
});

}
      // --- Donut (Aging Mix) ---
      const ctxD = donutCanvas?.getContext ? donutCanvas.getContext('2d') : null;
      if (ctxD) {
       // 🔥 allow tooltip to render fully
donutCanvas.width = 200;
donutCanvas.height = 200;



const rawCurrent = sums.current || 0;
const raw30_60   = sums.d30 || 0;
const raw60_90   = sums.d60 || 0;
const raw90_120  = sums.d90 || 0;
const raw120Plus = sums.d120 || 0;

STATE.chartDonut = new Chart(ctxD, {
  type: 'doughnut',
  data: {
    labels: ['Current','30–60','60–90','90–120','120+'],
    datasets: [{
      data: [
        rawCurrent,
        raw30_60,
        raw60_90,
        raw90_120,
        raw120Plus
      ],
      backgroundColor: [
        '#0f766e', // Current
        '#34d399', // 30–60
        '#6ee7b7', // 60–90
        '#a3e635', // 90–120
        '#facc15'  // 120+
      ],
      borderWidth: 0,
      hoverOffset: 6
    }]
  },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%', // slightly smaller hole so slices show more
    plugins: {
  legend: { display: false },
  tooltip: {
    enabled: false,
    external: function(context) {
      const tooltipEl = document.getElementById('agingTooltip');
      const tooltip = context.tooltip;

      if (!tooltip || tooltip.opacity === 0) {
        tooltipEl.style.display = 'none';
        return;
      }

      const label = tooltip.title[0];

const valueMap = {
  'Current': rawCurrent,
  '30–60': raw30_60,
  '60–90': raw60_90,
  '90–120': raw90_120,
  '120+': raw120Plus
};


      const value = valueMap[label] || 0;
     const base =
  sums.net && sums.net > 0
    ? sums.net
    : Math.max(1, sums.total || 0);
const pct = ((value / base) * 100).toFixed(2);

      tooltipEl.innerHTML = `
        <strong>${label}</strong><br>
        Amount: ${formatMoney(value)}<br>
        Share: ${pct}%
      `;

      const rect = context.chart.canvas.getBoundingClientRect();

      tooltipEl.style.left = rect.left + tooltip.caretX + 12 + 'px';
      tooltipEl.style.top  = rect.top  + tooltip.caretY + 12 + 'px';
      tooltipEl.style.transform = 'translateY(-50%)';

      tooltipEl.style.display = 'block';
    }
  }
},
            interaction: { mode: 'nearest', intersect: true }
          }
        });
      }
    } else {
      console.warn('Chart.js is not loaded; charts will not be shown.');
    }
  } catch (err) {
    console.error('Error initializing charts', err);
  }
}



function updateKPIsAndCharts(sums) {
  STATE.lastSums = sums;
  if (!sums) sums = { total:0, current:0, d30:0, d60:0, d90:0, d120:0 };

const displayCurrent = sums.current || 0;

const safeTotal =
  sums.net !== undefined
    ? sums.net
    : (sums.total || 0);
if (kpiTotal) kpiTotal.textContent = formatBig(safeTotal);

  if (kpiCurrent) {
    kpiCurrent.textContent = formatCompact(displayCurrent);
    kpiCurrent.setAttribute(
      'title',
      `Month-end current: ${formatMoney(displayCurrent)}`
    );
  }

if (kpiCritical)
  kpiCritical.textContent = formatBig(sums.trueOverdue || 0);



const base =
  sums.net && sums.net > 0
    ? sums.net
    : Math.max(1, sums.total || 0);

const pctCurr = base > 0
  ? Math.min(100, (displayCurrent / base) * 100)
  : 0;


const pctCrit = base > 0
  ? Math.min(100, ((sums.trueOverdue || 0) / base) * 100)
  : 0;

  if (progCurrent) progCurrent.style.width = pctCurr + '%';
  if (progCritical) progCritical.style.width = pctCrit + '%';
  if (txtCurrentPct) txtCurrentPct.textContent = pctCurr.toFixed(1) + '%';
}

    // ---------- Table rendering ----------
    function renderTablePage() {
      const total = STATE.filteredCustomers.length;
      const start = (STATE.currentPage - 1) * STATE.pageSize;
      const end = Math.min(start + STATE.pageSize, total);
      const pageRows = STATE.filteredCustomers.slice(start, end);

      if (!pageRows.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No records found.</td></tr>';
        pageInfo.textContent = 'Showing 0 results';
        prevBtn.disabled = true;
        nextBtn.disabled = true;
        return;
      }

      tbody.innerHTML = pageRows.map(c => {
      const d90 = c.d90 || 0;
const d120 = c.Over120 || 0;
        
      return `<tr style="cursor:pointer;" data-customer="${escAttr(c.CustomerName)}">
<td class="text-muted fw-semibold col-agent">
  ${escHtml(c.Agent || '—')}
</td>

<td class="fw-bold text-primary-custom col-customer">
  <span class="cust-name" title="${escAttr(c.CustomerName)}">
    ${escHtml(c.CustomerName)}
  </span>
</td>



<td class="text-muted invoice-cell" title="${escAttr(c.InvoiceTooltip)}">
  <div>
    ${c.OldestInvoiceDate
      ? new Date(c.OldestInvoiceDate).toLocaleDateString('en-US',{
          month:'short', day:'2-digit', year:'numeric'
        })
      : '—'}
  </div>
  <div style="font-size:0.75rem;color:#6b7280;">
    (${c.InvoiceCount} open invoice${c.InvoiceCount!==1?'s':''})
  </div>
</td>

<td class="text-end text-muted">
  ${c.Current ? formatMoney(c.Current) : '-'}
</td>
  <td class="text-end text-muted">${c.d30 ? formatMoney(c.d30) : '-'}</td>
  <td class="text-end text-muted">${c.d60 ? formatMoney(c.d60) : '-'}</td>

 <td class="text-end">
  ${c.d90 > 0 ? formatMoney(c.d90) : '-'}
</td>

  <td class="text-end ${c.Over120 > 0 ? 'text-success-custom fw-bold' : 'text-muted'}">
    ${c.Over120 > 0 ? formatMoney(c.Over120) : '-'}
  </td>

<td class="text-end fw-bold col-overdue"
    title="${c.Overdue > 0 ? formatMoney(c.Overdue) : '—'}">
  ${c.Overdue > 0 ? formatMoney(c.Overdue) : '—'}
</td>

<td class="text-end fw-bold col-pdc"
    title="${c.PDC > 0 ? formatMoney(c.PDC) : '—'}">
  ${c.PDC > 0 ? formatMoney(c.PDC) : '—'}
</td>

<td class="text-center">
  ${c.AvailableDate
    ? new Date(c.AvailableDate).toLocaleDateString('en-US',{
        month:'short', day:'2-digit', year:'numeric'
      })
    : '—'}
</td>

  <!-- ✅ TOTAL BALANCE (NOW ALIGNED) -->
<td class="text-end fw-bold text-dark col-total">
    ${c.Total ? formatMoney(c.Total) : '-'}
  </td>
</tr>`;

      }).join('');

      pageInfo.textContent = `Showing ${start + 1}-${end} of ${total}`;
      prevBtn.disabled = STATE.currentPage <= 1;
      nextBtn.disabled = end >= total;
    }


    // ---------- Events wiring ----------
    function attachEvents(customersList) {
        // ---------- SORTING (ASC → DESC → ORIGINAL) ----------
  document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const key = th.dataset.key;

      // reset arrows on other headers
      document.querySelectorAll('th.sortable').forEach(h => {
        if (h !== th) h.classList.remove('asc', 'desc');
      });

      // cycle: null → asc → desc → null
      if (STATE.sortState.key !== key) {
        STATE.sortState.key = key;
        STATE.sortState.dir = 'asc';
      } else if (STATE.sortState.dir === 'asc') {
        STATE.sortState.dir = 'desc';
      } else {
        STATE.sortState.key = null;
        STATE.sortState.dir = null;
      }

      // update arrow UI
      th.classList.remove('asc', 'desc');
      if (STATE.sortState.dir) th.classList.add(STATE.sortState.dir);

      // 🔄 RESTORE ORIGINAL ORDER
      if (!STATE.sortState.key) {
        STATE.filteredCustomers = STATE.originalOrder.slice();
        STATE.currentPage = 1;
        renderTablePage();
        return;
      }

  STATE.filteredCustomers.sort((a, b) => {
  const va = a[key];
  const vb = b[key];

  // 🔤 STRING SORT (Agent, CustomerName)
  if (typeof va === 'string' || typeof vb === 'string') {
    const sa = (va || '').toString().toUpperCase();
    const sb = (vb || '').toString().toUpperCase();

    return STATE.sortState.dir === 'asc'
      ? sa.localeCompare(sb)
      : sb.localeCompare(sa);
  }

  // 🔢 NUMERIC SORT (amounts)
  const na = Number(va || 0);
  const nb = Number(vb || 0);

  return STATE.sortState.dir === 'asc'
    ? na - nb
    : nb - na;
});


      STATE.currentPage = 1;
      renderTablePage();
    });
  });

      // Pagination
      prevBtn.addEventListener('click', () => { if (STATE.currentPage > 1) { STATE.currentPage--; renderTablePage(); } });
      nextBtn.addEventListener('click', () => {
        const max = Math.ceil(STATE.filteredCustomers.length / STATE.pageSize);
        if (STATE.currentPage < max) { STATE.currentPage++; renderTablePage(); }
      });

      // Row click -> modal
      tbody.addEventListener('click', e => {
        const tr = e.target.closest('tr');
        if (!tr) return;
        const name = tr.dataset.customer;
        if (!name) return;
        document.getElementById('criticalSummary').innerHTML = '';
        const modal = new bootstrap.Modal(document.getElementById('detailModal'));
        document.getElementById('modalTitle').textContent = name;
        const rows = STATE.allRowsByName[name] || [];
        document.getElementById('modalBody').innerHTML = rows.length
          ? rows.map(r => `<tr>
  <td class="ps-4 fw-bold">${escHtml(r.Invoice || '-')}</td>
  <td>${escHtml((r.InvoiceDate || '').substring(0,10))}</td>
  <td class="text-muted">${escHtml(r.Terms || r.Description || '—')}</td>
<td class="text-end text-muted">
  ${formatMoney(
    r.InvAmount ||
    r.OrigInvValue ||
    r.Amount ||
    0
  )}
</td>

<td class="text-end fw-bold">
  ${formatMoney(r.Total || r.Balance || 0)}
</td>

<td class="text-end text-success-custom fw-bold">
  ${Number(r.PDC || 0) > 0 ? formatMoney(r.PDC) : '—'}
</td>

<td>
  ${r.AvailableDate
    ? new Date(r.AvailableDate).toLocaleDateString('en-US',{
        month:'short', day:'2-digit', year:'numeric'
      })
    : '—'}
</td>

<td class="text-end text-muted">
  ${r.AgeingDays || 0}d
</td>

</tr>`).join('')
          : '<tr><td colspan="5" class="text-center py-4 text-muted">No invoices found.</td></tr>';
        modal.show();
      });

document.querySelectorAll('.overdue-item').forEach(el => {
  el.addEventListener('click', () => {
    const customer = el.dataset.customer;
    if (!customer) return;

    const overdueTotal = Number(el.dataset.overdue || 0);
    const rows = STATE.allRowsByName[customer] || [];

  
const criticalRows = rows.filter(r => r.IsOverdue === true);


document.getElementById('criticalSummary').innerHTML = `
  <div class="fw-bold text-danger mb-1">Overdue Balance Summary</div>
  <div class="small text-muted">
    This customer has <b>${formatMoney(overdueTotal)}</b>
   invoices overdue beyond their agreed payment terms.
  </div>
`;


    // modal title
document.getElementById('modalTitle').textContent =
  `Overdue Invoices — ${customer}`;

    // invoice rows
    document.getElementById('modalBody').innerHTML =
      criticalRows.length
        ? criticalRows.map(r => `
          <tr>
            <td class="ps-4 fw-bold">${escHtml(r.Invoice || '-')}</td>
            <td>${escHtml((r.InvoiceDate || '').substring(0,10))}</td>
            <td class="text-muted">${escHtml(r.Terms || r.Description || '—')}</td>
           <td class="text-end text-muted">
  ${formatMoney(
    r.InvAmount ||
    r.OrigInvValue ||
    r.Amount ||
    0
  )}
</td>
<td class="text-end fw-bold">
  ${formatMoney(r.Total || r.Balance || 0)}
</td>

<td class="text-end text-success-custom fw-bold">
  ${Number(r.PDC || 0) > 0 ? formatMoney(r.PDC) : '—'}
</td>


<td>
  ${r.AvailableDate
    ? new Date(r.AvailableDate).toLocaleDateString('en-US',{
        month:'short', day:'2-digit', year:'numeric'
      })
    : '—'}
</td>

<td class="text-end text-danger fw-bold">
  ${r.AgeingDays || 0}d
</td>

          </tr>
        `).join('')
        : `<tr>
            <td colspan="6" class="text-center text-muted py-4">
              No critical invoices found
            </td>
          </tr>`;

    new bootstrap.Modal(document.getElementById('detailModal')).show();
  });
});

// ================= OVERDUE CLICK FEATURE =================
const overdueHead = document.getElementById('overdueHead');
const overdueBody = document.getElementById('overdueBody');

STATE.rawPayload = STATE.rawPayload || parsePayload();
const payload = STATE.rawPayload

function getOverdueRows() {
  return (payload.rows || []).filter(r => r.IsOverdue === true);
}

function buildOverdueByCustomer(rows) {
  const map = {};

  rows.forEach(r => {
    const name = r.CustomerName || 'Unknown';

    if (!map[name]) {
      map[name] = {
        name,
        invoices: 0,
        total: 0
      };
    }

    map[name].invoices += 1;
    map[name].total += Number(r.Total || r.Balance || 0);
  });

  return Object.values(map).sort((a,b) => b.total - a.total);
}


function renderCustomerView(data) {

  // ✅ COMPUTE TOTAL
  const totalAmount = data.reduce((sum, c) => sum + c.total, 0);

  overdueHead.innerHTML = `
    <tr>
      <th>Customer</th>
      <th class="text-end">Invoices</th>
      <th class="text-end">Overdue Amount</th>
    </tr>
  `;

  overdueBody.innerHTML = `
    ${data.map(c => `
      <tr>
        <td>${c.name}</td>
        <td class="text-end">${c.invoices}</td>
        <td class="text-end fw-bold">${formatMoney(c.total)}</td>
      </tr>
    `).join('')}

    <!-- ✅ TOTAL ROW -->
    <tr style="border-top:2px solid #000;">
      <td class="fw-bold">TOTAL</td>
      <td></td>
      <td class="text-end fw-bold text-success">
        ${formatMoney(totalAmount)}
      </td>
    </tr>
  `;
}


function renderInvoiceView(rows) {

  const totalAmount = rows.reduce((sum, r) => {
    return sum + Number(r.Total || r.Balance || 0);
  }, 0);

  overdueHead.innerHTML = `
    <tr>
      <th>Customer</th>
      <th>Invoice</th>
      <th>Date</th>
      <th>Payment Terms</th> <!-- ✅ ADD THIS -->
      <th class="text-end">Balance</th>
      <th class="text-end">Age</th>
    </tr>
  `;

  overdueBody.innerHTML = `
    ${rows.map(r => `
      <tr>
        <td>${r.CustomerName}</td>
        <td>${r.Invoice || '-'}</td>
        <td>${(r.InvoiceDate || '').substring(0,10)}</td>
        <td>${r.Terms || r.Description || '—'}</td> <!-- ✅ ADD -->
        <td class="text-end fw-bold">
          ${formatMoney(r.Total || r.Balance)}
        </td>
        <td class="text-end text-danger">
          ${r.AgeingDays}d
        </td>
      </tr>
    `).join('')}

    <!-- ✅ TOTAL ROW -->
    <tr style="border-top:2px solid #000;">
      <td colspan="3" class="fw-bold">TOTAL</td>
      <td class="text-end fw-bold text-success">
        ${formatMoney(totalAmount)}
      </td>
      <td></td>
    </tr>
  `;
}
document.getElementById('btnByCustomer').addEventListener('click', () => {
  const rows = getOverdueRows();
  renderCustomerView(buildOverdueByCustomer(rows));
});

document.getElementById('btnByInvoice').addEventListener('click', () => {
  renderInvoiceView(getOverdueRows());
});
document.getElementById('overdueModal')
  .addEventListener('shown.bs.modal', () => {
    const rows = getOverdueRows();
    renderCustomerView(buildOverdueByCustomer(rows));
  });
document.getElementById('overdueCard').addEventListener('click', () => {
  const rows = getOverdueRows();
  renderCustomerView(buildOverdueByCustomer(rows));

  const modal = new bootstrap.Modal(document.getElementById('overdueModal'));
  modal.show();
});

document.getElementById('btnByCustomer').onclick = () => {
  const rows = getOverdueRows();
  renderCustomerView(buildOverdueByCustomer(rows));
};

document.getElementById('btnByInvoice').onclick = () => {
  const rows = getOverdueRows();
  renderInvoiceView(rows);
};

      // Predictive search (debounced)
      let suggestIndex = -1;
      let currentSuggestions = [];
      function showSuggestions(list) {
        if (!list || !list.length) {
          suggestionsEl.style.display = 'none';
          suggestionsEl.innerHTML = '';
          return;
        }
        suggestionsEl.innerHTML = list.map((it, idx) => `<div class="item" data-idx="${idx}" role="option">${escHtml(it)}</div>`).join('');
        suggestionsEl.style.display = 'block';
      }
      function debounce(fn, ms=180) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }

     const doSearch = debounce(value => {
  const term = String(value || '').toLowerCase().trim();

  if (!term) {
    STATE.filteredCustomers = STATE.allCustomers.slice();
    currentSuggestions = (customersList || []).slice(0, 6);
  } else {
   STATE.filteredCustomers = STATE.allCustomers.filter(c => {
  const customer = (c.CustomerName || '').toLowerCase();
  const agent    = (c.Agent || '').toLowerCase();

  return customer.includes(term) || agent.includes(term);
});

 const agentList = STATE.allCustomers.map(c => c.Agent).filter(Boolean);

currentSuggestions = [...new Set([
  ...(customersList || []),
  ...agentList
])]
  .filter(v => v.toLowerCase().includes(term))
  .slice(0, 10);

  }

  // ✅ reset original order after filtering
  STATE.originalOrder = STATE.filteredCustomers.slice();

  // 🔄 reset sort state after search
  STATE.sortState.key = null;
  STATE.sortState.dir = null;
  document.querySelectorAll('th.sortable').forEach(h =>
    h.classList.remove('asc', 'desc')
  );

  suggestIndex = -1;
  showSuggestions(currentSuggestions);
  STATE.currentPage = 1;
  renderTablePage();
}, 140);


      searchEl.addEventListener('input', e => doSearch(e.target.value));

      searchEl.addEventListener('keydown', e => {
        const items = suggestionsEl.querySelectorAll('.item');
        if (!items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); suggestIndex = Math.min(suggestIndex + 1, items.length - 1); items.forEach(it => it.classList.remove('active')); items[suggestIndex] && items[suggestIndex].classList.add('active'); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); suggestIndex = Math.max(suggestIndex - 1, 0); items.forEach(it => it.classList.remove('active')); items[suggestIndex] && items[suggestIndex].classList.add('active'); }
        else if (e.key === 'Enter') {
          if (suggestIndex >= 0 && currentSuggestions[suggestIndex]) {
            e.preventDefault();
            searchEl.value = currentSuggestions[suggestIndex];
            suggestionsEl.style.display = 'none';
        const val = String(currentSuggestions[suggestIndex]).toLowerCase();

STATE.filteredCustomers = STATE.allCustomers.filter(c => {
  const customer = (c.CustomerName || '').toLowerCase();
  const agent    = (c.Agent || '').toLowerCase();
  return customer.includes(val) || agent.includes(val);
});

            STATE.currentPage = 1;
            renderTablePage();
          } else {
            suggestionsEl.style.display = 'none';
          }
        } else if (e.key === 'Escape') {
          suggestionsEl.style.display = 'none';
        }
      });

      suggestionsEl.addEventListener('click', (e) => {
        const item = e.target.closest('.item');
        if (!item) return;
        const idx = Number(item.dataset.idx);
        const val = currentSuggestions[idx];
        if (!val) return;
        searchEl.value = val;
        suggestionsEl.style.display = 'none';
        const v = val.toLowerCase();

STATE.filteredCustomers = STATE.allCustomers.filter(c => {
  const customer = (c.CustomerName || '').toLowerCase();
  const agent    = (c.Agent || '').toLowerCase();
  return customer.includes(v) || agent.includes(v);
});

        // ✅ ADD THESE LINES HERE
STATE.originalOrder = STATE.filteredCustomers.slice();
STATE.sortState.key = null;
STATE.sortState.dir = null;
document.querySelectorAll('th.sortable').forEach(h =>
  h.classList.remove('asc', 'desc')
);
        STATE.currentPage = 1;
        renderTablePage();
      });

      document.addEventListener('click', (ev) => {
        if (!document.getElementById('searchBox').contains(ev.target)) {
          suggestionsEl.style.display = 'none';
        }
      });

      searchEl.addEventListener('focus', () => {
        const term = (searchEl.value || '').trim();
        if (!term) {
          currentSuggestions = (customersList || []).slice(0,6);
          showSuggestions(currentSuggestions);
        } else {
          currentSuggestions = (customersList || []).filter(n => n.toLowerCase().includes(term.toLowerCase())).slice(0,10);
          showSuggestions(currentSuggestions);
        }
      });

      // ---------- AJAX reload for date filter & form ----------
      const ajaxReload = (url) => {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5">Refreshing data...</td></tr>';
        fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
          credentials: 'same-origin'
        }).then(r => {
          if (!r.ok) throw new Error('Network response was not ok');
          return r.json();
        }).then(data => {
          // rebuild local state from returned rows
          const rows = data.rows || [];
         // 🔒 USE SERVER-AGGREGATED DATA ONLY
STATE.allCustomers =
  (data.meta && data.meta.customersAggregated)
    ? data.meta.customersAggregated
    : [];
STATE.filteredCustomers = STATE.allCustomers.slice();
STATE.originalOrder = STATE.allCustomers.slice();

STATE.sortState = {
  key: null,
  dir: null // 'asc' | 'desc' | null
};
          STATE.allRowsByName = {};
          rows.forEach(r => {
            const n = String(r.CustomerName || r.Customer || r['Customer Name'] || '').trim();
            if (!STATE.allRowsByName[n]) STATE.allRowsByName[n] = [];
            STATE.allRowsByName[n].push(r);
          });
          // update customer suggestions list if provided
          if (Array.isArray(data.customers) && data.customers.length) STATE.customersList = data.customers.slice();
          // UI updates
          STATE.currentPage = 1;
          renderTablePage();
          const sums = data.meta && data.meta.sums ? data.meta.sums : computeSums(rows);
          updateKPIsAndCharts(sums);
          // ensure charts exist
          initCharts(sums);
        }).catch(err => {
          console.error('ajaxReload failed', err);
          tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-5">Error loading data.</td></tr>';
        });
      };

      filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const url = new URL(filterForm.action, window.location.origin);
        new FormData(filterForm).forEach((v,k) => url.searchParams.set(k,v));
        ajaxReload(url.toString());
      });

      if (dateInput) {
        dateInput.addEventListener('change', () => {
          const url = new URL(filterForm.action, window.location.origin);
          new FormData(filterForm).forEach((v,k) => url.searchParams.set(k,v));
          ajaxReload(url.toString());
        });
      }
    }
function rebuildSparklines() {
  if (!window.Chart) return;

  destroyChart(STATE.chartSpark);
  destroyChart(STATE.chartCurrentSpark);
if (!window.AR_SPARK_LABELS.length || !window.AR_SPARK_VALUES.length) {
  console.warn('Sparkline skipped: empty data');
  return;
}

initCharts(STATE.lastSums || {
  total: 0,
  current: 0,
  d30: 0,
  d60: 0,
  d90: 0,
  d120: 0
});


}

    // ---------- Initialization from embedded data ----------
    (function init() {
      const payload = parsePayload();
const sparkYearSelect = document.getElementById('sparkYear');
if (sparkYearSelect) {
  sparkYearSelect.dataset.current = sparkYearSelect.value;
}

      
     const sums = payload.meta?.sums || {};
window.AR_SPARK_LABELS =
  payload.meta && payload.meta.sparkLabels
    ? payload.meta.sparkLabels
    : [];

window.AR_SPARK_VALUES =
  payload.meta && payload.meta.sparkValues
    ? payload.meta.sparkValues
    : [];

window.CURRENT_SPARK_VALUES =
  payload.meta && Array.isArray(payload.meta.currentSpark)
    ? payload.meta.currentSpark
    : [];


// 🔒 keep last known sums for rebuilds
STATE.lastSums = sums;

 // aggregate & state
STATE.allCustomers =
  payload.meta?.customersAggregated || [];

STATE.filteredCustomers = STATE.allCustomers.slice();
STATE.originalOrder = STATE.allCustomers.slice();

STATE.allRowsByName = {};

const rows = payload.rows || []; // ✅ ADD THIS LINE

rows.forEach(r => {
        const n = String(r.CustomerName || r.Customer || r['Customer Name'] || '').trim();
        if (!STATE.allRowsByName[n]) STATE.allRowsByName[n] = [];
        STATE.allRowsByName[n].push(r);
      });

      // customers list (for suggestions) - prefer server-provided
const customersList = Array.isArray(payload.customers) && payload.customers.length
  ? payload.customers.slice()
  : (payload.meta?.customersAggregated || []).map(c => c.CustomerName);
      STATE.customersList = Array.from(new Set(customersList));

     updateKPIsAndCharts(sums);
initCharts(sums);



if (sparkYearSelect) {
  sparkYearSelect.addEventListener('change', () => {
    const year = sparkYearSelect.value;

    sparkYearSelect.dataset.current = year;

    const url = new URL(window.location.href);
    url.searchParams.set('sparkYear', year);
    url.searchParams.set('_ts', Date.now());

    if (sparkFetchController) {
      sparkFetchController.abort();
    }
    sparkFetchController = new AbortController();

    fetch(url.toString(), {
      signal: sparkFetchController.signal,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'X-Spark-Only': '1'
      },
      credentials: 'same-origin'
    })
      .then(r => r.json())
      .then(data => {
        if (!data.meta) return;
// ✅ UPDATE "AS OF" DATE LABEL
if (kpiAsOf && data.meta.asOfDate) {
  const d = new Date(data.meta.asOfDate);
  kpiAsOf.textContent =
    'As of ' +
    d.toLocaleDateString('en-US', {
      month: 'short',
      day: '2-digit',
      year: 'numeric'
    });
}

        window.AR_SPARK_LABELS = data.meta.sparkLabels || [];
    window.AR_SPARK_VALUES = (data.meta.sparkValues || [])
  .map(v => Math.max(0, Number(v)));

window.CURRENT_SPARK_VALUES = (data.meta.currentSpark || [])
  .map(v => Math.max(0, Number(v)));

if (data.meta.sums) {
  updateKPIsAndCharts(data.meta.sums);
}
        rebuildSparklines();
      })
      .catch(err => {
        if (err.name !== 'AbortError') {
          console.error('Spark year fetch failed', err);
        }
      });
  });
}



      // attach events with the suggestions source
      attachEvents(STATE.customersList);

      // render table page
      renderTablePage();
      
    })();

  }); // DOMContentLoaded
</script>
@endpush
