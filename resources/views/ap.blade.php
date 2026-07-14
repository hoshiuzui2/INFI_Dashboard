  {{-- resources/views/ap.blade.php --}}
  @extends('layouts.app')

  @section('title','Accounts Payable')

  @section('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <style>
  :root{
    --ap-black: #0b0b0b;
    --ap-red: #d6453a;
    --ap-red-mid: #ff8f83;
    --ap-red-soft: #ffd6d1;
    --ap-dark: #1f2937;
    --ap-muted: #6b7280;
    --ap-card: #ffffff;
    --ap-border: #eef2f3;
    --ap-bg: #fbfcfd;
  }
  body { background: var(--ap-bg); color: var(--ap-dark); font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, Arial; }
  .container-fluid { max-width: 1400px; margin: 0 auto; }

  /* KPI row */
  .kpi-row { margin-bottom: .9rem; }
  .kpi-card { background: var(--ap-card); border: 1px solid var(--ap-border); border-radius: 10px; padding: 12px; box-shadow: 0 6px 18px rgba(11,11,11,0.03); height:100%; display:flex; flex-direction:column; justify-content:space-between; position:relative; }
  .kpi-label { font-size: .72rem; font-weight:700; color:var(--ap-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:6px; }
  .kpi-value { font-size: 1.6rem; font-weight:800; color:var(--ap-black); line-height:1; letter-spacing:-0.4px; }
  .kpi-sub { font-size:.82rem; color:var(--ap-muted); margin-top:8px; }

  /* ---- KPI canvas auto-fit fixes ---- */
  /* ensure canvases fill their parent KPI area and resize cleanly */
  .kpi-area-wrap, .kpi-mini-wrap, .kpi-critical-wrap, .kpi-aging-canvas {
    width: 100%;
    box-sizing: border-box;
  }

  /* keep earlier heights but allow responsive scaling */
  .kpi-area-wrap { height:56px; margin-top:8px; }
  .kpi-mini-wrap { height:36px; margin-top:6px; }
  .kpi-critical-wrap { height:56px; margin-top:8px; } /* same height as total outstanding area for visual consistency */
  .kpi-aging-canvas { width:120px; height:120px; }

  /* ensure the canvas element itself expands to parent */
  .kpi-area-canvas, .kpi-mini-chart, .kpi-critical-canvas {
    width: 100% !important;
    height: 100% !important;
    display:block;
  }

  /* Chart.js will follow the canvas element size if maintainAspectRatio:false is used */


  /* small year/month selector in KPI - compact */
  .kpi-controls { display:flex; gap:8px; align-items:center; margin-top:6px; }

  /* Beautiful compact parameter buttons - smaller, rounded and under the chart */
  .kpi-controls select,
  .kpi-controls button,
  .kpi-controls input[type="date"] {
    border-radius: 18px;
    padding: 4px 10px;
    border: 1px solid #e6e6e6;
    background: #ffffff;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ap-dark);
    height: 30px;
    min-width: 58px;
    transition: all .12s ease-in-out;
  }

  /* Slight hover effect */
  .kpi-controls select:hover,
  .kpi-controls button:hover {
    border-color: var(--ap-red);
    color: var(--ap-red);
    background: #fffaf9;
  }

  /* smaller variant when placed under charts */
  .kpi-controls.kpi-controls-under {
    justify-content: flex-start;
    gap: 6px;
    margin-top: 8px;
  }

  /* Make the Go button subdued (outline) so it doesn't dominate */
  .kpi-controls.kpi-controls-under button {
    background: var(--ap-red);
    color: #fff;
    padding: 4px 10px;
    height: 30px;
    border-radius: 16px;
    font-size: 0.78rem;
    font-weight:700;
  }

  /* ===== compact KPI controls placed under chart ===== */
  /* smaller, rounded, pill controls for when placed under charts */
  .kpi-controls.kpi-controls-under {
    justify-content: flex-start;
    gap: 8px;
    margin-top: 8px;
    padding-top: 6px;
  }

  /* inputs/selects/buttons inside compact controls */
  .kpi-controls.kpi-controls-under select,
  .kpi-controls.kpi-controls-under button,
  .kpi-controls.kpi-controls-under input[type="date"] {
    border-radius: 16px;
    padding: 4px 10px;
    border: 1px solid #e6e6e6;
    background: #ffffff;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ap-dark);
    height: 30px;
    min-width: 56px;
    transition: all .12s ease-in-out;
  }

  /* stronger style for the Go button */
  .kpi-controls.kpi-controls-under button {
    background: var(--ap-red);
    color: #fff;
    border: 0;
    padding: 4px 10px;
    border-radius: 16px;
    font-weight:700;
    height:30px;
  }

  /* hover */
  .kpi-controls.kpi-controls-under select:hover,
  .kpi-controls.kpi-controls-under button:hover {
    border-color: var(--ap-red);
    color: var(--ap-red);
    background: #fffaf9;
  }

  /* stacked KPI header layout: label above value (matches Critical card) */
  .kpi-header-left {
    display:flex;
    flex-direction:column;
    gap:6px;
    align-items:flex-start;
  }
  .kpi-card .kpi-label { margin:0; }
  .kpi-card .kpi-value { margin:0; }

  /* small mini spark canvas for 'Current' KPI */
  .kpi-mini-wrap { margin-top:6px; width:100%; height:36px; display:block; }
  .kpi-mini-chart { width:100%; height:36px; display:block; }

  /* Aging donut area */
  .kpi-aging-area { display:flex; gap:12px; align-items:center; justify-content:flex-start; padding:6px 0; }
  .kpi-aging-canvas { width:120px; height:120px; display:flex; align-items:center; justify-content:center; }
  .kpi-legend-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:8px; vertical-align:middle; }


  /* Left column cards */
  .table-card { background: var(--ap-card); border: 1px solid var(--ap-border); border-radius: 10px; overflow: hidden; box-shadow: 0 6px 18px rgba(11,11,11,0.03); display:flex; flex-direction:column; }
  .table-card .table-header { padding: 10px 12px; border-bottom: 1px solid var(--ap-border); display:flex; align-items:center; justify-content:space-between; gap:8px; background:transparent; }
  .table-card .card-body { padding: 10px; }

  /* Top vendors compactness - cleaner layout */
  .vendor-item { display:flex; justify-content:space-between; gap:10px; align-items:flex-start; padding:8px 6px; border-bottom:1px dashed #f6f7f8; }
  .vendor-item .meta { flex:1 1 auto; min-width:0; }
  .vendor-item .meta .title { font-weight:700; color:var(--ap-black); font-size:.88rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .vendor-item .meta .sub { font-size:.76rem; color:var(--ap-muted); margin-top:4px; }
  .vendor-item .amount { text-align:right; min-width:100px; font-weight:800; }

  /* controls */
  .controls { display:flex; gap:8px; align-items:center; }
  .search-box { position:relative; width:220px; max-width:28vw; }
  .search-box input { width:100%; padding:8px 10px 8px 36px; border-radius:8px; border:1px solid var(--ap-border); background:#fff; font-size:.92rem; }
  .search-box i{ position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--ap-muted); pointer-events:none; }
  .suggestions{ position:absolute; left:0; right:0; top:40px; background:#fff; border:1px solid var(--ap-border); border-radius:8px; max-height:260px; overflow:auto; z-index:9999; display:none; box-shadow:0 10px 24px rgba(11,11,11,0.06); }
  .export-group { display:flex; gap:8px; align-items:center; }

/* Match height with Upcoming Payments card */
.table-card.h-100 {
    display: flex;
    flex-direction: column;
    height: auto;
    max-height: 1182px;
    overflow: hidden;
}

.table-responsive {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
}

.table-footer {
    margin-top: auto;
    flex-shrink: 0;
}
  /* table */
  .table { border-collapse:collapse; font-size:.92rem; width:100%; }
  /* reduced header padding and tighter rows */
  .table thead th{

    position:sticky;

    top:0;

    background:#fff;

    color:var(--ap-muted);

    font-weight:700;

    padding:12px 10px;

    border-bottom:2px solid #eef2f3;

    vertical-align:middle;

    white-space:nowrap;

    z-index:10;
}
  .table thead th.critical { color: var(--ap-red); }
  .table tbody tr { border-bottom:1px solid #f4f6f7; }
  .table tbody tr:last-child { border-bottom: 0; }
  .table td{

    padding:10px 10px;

    vertical-align:middle;

    line-height:1.35;
}
  .cust-name { display:inline-block; max-width:420px; white-space:normal; word-wrap:break-word; overflow:hidden; text-overflow:ellipsis; font-weight:800; color:var(--ap-black); }
  .table td.text-end { text-align:right; font-variant-numeric: tabular-nums; }
  .val-normal { color: var(--ap-black); font-weight:700; }
  .val-muted { color: var(--ap-muted); }
  /* make critical values stand out in KPI and table - stronger specificity */
  #kpiTotal,
  .table td.text-end.val-critical,
  .val-critical {
    color: var(--ap-red) !important;
    font-weight: 800;
  }



  /* ---- Improved KPI chart visuals ---- */
  .kpi-area-wrap { height:42px !important; } /* smaller height */
  .kpi-area-canvas { opacity:0.9; }

  .kpi-mini-chart,
  .kpi-critical-canvas {
    opacity:0.9;
  }

  /* Soft shadow on all KPI charts */
  .kpi-area-canvas,
  .kpi-mini-chart,
  .kpi-critical-canvas {
    filter: drop-shadow(0px 1px 2px rgba(0,0,0,0.10));
  }

  /* KPI card with clean soft border + red top line */
  .kpi-card {
    border: 1px solid var(--ap-border) !important;
    border-top: 4px solid var(--ap-red) !important;
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(11,11,11,0.03) !important;
    background: #fff;
  }
  /* New: stacked KPI header layout (label above value) */
  .kpi-header-left {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
  }

  /* Ensure other KPI cards keep compact spacing */
  .kpi-card .kpi-label { margin: 0; }
  .kpi-card .kpi-value { margin: 0; }



  /* footer pinned small */
  .table-footer{

    padding:8px;

    border-top:1px solid var(--ap-border);

    display:flex;

    justify-content:space-between;

    align-items:center;

    background:transparent;

    flex-shrink:0;

    margin-top:0;

}
  .btn-outline-small { border-radius:8px; padding:6px 10px; border:1px solid var(--ap-border); background:#fff; color:var(--ap-dark); }

  /* buttons */
  .btn-refresh { background: var(--ap-red); color:#fff; border:0; padding:7px 10px; border-radius:8px; font-weight:700; }
  .btn-export { border-radius:8px; padding:6px 10px; border:1px solid var(--ap-border); background:#fff; color:var(--ap-dark); display:flex; gap:8px; align-items:center; }
  .modal-header { background:var(--ap-black); color:#fff; border-bottom:0; }

  th.sortable {
    cursor: pointer;
    user-select: none;
  }

  th.sortable::after {
    content: " ⇅";
    font-size: 0.7rem;
    color: var(--ap-muted);
  }

  th.sortable.asc::after {
    content: " ↑";
    color: var(--ap-red);
  }

  th.sortable.desc::after {
    content: " ↓";
    color: var(--ap-red);
  }
  /* responsive */
  @media (max-width:1100px) { .search-box { width:180px; } .cust-name { max-width:220px; } .kpi-mini-wrap { display:none; } .kpi-aging-canvas { width:100px; height:100px; } }
  @media (max-width:768px) { .kpi-value { font-size:1.2rem; } .controls { flex-direction:column; gap:8px; align-items:stretch; } .table-responsive { max-height:380px; } }
  </style>
  @endsection

  @section('content')
  @php
  $payloadArr = [];
  if (!empty($jsonPayload)) {
      $payloadArr = json_decode($jsonPayload, true) ?: [];
  }
  $rawRows = $payloadArr['rows'] ?? [];
  // prefer server-provided supplier aggregates; otherwise aggregate invoice rows
  $supplierList = $payloadArr['suppliers'] ?? [];
  $sums = $payloadArr['meta']['sums'] ?? ['total'=>0,'current'=>0,'d30'=>0,'d60'=>0,'d90'=>0,'d120'=>0];
  $topVendors = $payloadArr['topVendors'] ?? array_slice($supplierList, 0, 6);

  // simple compact format used server side
  function compact_display($n){
      if (!$n || floatval($n) == 0) return '-';
      if (abs($n) >= 1000000) {
          $m = round($n / 1000000);
          return '₱' . number_format($m, 0) . 'M';
      }
      if (abs($n) >= 1000) {
          $k = round($n / 1000);
          return '₱' . number_format($k, 0) . 'K';
      }
      return '₱' . number_format($n,2);
  }
  @endphp

  <script id="ap-raw-data" type="application/json">{!! json_encode($payloadArr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>

  <div class="container-fluid px-4 py-4">
    <!-- KPI row -->
    <div class="row g-3 kpi-row">
      <div class="col-lg-3 col-md-6">
        <div class="kpi-card">
          <div>
      <div class="kpi-header-left">
    <div class="kpi-label mb-0">Total Outstanding</div>
    <div id="kpiTotal" class="kpi-value val-critical" data-raw="{{ $sums['total'] ?? 0 }}"></div>

  </div>



        {{-- Area chart for trend --}}
  <div class="kpi-area-wrap"><canvas id="kpiArea" class="kpi-area-canvas" aria-hidden="true"></canvas></div>
  <!-- moved smaller parameter controls under the chart -->
  <form id="kpiDateForm" action="{{ route('ap.index') }}" method="GET" class="kpi-controls kpi-controls-under" style="margin-top:8px;">
    @csrf
    <input type="hidden" name="dateTo" id="kpiDateTo" value="{{ $dateTo ?? date('Y-m-d') }}">
    <select id="kpiYear" name="year"></select>
    <select id="kpiMonth" name="month"></select>
    <button type="submit" class="btn-outline-small">Go</button>
  </form>


          </div>
          <div class="kpi-sub">Total Accounts Payable balance</div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
        <div class="kpi-card">
          <div>
            <div class="kpi-label">Current (0–29 days)</div>
  <div
    id="kpiCurrent"
    class="kpi-value val-muted"
    data-raw="{{ $sums['current'] ?? 0 }}"
    title="₱{{ number_format($sums['current'] ?? 0, 2) }}"
  >
  </div>
            <div class="kpi-mini-wrap"><canvas id="miniCurrent" class="kpi-mini-chart"></canvas></div>
          </div>
          <div class="kpi-sub">Healthy</div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6">
    <div class="kpi-card">
      <div>
        <div class="d-flex flex-column">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <div class="kpi-label">Critical (&gt;90 days)</div>
              <div id="kpiCritical" class="kpi-value val-critical" data-raw="{{ (($sums['d90'] ?? 0) + ($sums['d120'] ?? 0)) }}">{{ (($sums['d90'] ?? 0) + ($sums['d120'] ?? 0)) ? '₱'.number_format(($sums['d90']+$sums['d120']),2) : '₱0.00' }}</div>
            </div>
          </div>

          <!-- small chart area for critical KPI (auto-fit via CSS) -->
          <div class="kpi-critical-wrap" style="margin-top:8px;">
            <canvas id="kpiCriticalChart" class="kpi-critical-canvas" aria-hidden="true"></canvas>
          </div>
        </div>
      </div>
      <div class="kpi-sub">Requires immediate action</div>
    </div>
  </div>


      <div class="col-lg-3 col-md-6">
        <div class="kpi-card">
          <div class="kpi-label">Aging Mix</div>
          <div class="kpi-sub">Portfolio composition</div>
          <div class="kpi-aging-area">
            <div class="kpi-aging-canvas"><canvas id="kpiAgingDonut"></canvas></div>
            <div style="min-width:110px;">
              <div style="font-size:0.92rem; color:var(--ap-muted); line-height:1.8;">
                <div>
    <span class="kpi-legend-dot"
  style="background:#2563eb"></span>
    Current (0–29)
  </div>
  <div>
    <span class="kpi-legend-dot"
  style="background:#22c55e"></span>
    30–59
  </div>
  <div>
    <span class="kpi-legend-dot"
  style="background:#facc15"></span>
    60–89
  </div>
  <div>
  <span class="kpi-legend-dot"
  style="background:#ef4444"></span>
    90+ / 120+
  </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- main row -->
    <div class="row g-4">
      <div class="col-xl-3 col-lg-4">
        <div class="table-card mb-3">
          <div class="table-header">
            <div class="fw-bold">Top 6 Vendors</div>
            <div class="badge-count">{{ count($topVendors) }}</div>
          </div>
          <div class="card-body">
            <canvas id="topVendorsBar" width="300" height="220"></canvas>

            {{-- compact vendor list under chart (clean spacing, formatted values) --}}
            <div style="margin-top:6px;">
              @php
  $topVendors = collect($topVendors)
      ->sortByDesc('Total')
      ->values()
      ->all();
  @endphp
            @foreach($topVendors as $it)
  @php
    $overdue =
      ($it['d30'] ?? 0)
    + ($it['d60'] ?? 0)
    + ($it['d90'] ?? 0)
    + ($it['d120'] ?? 0);
  @endphp

  <div class="vendor-item critical-vendor"
      data-supplier="{{ $it['SupplierName'] }}"
      title="View critical invoices"
      style="cursor:pointer;">

    <div class="meta">
      <div class="title" title="{{ $it['SupplierName'] }}">
        {{ \Illuminate\Support\Str::limit($it['SupplierName'], 48) }}
      </div>
      <div class="sub">
        Inv: {{ count($it['invoices'] ?? []) }} invoices
      </div>
    </div>

    <div class="amount">
      <div class="val-normal" data-raw="{{ $it['Total'] }}">
        {{ $it['Total'] ? '₱' . number_format($it['Total'],2) : '-' }}
      </div>

      <div class="small text-muted">
        Overdue:
        <span class="val-critical">
          {{ $overdue > 0 ? '₱'.number_format($overdue,2) : '-' }}
        </span>
      </div>
    </div>

  </div>
  @endforeach

  </div>
      </div>
    </div>

        <div class="table-card">
          <div class="table-header">
            <div class="table-header">
    <div class="fw-bold">Upcoming Payments (30d)</div>

    <button
        class="btn btn-sm btn-outline-success"
        onclick="exportUpcomingPaymentsExcel()"
    >
        <i class="bi bi-file-earmark-excel"></i>
        Excel
    </button>
</div>
          </div>
          <div class="card-body" style="max-height:300px; overflow:auto;">
            @php
              $upcoming = [];
              $today = new DateTimeImmutable(date('Y-m-d'));
              foreach ($rawRows as $r) {
                  if (!empty($r['DueDate'])) {
                      try {
                          $d = new DateTimeImmutable(substr($r['DueDate'],0,10));
                          // $diff = (int)($d->diff($today)->format('%r%a'));
                          $diff = (int)($today->diff($d)->format('%r%a'));
                          if ($diff >= 0 && $diff <= 30) {
                              $upcoming[] = ['Supplier'=>$r['SupplierName'] ?? $r['Supplier'] ?? '', 'Due'=>$d->format('Y-m-d'), 'Amount'=>floatval($r['MthInvBal1'] ?? $r['OrigInvValue'] ?? 0), 'Invoice'=>$r['Invoice'] ?? ''];
                          }
                      } catch (\Exception $e) {}
                  }
              }
              usort($upcoming, fn($a,$b)=> strcmp($a['Due'],$b['Due']));
              $upcoming = array_slice($upcoming,0,10);
            @endphp

            @if(empty($upcoming))
              <div class="text-center text-muted py-4">No payments due in next 30 days</div>
            @else
              @foreach($upcoming as $u)
                <div class="upcoming-item">
                  <div style="min-width:0;">
                    <div style="font-weight:700;">{{ $u['Supplier'] }}</div>
                    <div class="small text-muted">Inv {{ $u['Invoice'] }} • Due {{ $u['Due'] }}</div>
                  </div>
                  <div style="text-align:right; min-width:110px;">
                    <div class="val-normal" data-raw="{{ $u['Amount'] }}">{{ $u['Amount'] ? '₱'.number_format($u['Amount'],2) : '-' }}</div>
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
              <div class="fw-bold">Detailed Supplier Aging</div>
              <span class="badge-count">{{ count($supplierList) }}</span>
            </div>

            <div class="controls">
              <div class="search-box" id="searchBox">
                <i class="bi bi-search"></i>
                <input id="tableSearch" type="text" placeholder="Search supplier..." autocomplete="off" aria-label="Supplier search" />
                <div id="suggestions" class="suggestions" role="listbox" aria-label="Supplier suggestions"></div>
              </div>

              <form id="filterForm" action="{{ route('ap.index') }}" method="GET" class="d-flex align-items-center gap-2 m-0">
                <input type="date" name="dateTo" value="{{ $dateTo ?? date('Y-m-d') }}" class="form-control form-control-sm" style="border-radius:8px; width:150px;">
                <div class="export-group">
                  <button type="button" id="btnExportCsv" class="btn-export" title="Export to Excel">
  <i class="bi bi-file-earmark-excel" style="color:#22c55e"></i>
  <span style="font-weight:700">Excel</span>
</button>
                  <button type="submit" class="btn-refresh">Refresh</button>
                </div>
              </form>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table mb-0">
  <thead>
    <tr>
      <th style="width:34%">Supplier Name</th>

      <th class="text-end sortable" data-key="Current">Current</th>
      <th class="text-end sortable" data-key="d30">30 Days</th>
      <th class="text-end sortable" data-key="d60">60 Days</th>
      <th class="text-end sortable" data-key="d90">90 Days</th>
      <th class="text-end sortable critical" data-key="d120">120+ Days</th>
      <th class="text-end sortable" data-key="PDC">PDC</th>
      <th class="text-end sortable" data-key="Total">Total Balance</th>
    </tr>
  </thead>
              <tbody id="tableBody">
                @forelse($supplierList as $c)
                  @php
    $critVal = ($c['d90'] ?? 0) + ($c['d120'] ?? 0);
  @endphp
                  <tr data-supplier="{{ htmlspecialchars($c['SupplierName'], ENT_QUOTES) }}" style="cursor:pointer;">
                    <td>
    <span class="cust-name" title="{{ $c['SupplierName'] }}">
      {{ $c['SupplierName'] }}
    </span>
    <div class="small text-muted">
      Terms: {{ $c['PaymentTerms'] ?? '—' }}
    </div>
  </td>
                  <td class="text-end" data-raw="{{ $c['Current'] ?? 0 }}">
    {{ ($c['Current'] ?? 0) ? '₱'.number_format($c['Current'],2) : '-' }}
  </td>

  <td class="text-end" data-raw="{{ $c['d30'] ?? 0 }}">
    {{ ($c['d30'] ?? 0) ? '₱'.number_format($c['d30'],2) : '-' }}
  </td>

  <td class="text-end" data-raw="{{ $c['d60'] ?? 0 }}">
    {{ ($c['d60'] ?? 0) ? '₱'.number_format($c['d60'],2) : '-' }}
  </td>

  <td class="text-end" data-raw="{{ $c['d90'] ?? 0 }}">
    {{ ($c['d90'] ?? 0) ? '₱'.number_format($c['d90'],2) : '-' }}
  </td>

  <td class="text-end val-critical" data-raw="{{ $c['d120'] ?? 0 }}">
    {{ ($c['d120'] ?? 0) ? '₱'.number_format($c['d120'],2) : '-' }}
  </td>

  <td class="text-end text-muted" data-raw="{{ $c['PDC'] ?? 0 }}">
    {{ ($c['PDC'] ?? 0) ? '₱'.number_format($c['PDC'],2) : '-' }}
  </td>

  <td class="text-end val-normal" data-raw="{{ $c['Total'] ?? 0 }}">
    {{ ($c['Total'] ?? 0) ? '₱'.number_format($c['Total'],2) : '-' }}
  </td>

                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center py-5 text-muted">No data available. Check controller returns $jsonPayload.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="table-footer">
            <span id="pageInfo" class="text-muted small">Showing server-rendered results</span>
            <div class="btn-group" role="group" aria-label="Pagination">
              <button id="prevPage" class="btn-outline-small">Previous</button>
              <button id="nextPage" class="btn-outline-small">Next</button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- modal -->
  <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
       <div class="modal-header">
  <h5 class="modal-title" id="modalTitle">Supplier Details</h5>
  <div class="d-flex gap-2">
    <button type="button" id="btnExportModalExcel" class="btn btn-sm btn-success" title="Export to Excel">
      <i class="bi bi-file-earmark-excel"></i> Excel
    </button>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
</div>
        <div class="modal-body p-0">
          <div class="table-responsive modal-table-body" style="max-height:480px;">
            <table class="table table-striped mb-0">
            <thead class="bg-light">
  <tr>
    <th>Invoice</th>
    <th>Date</th>
    <th class="text-end">Amount</th>
    <th class="text-end">Balance</th>
    <th>Cheque Date</th>
    <th>Payment Terms</th>
  </tr>
  </thead>

              <tbody id="modalBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  @endsection

  @push('scripts')
  <!-- Chart.js CDN (safe to include even if loaded in layout) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // parse raw payload
    const rawEl = document.getElementById('ap-raw-data');
    const payload = JSON.parse(rawEl.textContent || '{}');
    const rows = payload.rows || [];
    const sums = payload.meta?.sums || { total:0, current:0, d30:0, d60:0, d90:0, d120:0 };
    const sparkValues = payload.sparkValues || []; // optional
    const monthsMeta = payload.meta?.months || [];

    // helper
    const escHtml = s => (s===null||s===undefined)?'':String(s).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  function compactKpi(n){
    if (n === null || n === undefined || !isFinite(n))
      return '₱0.00';

    const num = Number(n);
    const abs = Math.abs(num);

    if (abs >= 1_000_000_000)
      return '₱' + (num / 1_000_000_000).toFixed(1) + 'B';

    if (abs >= 1_000_000)
      return '₱' + (num / 1_000_000).toFixed(1) + 'M';

    if (abs >= 1000)
      return '₱' + (num / 1000).toFixed(1) + 'K';

    return '₱' + num.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

    function formatCurrency(val) {
    const n = Number(val);
    if (!isFinite(n) || n === 0) return '-';
    return '₱' + n.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

    // Fill compact KPI values (Total Outstanding)
    const kpiTotalEl = document.getElementById('kpiTotal');
    if (kpiTotalEl) kpiTotalEl.textContent = compactKpi(Number(kpiTotalEl.dataset.raw || sums.total || 0));

    // Fill compact KPI value (Current 0–29 days)
  const kpiCurrentEl = document.getElementById('kpiCurrent');
  if (kpiCurrentEl) {
    const raw = Number(kpiCurrentEl.dataset.raw || sums.current || 0);
    kpiCurrentEl.textContent = compactKpi(raw);
    kpiCurrentEl.title = '₱' + raw.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

    // format critical KPI as currency compact
    const kpiCriticalEl = document.getElementById('kpiCritical');
    if (kpiCriticalEl) {
      const rawCrit = Number(kpiCriticalEl.dataset.raw || ((sums.d90||0)+(sums.d120||0)) || 0);
      kpiCriticalEl.textContent = rawCrit ? compactKpi(rawCrit) : '₱0.00';
    }

    // ---------- KPI Area chart (Total Outstanding) with hover tooltip ----------
    (function initKpiArea(){
      let labels = [];
      let data = [];

      if (Array.isArray(sparkValues) && sparkValues.length) {
        labels = monthsMeta.length === sparkValues.length ? monthsMeta : sparkValues.map((_,i)=>`M${i+1}`);
        data = sparkValues.map(v => Number(v || 0));
      } else if (monthsMeta && monthsMeta.length) {
        labels = monthsMeta.slice();
        const monthlyMap = {};
        monthsMeta.forEach(m=> monthlyMap[m] = 0);
        rows.forEach(r => {
          const invDate = r.InvoiceDate || r.InvDate || null;
          if (!invDate) return;
          try {
            const dt = new Date(invDate);
            const key = dt.getFullYear() + '-' + String(dt.getMonth()+1).padStart(2,'0');
            if (key in monthlyMap) monthlyMap[key] += Number(r.Balance || 0);
          } catch(e) {}
        });
        data = labels.map(l => monthlyMap[l] || 0);
      } else {
        const now = new Date();
        for (let i=5;i>=0;i--){
          const dt = new Date(now.getFullYear(), now.getMonth()-i, 1);
          labels.push(`${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}`);
        }
        data = labels.map(_ => Number(sums.total || 0));
      }

      try {
        const ctx = document.getElementById('kpiArea').getContext('2d');
        window._kpiArea = new Chart(ctx, {
          type: 'line',
          data: {
            labels,
          datasets: [{
    label: 'Total Outstanding',
    data,
    fill: true,
    backgroundColor: 'rgba(214,69,58,0.12)',
    borderColor: '#d6453a',
    tension: 0.45,
    pointRadius: 0,
    borderWidth: 2
  }]

          },
          options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: true, mode: 'index', intersect: false, callbacks: { label: ctx => '₱' + Number(ctx.parsed.y ?? ctx.parsed).toLocaleString() } } },
            interaction: { mode: 'index', intersect: false },
            layout: { padding: { left: 6, right: 6, top: 2, bottom: 2 } },
            scales: { x: { display: false }, y: { display: false } }
          }
        });
      } catch(e){ console.warn('kpi area init failed', e); }
    })();

  (function initMiniCurrent(){
    try {
      const el = document.getElementById('miniCurrent');
      if (!el) return;
      const ctx = el.getContext('2d');

    let data = [];
  let labels = [];

  // Prefer real SQL-backed monthly data
  if (
    Array.isArray(payload.currentSpark) &&
    payload.currentSpark.length &&
    Array.isArray(payload.meta?.months) &&
    payload.meta.months.length === payload.currentSpark.length
  ) {
    data = payload.currentSpark.map(v => Number(v || 0));
    labels = payload.meta.months.map(m => {
      const [y, mo] = m.split('-');
      return new Date(y, mo - 1).toLocaleString('default', {
        month: 'short',
        year: 'numeric'
      });
    });
  } else {
    // fallback: zero data with generic labels
    data = [0, 0, 0, 0, 0, 0];
    labels = ['','','','','',''];
  }


      window._miniCurrent = new Chart(ctx, {
        type: 'line',
      data: {
    labels,
    datasets: [{
      data,
      borderColor: '#ff8f83',
      backgroundColor: 'rgba(255,143,131,0.15)',
      tension: 0.4,
      pointRadius: 0,
      borderWidth: 2,
      fill: true
    }]
  },
    options: {
    maintainAspectRatio: false,

    interaction: {
      mode: 'nearest',
      intersect: false
    },

    scales: {
      x: { display: false },
      y: { display: false }
    },

    plugins: {
      legend: { display: false },

      tooltip: {
        enabled: true,

        // 🔽 Make tooltip compact
        padding: 6,
        caretSize: 4,
        caretPadding: 4,

        // 🔽 Keep tooltip ABOVE the line
        yAlign: 'bottom',
        xAlign: 'center',

        displayColors: false,
        backgroundColor: 'rgba(17, 24, 39, 0.92)',

        titleFont: {
          size: 10,
          weight: '600'
        },

        bodyFont: {
          size: 11,
          weight: '700'
        },

  callbacks: {
    title: (items) => {
      const i = items[0].dataIndex;
      return labels[i] || '0–29 Days';
    },
    label: (ctx) => {
      const v = Number(ctx.parsed?.y ?? 0);
      return '₱ ' + v.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }
  }

      }
    }
  }

      });
    } catch(e){ console.warn('mini current init failed', e); }
  })();

    // ---------- small chart for Critical (>90 days) ----------
    (function initCriticalChart(){
      try {
        const el = document.getElementById('kpiCriticalChart');
        if (!el) return;
        const ctx = el.getContext('2d');
        // build 6-point sparkline for critical values (prefer payload.criticalSpark if provided)
        let data = [];
        if (Array.isArray(payload.criticalSpark) && payload.criticalSpark.length) {
          data = payload.criticalSpark.map(v => Number(v || 0));
        } else {
          // fallback: derive up to 6 points from months or repeat the raw value
          if (Array.isArray(payload.meta?.months) && payload.meta.months.length) {
            const months = payload.meta.months;
            const monthlyMap = {};
            months.forEach(m => monthlyMap[m] = 0);
            rows.forEach(r=>{
              const invDate = r.InvoiceDate || r.InvDate || null;
              if (!invDate) return;
              try {
                const dt = new Date(invDate);
                const key = dt.getFullYear() + '-' + String(dt.getMonth()+1).padStart(2,'0');
                if (key in monthlyMap) monthlyMap[key] += Number(r['90 DAYS'] || r['90DAYS'] || r.d90 || 0) + Number(r['OVER 120 DAYS'] || r.Over120Days || r.d120 || 0);
              } catch(e){}
            });
            data = Object.values(monthlyMap);
            if (!data.length) data = Array(6).fill(Number(document.getElementById('kpiCritical')?.dataset.raw || 0));
          } else {
            data = Array(6).fill(Number(document.getElementById('kpiCritical')?.dataset.raw || 0));
          }
        }

        window._kpiCritical = new Chart(ctx, {
          type: 'line',
          data: { labels: data.map((_,i)=>i+1), datasets: [{ data, borderColor: getComputedStyle(document.documentElement).getPropertyValue('--ap-red').trim() || '#d6453a', fill:false, tension:0.3, pointRadius:0, borderWidth:2 }] },
          options: {
            maintainAspectRatio:false,
            plugins:{ legend:{ display:false }, tooltip:{ enabled:true, callbacks:{ label: ctx => '₱' + Number(ctx.parsed.y ?? ctx.parsed).toLocaleString() } } },
            scales:{ x:{ display:false }, y:{ display:false } },
            interaction:{ mode:'index', intersect:false }
          }
        });
      } catch(e){ console.warn('critical chart init failed', e); }
    })();

    // ---------- Aging donut ----------
    (function initDonut(){
      try {
        const donutCanvas = document.getElementById('kpiAgingDonut');
        const donutCtx = donutCanvas.getContext('2d');
        const donutData = [
    sums.current || 0,
    sums.d30 || 0,
    sums.d60 || 0,
    (sums.d90 || 0) + (sums.d120 || 0)
  ];

  const donutColors = [
    '#2563eb', // Blue - Current
    '#22c55e', // Green - 30-59
    '#facc15', // Yellow - 60-89
    '#ef4444'  // Red - Critical
  ];

        window._apDonut = new Chart(donutCtx, {
          type: 'doughnut',
          data: { labels: [
    'Current (0–29)',
    '30–59 Days',
    '60–89 Days',
    '90+ / 120+ Days'
  ], datasets: [{ data: donutData, backgroundColor: donutColors, borderColor:'#fff', borderWidth:2 }] },
          options: { maintainAspectRatio:false, cutout:'62%', plugins:{ legend:{ display:false }, tooltip: {
    padding: 12,
    displayColors: false,
    callbacks: {
      title: (items) => {
        return items[0].label + ' Aging';
      },
      label: (ctx) => {
        const value = Number(ctx.parsed);
        return [
          'Amount:',
          '₱ ' + value.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          })
        ];
      }
    }
  }
  } }
        });
      } catch(e){ console.warn('donut init failed', e); }
    })();

    // ---------- Top vendors bar (descending - largest first) ----------
    (function initTopVendors(){
      try {
        const topCanvas = document.getElementById('topVendorsBar');
        const topCtx = topCanvas.getContext('2d');
        const tv = (payload.topVendors && payload.topVendors.length) ? payload.topVendors : (payload.suppliers || []).slice(0,6);
        // sort descending so largest first
        const topListDesc = tv.slice().sort((a,b)=> (b.Total||0) - (a.Total||0));
        const labels = topListDesc.map(s => ''); // hide left labels (visual requested)
        const dataVals = topListDesc.map(s => s.Total || 0);


        window._apTopChart = new Chart(topCtx, {
          type:'bar',
          data:{
  labels,
  datasets:[{
    data:dataVals,

    backgroundColor:[
      '#2563eb', // Blue
      '#22c55e', // Green
      '#facc15', // Yellow
      '#ef4444', // Red
      '#8b5cf6', // Purple
      '#f97316'  // Orange
    ],

    borderRadius:10,
    maxBarThickness:16
  }]
  },
          options:{
            indexAxis:'y',
            plugins:{ legend:{ display:false }, tooltip:{ callbacks:{ label: ctx => '₱' + Number(ctx.parsed.x ?? ctx.parsed).toLocaleString() } } },
            scales:{ 
              x:{ 
                ticks:{ 
                  callback: function(v){
                    const val = Number(v);
                    if (!isFinite(val)) return v;
                    if (val >= 1000000) return '₱' + Math.round(val/1000000) + 'M'; // NO decimals
                    if (val >= 1000) return '₱' + Math.round(val/1000) + 'K'; // NO decimals
                    return '₱' + Number(val).toLocaleString();
                  } 
                }, 
                grid:{ display:false } 
              }, 
              y:{ 
                ticks:{ display:false }, // hide left names so the chart is clean
                grid:{ display:false } 
              } 
            },
            layout:{ padding:{ right:6 } }
          }
        });

      } catch(e){ console.warn('top vendors chart failed', e); }
    })();

  

  let supplierList = Array.isArray(payload.suppliers)
    ? payload.suppliers
    : [];
    let filtered = supplierList.slice();
  // preserve original server order
  const originalOrder = supplierList.slice();

  let sortState = {
    key: null,   // column key
    dir: null    // 'asc' | 'desc' | null
  };

    const tbody = document.getElementById('tableBody');
    const pageSize = 50;
    let currentPage = 1;

    function renderTablePage(list = filtered) {
      const total = list.length;
      const start = (currentPage - 1) * pageSize;
      const end = Math.min(start + pageSize, total);
      if (!total) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No records found.</td></tr>';
        document.getElementById('prevPage').disabled = true;
        document.getElementById('nextPage').disabled = true;
        return;
      }
      tbody.innerHTML = list.slice(start,end).map(c=>{
        const crit = (c.d90 || 0) + (c.d120 || 0);
        const critClass = crit>0 ? 'val-critical' : 'val-muted';
    return `<tr data-supplier="${escHtml(c.SupplierName)}" style="cursor:pointer;">
    <td>
      <span class="cust-name">${escHtml(c.SupplierName)}</span>
      <div class="small text-muted">Terms: ${escHtml(c.PaymentTerms || '—')}</div>
    </td>

    <td class="text-end">${formatCurrency(c.Current)}</td>
  <td class="text-end">${formatCurrency(c.d30)}</td>
  <td class="text-end">${formatCurrency(c.d60)}</td>
  <td class="text-end">${formatCurrency(c.d90)}</td>

  <td class="text-end val-critical">
    ${formatCurrency(c.d120)}
  </td>

  <td class="text-end text-muted">
    ${formatCurrency(c.PDC)}
  </td>

  <td class="text-end fw-bold">
    ${formatCurrency(c.Total)}
  </td>

  </tr>`;

      }).join('');
      document.getElementById('prevPage').disabled = currentPage <= 1;
      document.getElementById('nextPage').disabled = end >= total;
      document.getElementById('pageInfo').textContent = `Showing ${start+1}-${end} of ${total}`;
    }

    renderTablePage();
  

   document.querySelectorAll('.critical-vendor').forEach(el => {
  el.addEventListener('click', () => {
    const supplier = el.dataset.supplier;
    if (!supplier) return;

    // ✅ Find supplier from the main supplierList
    const supplierData = supplierList.find(s => s.SupplierName === supplier);
    if (!supplierData) return;

    // ✅ Get invoices from supplierData.invoices (already populated by controller)
    const invoices = supplierData.invoices || [];

    // ✅ GET SELECTED YEAR/MONTH FROM URL
    const urlParams = new URLSearchParams(window.location.search);
    const selectedYear = urlParams.get('year') ? parseInt(urlParams.get('year')) : null;
    const selectedMonth = urlParams.get('month') ? parseInt(urlParams.get('month')) : null;

    // ✅ Filter invoices by year/month if selected
    const filteredInvoices = invoices.filter(inv => {
      if (!inv.InvoiceDate) return false;
      
      const invoiceDate = new Date(inv.InvoiceDate);
      const invoiceYear = invoiceDate.getFullYear();
      const invoiceMonth = invoiceDate.getMonth() + 1;
      
      if (selectedYear !== null && invoiceYear !== selectedYear) {
        return false;
      }
      if (selectedMonth !== null && invoiceMonth !== selectedMonth) {
        return false;
      }
      return true;
    });

    // ✅ MODAL TITLE
    const filterText = selectedYear && selectedMonth 
      ? ` (${selectedYear}-${String(selectedMonth).padStart(2,'0')})`
      : '';
    document.getElementById('modalTitle').textContent =
      `${supplier}${filterText}`;

    // ✅ MODAL TABLE
    document.getElementById('modalBody').innerHTML =
      filteredInvoices.length
        ? filteredInvoices.map(inv => `
            <tr>
              <td class="ps-4 fw-bold">${escHtml(inv.Invoice || '-')}</td>
              <td>${escHtml((inv.InvoiceDate || '').substring(0,10))}</td>
              <td class="text-end text-muted">
                ₱${Number(inv.Amount || 0).toLocaleString()}
              </td>
              <td class="text-end fw-bold">
                ₱${Number(inv.Balance || 0).toLocaleString(undefined, {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2
                })}
              </td>
              <td>${escHtml((inv.ChequeDate || '').substring(0,10))}</td>
              <td>${escHtml(inv.PaymentTerms || '—')}</td>
            </tr>
          `).join('')
        : `<tr>
            <td colspan="5" class="text-center text-muted py-4">
              No invoices found
            </td>
          </tr>`;

    // ✅ SHOW MODAL
    new bootstrap.Modal(
      document.getElementById('detailModal')
    ).show();
  });
});

    document.getElementById('prevPage').addEventListener('click', ()=>{ if (currentPage>1){ currentPage--; renderTablePage(); }});
    document.getElementById('nextPage').addEventListener('click', ()=>{ if ((currentPage*pageSize) < filtered.length){ currentPage++; renderTablePage(); }});

  // modal row click
  tbody.addEventListener('click', (e) => {
    const tr = e.target.closest('tr'); if (!tr) return;
    const name = tr.dataset.supplier; if (!name) return;
    
    // ✅ Find supplier from aggregated list
    const supplierData = supplierList.find(s => s.SupplierName === name);
    if (!supplierData || !supplierData.invoices) {
      alert('No invoice data available for this supplier');
      return;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    document.getElementById('modalTitle').textContent = name;
    
    // ✅ Use the invoices array from supplierData
    const invoices = supplierData.invoices || [];
    
    document.getElementById('modalBody').innerHTML = invoices.length ? 
      invoices.map(inv => `
        <tr>
          <td class="ps-4 fw-bold">${escHtml(inv.Invoice || '-')}</td>
          <td>${escHtml((inv.InvoiceDate || '').substring(0,10))}</td>
          <td class="text-end text-muted">
            ${inv.Amount !== undefined && inv.Amount !== null ? 
              '₱' + Number(inv.Amount).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) : '-'}
          </td>
          <td class="text-end fw-bold">
            ${inv.Balance !== undefined && inv.Balance !== null ? 
              '₱' + Number(inv.Balance).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) : '-'}
          </td>
          <td>${escHtml((inv.ChequeDate || '').substring(0,10))}</td>
          <td>${escHtml(inv.PaymentTerms || '—')}</td>
        </tr>
      `).join('') : 
      '<tr><td colspan="5" class="text-center py-4 text-muted">No invoices found.</td></tr>';
      
    modal.show();
  });

    document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const key = th.dataset.key;

      // reset other headers
      document.querySelectorAll('th.sortable').forEach(h => {
        if (h !== th) h.classList.remove('asc', 'desc');
      });

      // cycle state: null → asc → desc → null
      if (sortState.key !== key) {
        sortState.key = key;
        sortState.dir = 'asc';
      } else if (sortState.dir === 'asc') {
        sortState.dir = 'desc';
      } else if (sortState.dir === 'desc') {
        sortState.key = null;
        sortState.dir = null;
      } else {
        sortState.dir = 'asc';
      }

      // apply UI classes
      th.classList.remove('asc', 'desc');
      if (sortState.dir) th.classList.add(sortState.dir);

      // restore original order
      if (!sortState.key) {
        filtered = originalOrder.slice();
        currentPage = 1;
        renderTablePage();
        return;
      }

      // sort numerically
      filtered.sort((a, b) => {
        const va = Number(a[key] || 0);
        const vb = Number(b[key] || 0);
        return sortState.dir === 'asc' ? va - vb : vb - va;
      });

      currentPage = 1;
      renderTablePage();
    });
  });
    // search + suggestions
    const searchEl = document.getElementById('tableSearch');
    const suggestionsEl = document.getElementById('suggestions');
    let currentSuggestions = [];
    function showSuggestions(list){
      if (!list || !list.length) { suggestionsEl.style.display='none'; suggestionsEl.innerHTML=''; return; }
      suggestionsEl.innerHTML = list.map((it, idx)=>`<div class="item p-2" data-idx="${idx}" role="option">${escHtml(it)}</div>`).join('');
      suggestionsEl.style.display='block';
    }
    function debounce(fn, ms=160){ let t; return (...a)=>{ clearTimeout(t); t = setTimeout(()=>fn(...a), ms); }; }

    const doSearch = debounce(value=>{
      const term = String(value||'').toLowerCase().trim();
      if (!term) {
    filtered = supplierList.slice();
    originalOrder.length = 0;
    originalOrder.push(...filtered);
        currentSuggestions = supplierList.map(s=>s.SupplierName).slice(0,6);
      } else {
        filtered = supplierList.filter(c => c.SupplierName.toLowerCase().includes(term));
  originalOrder.length = 0;
  originalOrder.push(...filtered);
        currentSuggestions = supplierList.map(s=>s.SupplierName).filter(n=>n.toLowerCase().includes(term)).slice(0,10);
      }
      currentPage = 1;
      showSuggestions(currentSuggestions);
      renderTablePage();
    }, 140);
    searchEl.addEventListener('input', e => doSearch(e.target.value));
    searchEl.addEventListener('focus', ()=> {
      const term = (searchEl.value||'').trim();
      if (!term) showSuggestions(supplierList.map(s=>s.SupplierName).slice(0,6));
    });

    suggestionsEl.addEventListener('click', (e) => {
      const item = e.target.closest('.item'); if (!item) return;
      const idx = Number(item.dataset.idx); const val = currentSuggestions[idx];
      searchEl.value = val; suggestionsEl.style.display='none';
      filtered = supplierList.filter(s => s.SupplierName.toLowerCase().includes(val.toLowerCase()));
      currentPage = 1; renderTablePage();
    });
    document.addEventListener('click', (ev) => { if (!document.getElementById('searchBox').contains(ev.target)) suggestionsEl.style.display='none'; });

 // Excel export (CSV format that Excel opens)
function exportCsvFromArray(rows){
  if(!rows || !rows.length){ alert('No rows to export'); return; }
  
const header = [
   'Supplier Name',
   'Current',
   '30 Days',
   '60 Days',
   '90 Days',
   '120+ Days',
   'PDC',
   'Total Balance',
   'Payment Terms'
 ];

 const excelRows = [];

 excelRows.push(header);

 rows.forEach(r=>{

   excelRows.push([

    r.SupplierName || '',

    Number(r.Current || 0),

    Number(r.d30 || 0),

    Number(r.d60 || 0),

    Number(r.d90 || 0),

    Number(r.d120 || 0),

    Number(r.PDC || 0),

    Number(r.Total || 0),

    r.PaymentTerms || ''

   ]);

 });

 const worksheet =
 XLSX.utils.aoa_to_sheet(excelRows);
worksheet['!cols'] = [

 { wch: 35 }, // Supplier

 { wch: 14 }, // Current

 { wch: 14 }, // 30

 { wch: 14 }, // 60

 { wch: 14 }, // 90

 { wch: 14 }, // 120

 { wch: 14 }, // PDC

 { wch: 18 }, // Total

 { wch: 22 }  // Terms

];

const range =
XLSX.utils.decode_range(
 worksheet['!ref']
);

for(
 let R=1;
 R<=range.e.r;
 ++R
){

 for(
  let C=1;
  C<=7;
  ++C
 ){

  const cell =
   worksheet[
    XLSX.utils.encode_cell({
      r:R,
      c:C
    })
   ];

  if(cell){

   cell.z =
   '#,##0.00';

  }

 }

}
 const workbook =
 XLSX.utils.book_new();

 XLSX.utils.book_append_sheet(
  workbook,
  worksheet,
  'AP Aging'
 );

 XLSX.writeFile(
  workbook,
`AP_Aging_Report_${
new Date()
.toISOString()
.slice(0,10)
}.xlsx`
 );

}

document.getElementById(
'btnExportCsv'
).addEventListener(
'click',
()=> exportCsvFromArray(filtered)
);
   // ✅ EXPORT MODAL TO EXCEL
document.getElementById('btnExportModalExcel')?.addEventListener('click', () => {
  const supplierName = document.getElementById('modalTitle').textContent.replace(/\s*\(.*\)\s*$/, '').trim();
  const modalBody = document.getElementById('modalBody');
  const rows = modalBody.querySelectorAll('tr');
  
  if (rows.length === 0) {
    alert('No data to export');
    return;
  }
  
  // Helper function to format currency for Excel
  const formatCurrency = (value) => {
    const num = parseFloat(value.replace(/[^0-9.-]+/g, ''));
    if (isNaN(num)) return '0.00';
    return num.toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };
  
  // Build CSV content
  let csvContent = "data:text/csv;charset=utf-8,";
  
// Header row - KEEP DATE + TERMS
csvContent += "Supplier Name,Invoice,Date,Amount,Balance,Payment Terms\n";

// Data rows
rows.forEach(row => {
  const cells = row.querySelectorAll('td');
  if (cells.length >= 5) {
    const invoice = cells[0].textContent.trim();
    const date = cells[1].textContent.trim();  // Keep date column
    const amount = formatCurrency(cells[2].textContent.trim());
    const balance = formatCurrency(cells[3].textContent.trim());
    const terms = cells[4].textContent.trim();
    
    csvContent += `"${supplierName}","${invoice}","${date}","${amount}","${balance}","${terms}"\n`;
  }
});
  
  // Create download link
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `supplier_invoices_${supplierName.replace(/\s+/g, '_')}_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
});
    // AJAX refresh for date filter (keeps behavior)
    const filterForm = document.getElementById('filterForm');
    filterForm.addEventListener('submit', (e) => {
      if (e.submitter && (e.submitter.matches && e.submitter.matches('.btn-refresh'))) {
        e.preventDefault();
        const url = new URL(filterForm.action, window.location.origin);
        new FormData(filterForm).forEach((v,k)=> url.searchParams.set(k,v));
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Refreshing...</td></tr>';
        fetch(url.toString(), { headers:{ 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' } })
          .then(r => r.json())
          .then(data => {
            const rows = data.rows || [];
            supplierList = Array.isArray(data.suppliers)
    ? data.suppliers
    : [];
            
            filtered = supplierList.slice(); currentPage = 1; renderTablePage();

            const s = data.meta?.sums || {};
            document.getElementById('kpiTotal').textContent = compactKpi(s.total || 0);
            document.getElementById('kpiCurrent').textContent = (s.current || 0) ? compactKpi(s.current) : '---';
            document.getElementById('kpiCritical').textContent = ((s.d90||0)+(s.d120||0)) ? compactKpi((s.d90||0)+(s.d120||0)) : '₱0.00';

            try {
              if (window._apDonut && data.meta?.sums) {
                const S = data.meta.sums;
                window._apDonut.data.datasets[0].data = [
    S.current || 0,
    S.d30 || 0,
    S.d60 || 0,
    (S.d90 || 0) + (S.d120 || 0)
  ];
                window._apDonut.update();
              }
              // update kpi area and mini current if spark data provided (best-effort)
              if (window._kpiArea && data.meta?.months && data.sparkValues) {
                window._kpiArea.data.labels = data.meta.months;
                window._kpiArea.data.datasets[0].data = data.sparkValues;
                window._kpiArea.update();
              }
              if (window._miniCurrent && data.currentSpark) {
                window._miniCurrent.data.datasets[0].data = data.currentSpark;
                window._miniCurrent.update();
              }
            } catch(e){ console.warn('donut/update failed', e); }

          })
          .catch(err => {
            console.error('AP reload failed', err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Error loading data.</td></tr>';
          });
      }
    });

    // --------------------------
    // KPI Year/Month selector logic
    // --------------------------
    (function initKpiDateControls(){
      const yearSel = document.getElementById('kpiYear');
      const monthSel = document.getElementById('kpiMonth');
      const dateToInput = document.getElementById('kpiDateTo');

  const now = new Date();
  const currentYear = now.getFullYear();

  const yearOptions = [];

  for (let y = currentYear; y >= 2022; y--) {
      yearOptions.push(String(y));
  }

      yearSel.innerHTML = yearOptions.map(y => `<option value="${y}">${y}</option>`).join('');
      const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      monthSel.innerHTML = monthNames.map((m,i) => `<option value="${i+1}">${m}</option>`).join('');

      const serverDate = (document.querySelector('input[name="dateTo"]')?.value) || dateToInput.value || '{{ $dateTo ?? date("Y-m-d") }}';
      if (serverDate) {
        try {
          const dt = new Date(serverDate);
          const sYear = String(dt.getFullYear());
          const sMonth = dt.getMonth() + 1;
          if ([...yearSel.options].some(o=>o.value===sYear)) yearSel.value = sYear;
          monthSel.value = sMonth;
        } catch(e) {}
      }

      const kpiForm = document.getElementById('kpiDateForm');
      kpiForm.addEventListener('submit', (e) => {
        const y = Number(yearSel.value);
        const m = Number(monthSel.value);
        const dt = new Date(y, m, 0);
        const yyyy = dt.getFullYear();
        const mm = String(dt.getMonth()+1).padStart(2,'0');
        const dd = String(dt.getDate()).padStart(2,'0');
        dateToInput.value = `${yyyy}-${mm}-${dd}`;
      });
    })();
window.exportUpcomingPaymentsExcel = function(){

    const rows = [];

    document.querySelectorAll(
        '.upcoming-item'
    ).forEach(item=>{

        const supplier =
            item.children[0]
            ?.children[0]
            ?.innerText
            ?.trim();

        const details =
            item.querySelector(
                '.small.text-muted'
            )
            ?.innerText
            ?.trim();

        const amount =
            item.querySelector(
                '.val-normal'
            )
            ?.innerText
            ?.trim();

        if(supplier){

            rows.push({

                Supplier:supplier,

                Details:details,

                Amount:amount

            });

        }

    });

    const ws =
        XLSX.utils.json_to_sheet(rows);

    const wb =
        XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(

        wb,

        ws,

        "Upcoming Payments"

    );

    XLSX.writeFile(

        wb,

        "Upcoming_Payments.xlsx"

    );

}
  }); // DOMContentLoaded
  </script>
  @endpush
