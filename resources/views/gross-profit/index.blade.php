@extends('layouts.app')

@section('title', 'Gross Profit')

@section('content')

<div id="pageLoader" style="
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(255,255,255,.85);
z-index:9999;
display:none;
align-items:center;
justify-content:center;
flex-direction:column;
">

<div class="spinner-border text-success" style="width:3rem;height:3rem"></div>

<div style="margin-top:10px;font-weight:600;color:#0f766e">
Loading data...
</div>

</div>
@php
    $periodLabel = $year ? "Year {$year}" : 'All Time';

   $monthLabels = array_map(function($m){
    return date('M', mktime(0,0,0,$m,1));
}, range(1,12));

    $gpTrendData = array_values($monthlyGpMap);



    $colorPalette = [
    '#3b82f6', // 🔵 Blue
    '#22c55e', // 🟢 Green
    '#eab308', // 🟡 Yellow
    '#ef4444', // 🔴 Red
    '#8b5cf6', // 🟣 Purple
    '#f97316', // 🟠 Orange
];

    $barDatasets = [];
    foreach ($classMargins->keys() as $i => $label) {
        $barDatasets[] = [
            'label' => $label,
            'data' => [ min($classMargins[$label], 100) ],
            'actual' => $classMargins[$label],
            'backgroundColor' => $colorPalette[$i % count($colorPalette)],
            'borderRadius' => 6,
        ];
    }
@endphp

<style>
.kpi-card {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:20px;
    height:130px;
    cursor:pointer;
}
.kpi-label { 
    font-size:.7rem; 
    text-transform:uppercase; 
    color:#64748b; 
}

.kpi-value { 
    font-size:1.6rem; 
    font-weight:700; 
    color:#0f766e; 
}

.kpi-sub {
    font-size: .75rem;
    color: #64748b;
}

.kpi-highlight {
    background:linear-gradient(135deg,#0f766e,#14b8a6);
    color:#fff;
}
.kpi-highlight * { color:#fff; }
.chart-card {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:20px;
}

.chart-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:14px;
}
.chart-title { font-size:.9rem; font-weight:600; }
.chart-filter select {
    font-size:.75rem;
    padding:4px 12px;
    border-radius:999px;
    border:1px solid #e5e7eb;
}
.chart-body { flex:1; position:relative; }
.chart-body canvas { height:260px !important; }

.drill-card {
    transition: all .25s ease;
    border: 1px solid #e5e7eb;
}
.drill-card .kpi-label {
    font-size: 0.65rem;
    letter-spacing: .08em;
    color: #94a3b8;
}

.drill-card .kpi-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: #0f172a;
    margin-top: 4px;
}
.drill-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(0,0,0,.08);
    border-color: #cbd5f5;
}
.customer-row:hover{
background:#f8fafc;
transform:scale(1.01);
transition:.15s;
}
.modal-header{
border-bottom:1px solid #eef2f7;
}

#customerChartContainer{
background:#f9fafb;
padding:18px;
border-radius:10px;
margin-bottom:15px;
}
.chart-filter select{
width:90px;
text-align:center;
}
#productGpModal .modal-body{
padding:22px;
}

#productChartContainer{
background:#f8fafc;
padding:22px;
border-radius:12px;
margin-bottom:20px;
border:1px solid #e5e7eb;
box-shadow:0 4px 12px rgba(0,0,0,.04);
}

#productMonthlyChart{
height:180px !important;
}
.product-row:hover{
background:#f8fafc;
transform:scale(1.01);
transition:.15s;
}

#productGpModal table{
font-size:14px;
}

#productGpModal th{
font-weight:600;
color:#475569;
}

#productGpModal td{
vertical-align:middle;
}

#productGpModal tbody tr:hover{
background:#f8fafc;
}

td.text-end{
white-space:nowrap;
}
th.text-end{
white-space:nowrap;
}

.product-name{
max-width:380px;
white-space:nowrap;
overflow:hidden;
text-overflow:ellipsis;
}
</style>

{{-- ================= KPI SUMMARY ================= --}}
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="kpi-card js-sales-card"
             data-bs-toggle="tooltip"
             data-bs-placement="bottom"
             title="{{ number_format($gpTotalSales, 2) }}">
            <div class="kpi-label">Total Sales</div>
            <div class="kpi-value">{{ formatPeso($gpTotalSales) }}</div>
            <div class="kpi-sub">Click for monthly breakdown</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card js-cogs-card">
            <div class="kpi-label">Total COGS</div>
            <div class="kpi-value">{{ formatPeso($gpTotalCogs) }}</div>
            <div class="kpi-sub">Cost of goods sold</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card js-gp-card"
     data-bs-toggle="tooltip"
     data-bs-placement="bottom"
     title="{{ number_format($gpTotalAmount, 2) }}">
            <div class="kpi-label">Gross Profit</div>
            <div class="kpi-value">{{ formatPeso($gpTotalAmount) }}</div>
            <div class="kpi-sub">Sales minus COGS</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card kpi-highlight">
            <div class="kpi-label">Gross Margin</div>
            <div class="kpi-value">{{ number_format($gpMarginPercent,2) }}%</div>
            <div class="kpi-sub">GP ÷ Sales</div>
        </div>
    </div>

</div>
{{-- ================= EXECUTIVE DRILLDOWN CARDS ================= --}}
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="kpi-card text-center drill-card js-customer-card" data-type="customer">
            <div class="mb-3 text-primary" style="font-size:2rem;">
                <i class="fa-solid fa-user-group"></i>
            </div>
       <div class="kpi-label">CUSTOMERS</div>
<div class="kpi-title">Revenue Performance</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card text-center drill-card" data-type="product">
            <div class="mb-3 text-success" style="font-size:2rem;">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
         <div class="kpi-label">PRODUCTS</div>
<div class="kpi-title">Profitability Analysis</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card text-center drill-card" data-type="agent">
            <div class="mb-3 text-warning" style="font-size:2rem;">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="kpi-label">Agent / Sales Person</div>
            <div class="kpi-title">Agent Performance</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="kpi-card text-center drill-card" data-type="division">
            <div class="mb-3 text-danger" style="font-size:2rem;">
                <i class="fa-solid fa-sitemap"></i>
            </div>
            <div class="kpi-label">Division</div>
            <div class="kpi-title">Division Profitability</div>
        </div>
    </div>

</div>
{{-- ================= SALES MODAL ================= --}}
<div class="modal fade" id="salesBreakdownModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Monthly Sales Breakdown</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(range(1,12) as $m)
                        <tr>
                            <td>{{ date('F', mktime(0,0,0,$m,1)) }}</td>
                            <td class="text-end">{{ number_format($monthlySalesMap[$m] ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td>Total</td>
                            <td class="text-end">{{ number_format($gpTotalSales, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- COGS MODAL --}}
<div class="modal fade" id="cogsBreakdownModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header">
                <h6 class="modal-title fw-semibold">Monthly COGS Breakdown</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-end">COGS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(range(1,12) as $m)
                        <tr>
                            <td>{{ date('F', mktime(0,0,0,$m,1)) }}</td>
                            <td class="text-end">
                                {{ number_format($monthlyCogsMap[$m] ?? 0, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td>Total</td>
                            <td class="text-end">
                                {{ number_format($gpTotalCogs, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
{{-- ================= GP MODAL ================= --}}
<div class="modal fade" id="gpBreakdownModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:14px">
            <div class="modal-header d-flex justify-content-between align-items-center">

    <h5 class="modal-title">Monthly Gross Profit Breakdown</h5>

    <div class="d-flex align-items-center gap-2">

        <!-- ✅ EXPORT BUTTON -->
        <button id="exportMonthlyExcel"
                class="btn btn-success btn-sm d-none">
            <i class="fa fa-file-excel"></i> Export Excel
        </button>

       <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

</div>

            <div class="modal-body">
                <table class="table table-sm">
                    <thead>
@if($isAllYears)
    <tr>
        <th>Year</th>
        <th class="text-end">Sales</th>
        <th class="text-end">COGS</th>
        <th class="text-end">Gross Profit</th>
        <th class="text-end">Margin %</th>
    </tr>
@else
    <tr>
        <th>Month</th>
        <th class="text-end">Sales</th>
        <th class="text-end">COGS</th>
        <th class="text-end">Gross Profit</th>
        <th class="text-end">Margin %</th>
    </tr>
@endif
</thead>
                    <tbody>

@if($isAllYears)

    {{-- ✅ YEAR VIEW --}}
    @foreach($yearlyData as $y)
        <tr>
            <td>{{ $y['year'] }}</td>
            <td class="text-end">{{ formatPeso($y['sales']) }}</td>
            <td class="text-end">{{ formatPeso($y['cogs']) }}</td>
            <td class="text-end fw-semibold text-teal">
                {{ formatPeso($y['gp']) }}
            </td>
            <td class="text-end">
                {{ number_format($y['margin'],2) }}%
            </td>
        </tr>
    @endforeach

@else

    {{-- ✅ MONTH VIEW --}}
    @foreach(range(1,12) as $m)
        @php
            $monthRows = $rows->where('month', $m);

            $sales = $monthRows->sum('totalsales');
            $cogs  = $monthRows->sum('cogs');
            $gp    = $monthRows->sum('gp_amount');

            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;
        @endphp
        <tr>
            <td>{{ date('F', mktime(0,0,0,$m,1)) }}</td>
            <td class="text-end">{{ formatPeso($sales) }}</td>
            <td class="text-end">{{ formatPeso($cogs) }}</td>
            <td class="text-end fw-semibold text-teal">
                {{ formatPeso($gp) }}
            </td>
            <td class="text-end">
                {{ number_format($margin,2) }}%
            </td>
        </tr>
    @endforeach

@endif

</tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td>Total</td>
                            <td class="text-end">{{ formatPeso($gpTotalSales) }}</td>
                            <td class="text-end">{{ formatPeso($gpTotalCogs) }}</td>
                            <td class="text-end">{{ formatPeso($gpTotalAmount) }}</td>
                            <td class="text-end">{{ number_format($gpMarginPercent,2) }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ================= CUSTOMER GP MODAL ================= --}}
<div class="modal fade" id="customerGpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:14px">

      <div class="modal-header">

<div class="d-flex justify-content-between align-items-center w-100">

    <div class="d-flex align-items-center gap-2">
        <button id="customerBackBtn"
            class="btn btn-sm btn-light"
            style="display:none">
            ← Back
        </button>

        <h6 class="modal-title fw-semibold mb-0">
            Customer Gross Profit Breakdown
        </h6>
    </div>

    <div class="d-flex gap-2">
        <button id="exportCustomerExcelLvl1" class="btn btn-sm btn-success">
            <i class="fa fa-file-excel"></i> Export Excel
        </button>

        <button id="exportCustomerExcelLvl2"
            class="btn btn-sm btn-success"
            style="display:none">
            <i class="fa fa-file-excel"></i> Export Monthly
        </button>
    </div>

</div>

<button type="button" class="btn-close" data-bs-dismiss="modal"></button>

</div>
          <div class="modal-body">

<div id="customerChartContainer" style="display:none;margin-bottom:20px;">
    <canvas id="customerMonthlyChart" height="120"></canvas>
</div>

<div class="table-responsive" style="max-height:500px; overflow-y:auto;">

<table class="table table-hover align-middle">

<thead class="table-light">
<tr>
<th style="width:40px">#</th>
<th>Customer / Month</th>
<th class="text-end">Sales</th>
<th class="text-end">COGS</th>
<th class="text-end">Gross Profit</th>
<th class="text-end">Margin</th>
</tr>
</thead>

<tbody id="customerGpBody"></tbody>

</table>

</div>

</div>
           
        </div>
    </div>
</div>

{{-- ================= PRODUCT PROFITABILITY MODAL ================= --}}
<div class="modal fade" id="productGpModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
<div class="modal-content" style="border-radius:16px">

<div class="modal-header d-flex justify-content-between align-items-center">

<div class="d-flex align-items-center gap-2">

<button id="productBackBtn"
class="btn btn-sm btn-light"
style="display:none">
← Back
</button>

<h6 class="modal-title fw-semibold mb-0" id="productModalTitle">
Product Profitability Analysis
</h6>

</div>

<div class="d-flex align-items-center gap-2">

    <button id="exportProductExcel" class="btn btn-sm btn-success">
        <i class="fa fa-file-excel"></i> Export Excel
    </button>

    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

</div>

</div>

<div class="modal-body">

<div id="productChartContainer"
style="display:none;margin-bottom:20px">

<canvas id="productMonthlyChart" height="110"></canvas>

</div>

<div class="table-responsive" style="max-height:520px;overflow-y:auto">

<table class="table table-hover align-middle">

<thead class="table-light">
<tr>
<th style="width:40px">#</th>
<th>Product</th>
<th class="text-end">Sales</th>
<th class="text-end">COGS</th>
<th class="text-end">Gross Profit</th>
<th class="text-end">Margin %</th>
<th class="text-end">Share %</th>
</tr>
</thead>

<tbody id="productGpBody"></tbody>

</table>

</div>

</div>

</div>
</div>
</div>
{{-- ================= AGENT GP MODAL ================= --}}
<div class="modal fade" id="agentGpModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-xl">
<div class="modal-content" style="border-radius:14px">

<div class="modal-header">

<div class="d-flex justify-content-between align-items-center w-100">

    <div class="d-flex align-items-center gap-2">

        <button id="agentBackBtn"
            class="btn btn-sm btn-light"
            style="display:none">
            ← Back
        </button>

        <h6 class="modal-title fw-semibold mb-0">
            Agent Performance
        </h6>

    </div>

    <div class="d-flex gap-2">

        <button id="exportAgentExcelLvl1" class="btn btn-sm btn-success">
            <i class="fa fa-file-excel"></i> Export Excel
        </button>

    </div>

</div>

<button type="button" class="btn-close" data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">

<div id="agentChartContainer" style="display:none;margin-bottom:20px;">
<canvas id="agentMonthlyChart" height="120"></canvas>
</div>

<div class="table-responsive" style="max-height:500px; overflow-y:auto;">

<table class="table table-hover align-middle">

<thead class="table-light">
<tr>
<th>#</th>
<th>Agent</th>
<th class="text-end">Sales</th>
<th class="text-end">COGS</th>
<th class="text-end">Gross Profit</th>
<th class="text-end">Margin</th>
<th class="text-end">Share %</th> 
</tr>
</thead>

<tbody id="agentGpBody"></tbody>

</table>

</div>

</div>
</div>
</div>
</div>

<div class="modal fade" id="divisionGpModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-xl">
<div class="modal-content" style="border-radius:14px">

<div class="modal-header">

<div class="d-flex justify-content-between align-items-center w-100">

    <div class="d-flex align-items-center gap-2">

        <button id="divisionBackBtn"
        class="btn btn-sm btn-light"
        style="display:none">
        ← Back
        </button>

        <h6 class="modal-title fw-semibold mb-0">
            Division Performance
        </h6>

    </div>

    <!-- ✅ ADD THIS EXPORT BUTTON -->
    <div>
        <button id="exportDivisionExcel"
            class="btn btn-sm btn-success">
            <i class="fa fa-file-excel"></i> Export Excel
        </button>
    </div>

</div>

<button type="button" class="btn-close" data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">
<div id="divisionChartContainer" style="display:none;margin-bottom:20px;">
    <canvas id="divisionMonthlyChart" height="120"></canvas>
</div>
<div class="table-responsive" style="max-height:500px; overflow-y:auto;">

<table class="table table-hover align-middle">

<thead class="table-light">
<tr>
<th>#</th>
<th>Division</th>
<th class="text-end">Sales</th>
<th class="text-end">COGS</th>
<th class="text-end">Gross Profit</th>
<th class="text-end">Margin</th>
<th class="text-end">Share %</th>
</tr>
</thead>

<tbody id="divisionGpBody"></tbody>

</table>

</div>

</div>
</div>
</div>
</div>
{{-- ================= CHARTS ================= --}}
<div class="row g-4 mb-4">

    {{-- GP TREND --}}
    <div class="col-lg-6">
        <div class="chart-card h-100">

         <div class="chart-header">
    <div class="chart-title">
        Gross Profit Trend (Monthly)
    </div>

    {{-- ✅ Enhanced Filter: Month + Year --}}
    <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="month" onchange="this.form.submit()" 
                class="form-select form-select-sm" 
                style="width:110px; font-size: 0.85rem;">
            <option value="">All Months</option>
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected($month == $m)>
                    {{ date('M', mktime(0,0,0,$m,1)) }}
                </option>
            @endforeach
        </select>
        
        <select name="year" onchange="this.form.submit()" 
                class="form-select form-select-sm" 
                style="width:90px; font-size: 0.85rem;">
            <option value="">All</option>
            @for ($y = now()->year; $y >= now()->year - 6; $y--)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>
            <div class="chart-body">
                <canvas id="gpTrend"></canvas>
            </div>

        </div>
    </div>

    {{-- GP BY CLASS --}}
    <div class="col-lg-6">
        <div class="chart-card h-100">

            <div class="chart-header">
                <div class="chart-title">
                    Gross Margin by Class
                </div>
            </div>

            <div class="chart-body">
                <canvas id="gpByClass"></canvas>
            </div>

        </div>
    </div>

</div>

{{-- ================= TABLE + FILTER ================= --}}
<div class="chart-card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="chart-title">
            Gross Profit Breakdown ({{ $periodLabel }})
        </div>

        <div class="d-flex gap-2">
            <select id="sortPrimary" class="form-select form-select-sm" style="width:150px">
                <option value="">Sort by…</option>
                <option value="month">Month</option>
                <option value="class">Class</option>
                <option value="subcategory">Sub Category</option>
            </select>

            <select id="sortSecondary" class="form-select form-select-sm d-none" style="width:180px"></select>
        </div>
    </div>

  <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
    <table class="table table-sm table-hover align-middle" id="gpTable">

            <thead>
                <tr>
                    <th>Year</th>
                    <th>Month</th>
                    <th>Class</th>
                    <th>Sub Category</th>
                    <th class="text-end sortable" data-key="sales">Sales</th>
<th class="text-end sortable" data-key="cogs">COGS</th>
<th class="text-end sortable" data-key="gp">GP</th>
<th class="text-end sortable" data-key="gp_percent">GP %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
               <tr 
    data-month="{{ $r->month }}"
    data-class="{{ $r->CLASS }}"
    data-subcategory="{{ $r->subcategory }}"
    data-sales="{{ $r->totalsales }}"
    data-cogs="{{ $r->cogs }}"
    data-gp="{{ $r->gp_amount }}"
    data-gp_percent="{{ $r->gp_percentage }}"
>
                  <td>{{ $r->trnyear }}</td>
<td>{{ date('F', mktime(0,0,0,$r->month,1)) }}</td>
<td>{{ $r->CLASS }}</td>
<td>{{ $r->subcategory }}</td>
<td class="text-end">{{ formatPeso($r->totalsales) }}</td>
<td class="text-end">{{ formatPeso($r->cogs) }}</td>
<td class="text-end fw-semibold text-teal">{{ formatPeso($r->gp_amount) }}</td>
<td class="text-end">{{ number_format($r->gp_percentage,2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    window.onerror = function (msg, url, line) {
    console.error("JS ERROR:", msg, "at", line);
};
document.addEventListener('DOMContentLoaded', () => {

    let selectedAgent = null;
    let selectedDivision = null;

        function getCurrentFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const year = urlParams.get('year');
    const month = urlParams.get('month');
    
    return {
        year: year === '' ? null : year,
        month: month === '' ? null : month
    };
}
    document.getElementById("exportCustomerExcelLvl1")
?.addEventListener("click", async () => {

    const { year, month } = getCurrentFilters();

window.location.href =
    `/SysproDashboard/gross-profit/customer/export?year=${year}&month=${month}`;
});


// ===== EXPORT LEVEL 2 =====
document.getElementById("exportCustomerExcelLvl2")
?.addEventListener("click", async () => {

    const title = document.querySelector("#customerGpModal .modal-title").innerText;

    const customer = title.split(" — ")[0];

    const { year } = getCurrentFilters();

 window.location.href =
    `/SysproDashboard/gross-profit/customer/monthly-export?customer=${encodeURIComponent(customer)}&year=${year}`;
});

let selectedProductCode = null;

document.getElementById("exportProductExcel")
?.addEventListener("click", () => {

    const { year, month } = getCurrentFilters();

    const isDrilldown =
        document.getElementById("productBackBtn").style.display !== "none";

    // ✅ LEVEL 2 (MONTHLY)
    if (isDrilldown && selectedProductCode) {

        window.location.href =
            `/SysproDashboard/gross-profit/product/monthly-export?product=${selectedProductCode}&year=${year}`;

    } else {

        // ✅ LEVEL 1
        window.location.href =
            `/SysproDashboard/gross-profit/product/export?year=${year}&month=${month}`;
    }
});
document.getElementById("exportAgentExcelLvl1")
?.addEventListener("click", () => {

    const { year, month } = getCurrentFilters();

    if (selectedAgent) {

        window.location.href =
            `{{ route('gross-profit.agent.monthly-export') }}?agent=${encodeURIComponent(selectedAgent)}&year=${year}`;

    } else {

        window.location.href =
            `{{ route('gross-profit.agent.export') }}?year=${year}&month=${month}`;
    }
});

document.getElementById("exportDivisionExcel")
?.addEventListener("click", () => {

    const { year, month } = getCurrentFilters();  // ✅ ADD month

    if (selectedDivision) {

        window.location.href =
            `{{ route('gross-profit.division.monthly-export') }}?division=${encodeURIComponent(selectedDivision)}&year=${year}`;

    } else {

        window.location.href =
            `{{ route('gross-profit.division.export') }}?year=${year}&month=${month}`;  // ✅ ADD month
    }
});
let exportBtn = document.getElementById("exportMonthlyExcel");
exportBtn?.addEventListener("click", () => {

    const { year } = getCurrentFilters();

    window.location.href =
        `{{ route('gross-profit.monthly.export') }}?year=${year}`;
});
document.querySelector('.js-cogs-card')?.addEventListener('click', () => {



    if (typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(
        document.getElementById('cogsBreakdownModal')
    );

    modal.show();
});
function showLoader(){
document.getElementById('pageLoader').style.display='flex';
}

function hideLoader(){
document.getElementById('pageLoader').style.display='none';
}

function cleanupModalArtifacts(){
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

if (typeof Chart === "undefined") {
    console.warn("ChartJS NOT LOADED - charts disabled");
}

let customerPage = 1;
let productCache = {};
let loadingProducts = false;

if(Object.keys(productCache).length > 200){
productCache = {};
}
const customerModal = document.getElementById("customerGpModal");

if (customerModal) {
    customerModal.addEventListener('hidden.bs.modal', () => {
     cleanupModalArtifacts();
        if(window.customerChart){
            window.customerChart.destroy();
            window.customerChart = null;
        }

        document.querySelector("#customerGpModal .modal-title")
        .innerText = "Customer Gross Profit Breakdown";

        document.getElementById("customerChartContainer").style.display = "none";
        document.getElementById("customerBackBtn").style.display = "none";
        document.getElementById("customerGpBody").innerHTML = "";

        
    });
}



const productModal = document.getElementById("productGpModal");
const agentModal = document.getElementById("agentGpModal");
const divisionModal = document.getElementById("divisionGpModal");

if (divisionModal) {
    divisionModal.addEventListener('hidden.bs.modal', () => {
        cleanupModalArtifacts();

        if(window.divisionChart){
            window.divisionChart.destroy();
            window.divisionChart = null;
        }

        document.getElementById("divisionChartContainer").style.display = "none";
    });
}
if (agentModal) {
    agentModal.addEventListener('hidden.bs.modal', () => {
        cleanupModalArtifacts();
    });
}
if (productModal) {
    productModal.addEventListener('hidden.bs.modal', () => {
        cleanupModalArtifacts(); // ✅ FIX BLACK SCREEN
    });
}

if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        try {
            new bootstrap.Tooltip(el);
        } catch(e) {
            console.error("Tooltip error:", e);
        }
        finally {
   loadingProducts = false;
   hideLoader();
}
    });
}
   document.querySelector('.js-sales-card')?.addEventListener('click', () => {

    if (typeof bootstrap === 'undefined') return;

    const el = document.getElementById('salesBreakdownModal');
    const modal = new bootstrap.Modal(el);
    modal.show();
});


document.querySelector('.js-gp-card')?.addEventListener('click', () => {

    if (typeof bootstrap === 'undefined') return;

    const { year, month } = getCurrentFilters();

    // ✅ CONTROL BUTTON VISIBILITY
    if (!month || !year) {
        exportBtn.classList.remove('d-none');
    } else {
        exportBtn.classList.add('d-none');
    }

    // ✅ DYNAMIC LABEL
    if (!year) {
        exportBtn.innerHTML = '<i class="fa fa-file-excel"></i> Export All Years';
    } else {
        exportBtn.innerHTML = '<i class="fa fa-file-excel"></i> Export Year';
    }

    const el = document.getElementById('gpBreakdownModal');
    const modal = new bootstrap.Modal(el);
    modal.show();
});



const primary = document.getElementById('sortPrimary');
const secondary = document.getElementById('sortSecondary');
const tbody = document.querySelector('#gpTable tbody');

if (primary && secondary && tbody) {

    const originalRows = Array.from(tbody.children);

    primary.addEventListener('change', () => {
        tbody.innerHTML = '';
        originalRows.forEach(r => tbody.appendChild(r));
        secondary.classList.add('d-none');
        secondary.innerHTML = '';

        if (!primary.value) return;

        const values = [...new Set(originalRows.map(r => r.dataset[primary.value]))];

        secondary.innerHTML = `<option value="">Select ${primary.value}</option>`;

        values.sort().forEach(v =>
            secondary.innerHTML += `<option value="${v}">${v}</option>`
        );

        secondary.classList.remove('d-none');
    });

    secondary.addEventListener('change', () => {
        tbody.innerHTML = '';

        originalRows
            .filter(r => r.dataset[primary.value] === secondary.value)
            .forEach(r => tbody.appendChild(r));
    });

}
// ===== COLUMN SORTING (ASC / DESC / ORIGINAL) =====

const table = document.getElementById('gpTable');
const tbodyEl = table?.querySelector('tbody');
const headers = table.querySelectorAll('.sortable');

let originalOrder = [];
let sortState = {};

if (tbodyEl) {
    originalOrder = Array.from(tbodyEl.querySelectorAll('tr'));
}

headers.forEach(header => {

    header.style.cursor = 'pointer';

    header.addEventListener('click', () => {

        const key = header.dataset.key;

       if (!sortState[key]) sortState[key] = null;

        sortState[key] =
            sortState[key] === 'asc' ? 'desc' :
            sortState[key] === 'desc' ? null :
            'asc';

        headers.forEach(h => {
            h.innerHTML = h.textContent.replace(/[↑↓⟳]/g, '').trim();
        });

        if (!sortState[key]) {
            tbodyEl.innerHTML = '';
            originalOrder.forEach(row => tbodyEl.appendChild(row));
            header.innerHTML += ' ⟳';
            return;
        }

        const rows = Array.from(tbodyEl.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const valA = parseFloat(a.dataset[key]) || 0;
            const valB = parseFloat(b.dataset[key]) || 0;

            return sortState[key] === 'asc'
                ? valA - valB
                : valB - valA;
        });

        tbodyEl.innerHTML = '';
        rows.forEach(r => tbodyEl.appendChild(r));

        header.innerHTML += sortState[key] === 'asc' ? ' ↑' : ' ↓';
    });

});
async function openGrossProfitCustomers(page = 1){
customerPage = page;
if (typeof bootstrap === 'undefined') return;

const modal = new bootstrap.Modal(
    document.getElementById('customerGpModal')
);

modal.show();

showLoader();

   const { year, month } = getCurrentFilters();

 const res = await fetch(
        `{{ route('gross-profit.customer.data') }}?year=${year}&month=${month}&page=${customerPage}`
    );

if(!res.ok){
console.error("Server error loading customers");
hideLoader();
return;
}
const data = await res.json();
hideLoader();

    const body = document.getElementById('customerGpBody');

    body.innerHTML = '';

   let rank = (data.current_page - 1) * 25 + 1;

let html = '';
data.data.forEach(r => {

html += `
<tr class="customer-row" data-customer="${r.CUSTOMER}" style="cursor:pointer">

<td class="text-muted">${rank++}</td>

<td class="fw-semibold text-primary">
${r.CUSTOMER}
</td>

<td class="text-end">
₱ ${Number(r.sales).toLocaleString()}
</td>

<td class="text-end text-muted">
₱ ${Number(r.cogs).toLocaleString()}
</td>

<td class="text-end fw-semibold text-success">
₱ ${Number(r.gp).toLocaleString()}
</td>

<td class="text-end fw-semibold">
${r.margin}%
</td>

</tr>
`;

});

body.innerHTML = html;

const prevPage = Math.max(1, data.current_page - 1);
const nextPage = Math.min(data.last_page, data.current_page + 1);

body.innerHTML += `
<tr>
<td colspan="5" class="text-center">

<button class="btn btn-sm btn-light customer-prev"
${data.current_page === 1 ? 'disabled' : ''}>
Prev
</button>

<span style="margin:0 10px;">
Page ${data.current_page} of ${data.last_page}
</span>

<button class="btn btn-sm btn-light customer-next"
${data.current_page === data.last_page ? 'disabled' : ''}>
Next
</button>

</td>
</tr>
`;

document.querySelector('.customer-prev')?.addEventListener('click', () => {
    openGrossProfitCustomers(prevPage);
});

document.querySelector('.customer-next')?.addEventListener('click', () => {
    openGrossProfitCustomers(nextPage);
});

document.querySelectorAll('.customer-row').forEach(row => {

row.onclick = async () => {

const customer = row.dataset.customer;
document.getElementById("customerBackBtn").style.display = "inline-block";
document.getElementById("exportCustomerExcelLvl1").style.display = "none";
document.getElementById("exportCustomerExcelLvl2").style.display = "inline-block";

document.querySelector("#customerGpModal .modal-title")
.innerText = customer + " — Monthly Gross Profit";

const { year } = getCurrentFilters();
try {

    showLoader();

    const baseUrl = "{{ route('gross-profit.category.breakdown') }}";

    const res = await fetch(
        baseUrl + "?category=" + encodeURIComponent(customer) + "&year=" + year
    );

    if (!res.ok) {
        const errText = await res.text();
        console.error("Server error:", errText);
        alert('Failed to load customer monthly data.');
          throw new Error('Request failed');
    }

    const data = await res.json();


const body = document.getElementById('customerGpBody');
body.innerHTML = '';

const months = [];
const gpValues = [];

const monthMap = {};
data.forEach(r => {
    monthMap[Number(r.month)] = r;
});

for(let m=1;m<=12;m++){

const r = monthMap[m];

const sales = r ? Number(r.sales) : 0;
const cogs = r ? Number(r.cogs) : 0;

const gp = sales - cogs;

const monthName = new Date(2000, m-1)
.toLocaleString('default',{month:'short'});

const margin = sales > 0 ? ((gp / sales) * 100).toFixed(2) : 0;

months.push(monthName);
gpValues.push(gp);

body.innerHTML += `
<tr>
<td>-</td>
<td>${monthName}</td>
<td class="text-end">₱ ${sales.toLocaleString()}</td>
<td class="text-end">₱ ${cogs.toLocaleString()}</td>
<td class="text-end fw-semibold text-success">₱ ${gp.toLocaleString()}</td>
<td class="text-end">${margin}%</td>
</tr>
`;

}


document.getElementById("customerChartContainer").style.display = "block";

if(window.customerChart){
window.customerChart.destroy();
}

window.customerChart = new Chart(
document.getElementById('customerMonthlyChart'),
{
type:'line',
data:{
labels:months,
datasets:[{
label:'Gross Profit',
data:gpValues,
borderColor:'#0f766e',
backgroundColor:'rgba(15,118,110,.15)',
fill:true,
tension:.3
}]
},
options:{
maintainAspectRatio:false,

interaction:{
mode:'index',
intersect:false
},

plugins:{
legend:{display:false}
},

scales:{
x:{
grid:{display:false}
},

y:{
beginAtZero:true,
ticks:{
callback:v=>'₱ '+Number(v).toLocaleString()
},
grid:{
color:'#e5e7eb'}
}
}

}
}
);
} catch (err) {
    console.error(err);
    alert('Unexpected error occurred');
} finally {
    hideLoader(); // ✅ ALWAYS RUNS
}
}; // closes row.onclick

}); // closes forEach

} // closes openGrossProfitCustomers function

function renderProductTable(data){
const body = document.getElementById('productGpBody');
body.innerHTML = "";

let rank = 1;

data.forEach(r => {

body.innerHTML += `
<tr class="product-row" 
    data-code="${r.ItemCode}" 
    data-product="${r.PRODUCT}" 
    style="cursor:pointer">

<td class="text-muted">${rank++}</td>

<td class="fw-semibold text-success product-name">
${r.PRODUCT}
</td>

<td class="text-end fw-semibold">
₱ ${Number(r.sales).toLocaleString()}
</td>

<td class="text-end text-muted">
₱ ${Number(r.cogs).toLocaleString()}
</td>

<td class="text-end fw-semibold text-success">
₱ ${Number(r.gp).toLocaleString()}
</td>

<td class="text-end fw-semibold">
${r.margin}%
</td>

<td class="text-end text-muted">
${r.share}%
</td>

</tr>
`;

});

}

async function openGrossProfitProducts(page = 1){   

if(loadingProducts) return;
loadingProducts = true;

if (typeof bootstrap === 'undefined') return;

const modal = new bootstrap.Modal(
    document.getElementById('productGpModal')
);
modal.show();

try {

showLoader();

 const { year, month } = getCurrentFilters();

        const res = await fetch(
            `{{ route('gross-profit.product.data') }}?year=${year}&month=${month}&page=${page}`
        );
const body = document.getElementById('productGpBody');

body.innerHTML = `
<tr>
<td colspan="7" class="text-center py-4">
<div class="spinner-border text-success"></div>
<div class="mt-2 text-muted">Loading products...</div>
</td>
</tr>
`;

const selectedYear = new URLSearchParams(window.location.search).get('year') || '';

/* RESET PRODUCT DRILLDOWN STATE */
document.getElementById("productChartContainer").style.display = "none";

if(window.productChart){
window.productChart.destroy();
window.productChart = null;
}


if(!res.ok){
console.error("Server error loading products");
hideLoader();
return;
}

const data = await res.json();
hideLoader();

body.innerHTML = "";

let rank = (data.current_page - 1) * 25 + 1;

data.data.forEach(r => {

body.innerHTML += `
<tr class="product-row" 
    data-code="${r.ItemCode}" 
    data-product="${r.PRODUCT}" 
    style="cursor:pointer">

<td class="text-muted">${rank++}</td>

<td class="fw-semibold text-success product-name">
${r.PRODUCT}
</td>

<td class="text-end fw-semibold">
₱ ${Number(r.sales).toLocaleString()}
</td>

<td class="text-end text-muted">
₱ ${Number(r.cogs).toLocaleString()}
</td>

<td class="text-end fw-semibold text-success">
₱ ${Number(r.gp).toLocaleString()}
</td>

<td class="text-end fw-semibold">
${r.margin}%
</td>

<td class="text-end text-muted">
${r.share}%
</td>

</tr>
`;

});

const prevPage = Math.max(1, data.current_page - 1);
const nextPage = Math.min(data.last_page, data.current_page + 1);

body.innerHTML += `
<tr>
<td colspan="7" class="text-center">

<button class="btn btn-sm btn-light prev-btn"
${data.current_page === 1 ? 'disabled' : ''}>
Prev
</button>

<span style="margin:0 10px;">
Page ${data.current_page} of ${data.last_page}
</span>

<button class="btn btn-sm btn-light next-btn"
${data.current_page === data.last_page ? 'disabled' : ''}>
Next
</button>

</td>
</tr>
`;

document.querySelector('.prev-btn')?.addEventListener('click', () => {
    openGrossProfitProducts(prevPage);
});

document.querySelector('.next-btn')?.addEventListener('click', () => {
    openGrossProfitProducts(nextPage);
});

document.querySelectorAll('.product-row').forEach(row => {

row.onclick = async () => {
selectedProductCode = row.dataset.code;
const product = row.dataset.code;
const productName = row.dataset.product;


document.getElementById("productBackBtn").style.display = "inline-block";

document.getElementById("productModalTitle").innerText =
productName + " — Monthly Performance";

document.querySelector("#productGpModal thead").innerHTML = `
<tr>
<th style="width:40px">#</th>
<th>Month</th>
<th class="text-end">Sales</th>
<th class="text-end">COGS</th>
<th class="text-end">Gross Profit</th>
<th class="text-end">Margin %</th>
<th class="text-end"></th>
</tr>
`;



const cacheKey = product + '_' + year;

if(productCache[cacheKey]){

    const data = productCache[cacheKey];
    body.innerHTML = "";

    const monthMap = {};
    const months = [];
    const gpValues = [];

    const monthIndexMap = {
    JANUARY:1, FEBRUARY:2, MARCH:3, APRIL:4,
    MAY:5, JUNE:6, JULY:7, AUGUST:8,
    SEPTEMBER:9, OCTOBER:10, NOVEMBER:11, DECEMBER:12
};

data.forEach(r => {
    const key = monthIndexMap[r.MONTH?.toUpperCase()];
    if(key){
        monthMap[key] = r;
    }
});

    for(let m=1;m<=12;m++){

        const r = monthMap[m];
        const sales = r ? Number(r.sales) : 0;
        const cogs = r ? Number(r.cogs) : 0;
        const gp = sales - cogs;

        const monthName = new Date(2000,m-1)
            .toLocaleString('default',{month:'short'});

        const margin = sales > 0 ? ((gp/sales)*100).toFixed(2) : 0;

        months.push(monthName);
        gpValues.push(gp);

        body.innerHTML += `
        <tr>
            <td>${m}</td>
            <td>${monthName}</td>
            <td class="text-end">₱ ${sales.toLocaleString()}</td>
            <td class="text-end">₱ ${cogs.toLocaleString()}</td>
            <td class="text-end">₱ ${gp.toLocaleString()}</td>
            <td class="text-end">${margin}%</td>
            <td></td>
        </tr>`;
    }

    // ✅ ADD THIS (IMPORTANT)
    document.getElementById("productChartContainer").style.display = "block";

    if(window.productChart){
        window.productChart.destroy();
    }

    window.productChart = new Chart(
        document.getElementById('productMonthlyChart'),
        {
            type:'line',
            data:{
                labels:months,
                datasets:[{
                    label:'Gross Profit',
                    data:gpValues,
                    borderColor:'#0f766e',
                    backgroundColor:'rgba(15,118,110,0.12)',
                    fill:true,
                    tension:0.4
                }]
            },
            options:{
                maintainAspectRatio:false,
                plugins:{
                    legend:{display:false}
                }
            }
        }
    );

 hideLoader();
    return;
}

const baseUrl = "{{ route('gross-profit.product.monthly') }}";

const res2 = await fetch(
    baseUrl + "?product=" + encodeURIComponent(product) + "&year=" + year
);

if(!res2.ok){
    const err = await res2.text();
    console.error("Product monthly error:", err);
    alert("Failed to load product data");
     hideLoader();
    return;
}

const data = await res2.json();
productCache[cacheKey] = data;


body.innerHTML = "";

const monthMap = {};
const months = [];
const gpValues = [];

data.forEach(r=>{
monthMap[parseInt(r.month)] = r;
});

for(let m=1;m<=12;m++){

const r = monthMap[m];

const sales = r ? Number(r.sales) : 0;
const cogs = r ? Number(r.cogs) : 0;
const gp = sales - cogs;

const monthName = new Date(2000,m-1)
.toLocaleString('default',{month:'short'});

months.push(monthName);
gpValues.push(gp);

const margin = sales > 0 ? ((gp/sales)*100).toFixed(2) : 0;

body.innerHTML += `
<tr>
<td class="text-muted">${m}</td>
<td class="fw-semibold">${monthName}</td>
<td class="text-end">₱ ${sales.toLocaleString()}</td>
<td class="text-end text-muted">₱ ${cogs.toLocaleString()}</td>
<td class="text-end fw-semibold text-success">₱ ${gp.toLocaleString()}</td>
<td class="text-end">${margin}%</td>
<td></td>
</tr>
`;

}

document.getElementById("productChartContainer").style.display = "block";

if(window.productChart){
window.productChart.destroy();
}

window.productChart = new Chart(
document.getElementById('productMonthlyChart'),
{
type:'line',
data:{
labels:months,
datasets:[{
label:'Gross Profit',
data:gpValues,
borderColor:'#0f766e',
backgroundColor:'rgba(15,118,110,0.12)',
borderWidth:3,
pointRadius:4,
pointHoverRadius:6,
pointBackgroundColor:'#0f766e',
fill:true,
tension:0.4
}]
},
options:{
maintainAspectRatio:false,

interaction:{
mode:'index',
intersect:false
},

layout:{
padding:{
top:10,
bottom:10,
left:10,
right:10
}
},

plugins:{
legend:{display:false},
tooltip:{
callbacks:{
label:c=>'₱ '+Number(c.raw).toLocaleString()
}
}
},

scales:{
x:{
grid:{display:false},
ticks:{color:'#475569'}
},

y:{
grid:{
color:'#e5e7eb'
},
ticks:{
color:'#475569',
callback:v=>'₱ '+Number(v).toLocaleString()
}
}
}
}
});

return;
}; // closes row.onclick

}); 

} catch (err) {
    console.error("PRODUCT ERROR:", err);
} finally {
    loadingProducts = false;
    hideLoader();
}

}

async function openGrossProfitAgents(page = 1){

if (typeof bootstrap === 'undefined') return;

const modal = new bootstrap.Modal(
    document.getElementById('agentGpModal')
);

modal.show();

try {

showLoader();

const { year, month } = getCurrentFilters();

const res = await fetch(
            `{{ route('gross-profit.agent.data') }}?year=${year}&month=${month}&page=${page}`
        );
if(!res.ok){
const err = await res.text();
console.error("Agent load failed:", err);
alert(err);
hideLoader();
return;
}

const resData = await res.json();
const data = resData.data;

const body = document.getElementById('agentGpBody');
body.innerHTML = "";

let rank = (resData.current_page - 1) * 25 + 1;

data.forEach(r => {

const gp = r.gp;
const margin = r.margin;

body.innerHTML += `
<tr class="agent-row" data-agent="${r.AGENT}" style="cursor:pointer">

<td>${rank++}</td>
<td class="fw-semibold text-warning">${r.AGENT}</td>
<td class="text-end">₱ ${Number(r.sales).toLocaleString()}</td>
<td class="text-end text-muted">₱ ${Number(r.cogs).toLocaleString()}</td>
<td class="text-end fw-semibold text-success">₱ ${gp.toLocaleString()}</td>
<td class="text-end">${margin}%</td>
<td class="text-end">${r.share}%</td>
</tr>
`;

});
const prevPage = Math.max(1, resData.current_page - 1);
const nextPage = Math.min(resData.last_page, resData.current_page + 1);

body.innerHTML += `
<tr>
<td colspan="7" class="text-center">

<button class="btn btn-sm btn-light agent-prev"
${resData.current_page === 1 ? 'disabled' : ''}>
Prev
</button>

<span style="margin:0 10px;">
Page ${resData.current_page} of ${resData.last_page}
</span>

<button class="btn btn-sm btn-light agent-next"
${resData.current_page === resData.last_page ? 'disabled' : ''}>
Next
</button>

</td>
</tr>
`;

document.querySelector('.agent-prev')?.addEventListener('click', () => {
    openGrossProfitAgents(prevPage);
});

document.querySelector('.agent-next')?.addEventListener('click', () => {
    openGrossProfitAgents(nextPage);
});

document.querySelectorAll('.agent-row').forEach(row => {

row.onclick = async () => {

    const agent = row.dataset.agent;

    selectedAgent = agent; // ✅ FORCE SET

    console.log("SELECTED AGENT:", selectedAgent); // ✅ DEBUG

    document.getElementById("agentBackBtn").style.display = "inline-block";

    document.querySelector("#agentGpModal .modal-title")
    .innerText = agent + " — Monthly Performance";

    const { year } = getCurrentFilters();

    showLoader();

    const res = await fetch(
        "{{ route('gross-profit.agent.monthly') }}?agent=" + encodeURIComponent(agent) + "&year=" + year
    );

    if(!res.ok){
        alert("Failed to load agent monthly data");
        hideLoader();
        return;
    }

    const data = await res.json();

    const body = document.getElementById('agentGpBody');
    body.innerHTML = "";

    const months = [];
    const gpValues = [];
    const monthMap = {};

    data.forEach(r => {
        const key = Number(r.MONTH || r.month);
        if(key >= 1 && key <= 12){
            monthMap[key] = r;
        }
    });

    for(let m=1;m<=12;m++){

        const r = monthMap[m];

        const sales = r ? Number(r.sales) : 0;
        const cogs = r ? Number(r.cogs) : 0;
        const gp = sales - cogs;

        const monthName = new Date(2000,m-1)
        .toLocaleString('default',{month:'short'});

        const margin = sales > 0 ? ((gp/sales)*100).toFixed(2) : 0;

        months.push(monthName);
        gpValues.push(gp);

        body.innerHTML += `
        <tr>
        <td>${m}</td>
        <td>${monthName}</td>
        <td class="text-end">₱ ${sales.toLocaleString()}</td>
        <td class="text-end">₱ ${cogs.toLocaleString()}</td>
        <td class="text-end">₱ ${gp.toLocaleString()}</td>
        <td class="text-end">${margin}%</td>
        <td></td>
        </tr>
        `;
    }

    document.getElementById("agentChartContainer").style.display = "block";

    if(window.agentChart){
        window.agentChart.destroy();
    }

    window.agentChart = new Chart(
        document.getElementById('agentMonthlyChart'),
        {
            type:'line',
            data:{
                labels:months,
                datasets:[{
                    label:'Gross Profit',
                    data:gpValues,
                    borderColor:'#f59e0b',
                    backgroundColor:'rgba(245,158,11,.15)',
                    fill:true,
                    tension:.3
                }]
            },
            options:{
                maintainAspectRatio:false,
                plugins:{legend:{display:false}}
            }
        }
    );

    hideLoader();
};

});
} catch (err) {
console.error("AGENT ERROR:", err);
} finally {
hideLoader();
}

}

async function openGrossProfitDivisions(){

    if (typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(
        document.getElementById('divisionGpModal')
    );

    modal.show();

    showLoader();

    try {

       const { year, month } = getCurrentFilters();

       const res = await fetch(
            `{{ route('gross-profit.division.data') }}?year=${year}&month=${month}`
        );

        if(!res.ok){
            throw new Error("Failed to load division data");
        }

        const data = await res.json();

        const body = document.getElementById('divisionGpBody');
        body.innerHTML = "";

        let rank = 1;

       data.forEach(r => {
    body.innerHTML += `
    <tr class="division-row" data-division="${r.DIVISION}" style="cursor:pointer">

        <td>${rank++}</td>

        <td class="fw-semibold text-danger">
            ${r.DIVISION}
        </td>

        <td class="text-end">
            ₱ ${Number(r.sales).toLocaleString()}
        </td>

        <td class="text-end text-muted">
            ₱ ${Number(r.cogs).toLocaleString()}
        </td>

        <td class="text-end fw-semibold text-success">
            ₱ ${Number(r.gp).toLocaleString()}
        </td>

        <td class="text-end">
            ${r.margin}%
        </td>

        <td class="text-end text-muted">
            ${r.share}%
        </td>

    </tr>
    `;
});
document.querySelectorAll('.division-row').forEach(row => {

row.onclick = async () => {

const division = row.dataset.division;
selectedDivision = division;

document.getElementById("divisionBackBtn").style.display = "inline-block";

document.querySelector("#divisionGpModal .modal-title")
.innerText = division + " — Monthly Performance";
document.querySelector("#divisionGpModal thead").innerHTML = `
<tr>
<th>#</th>
<th>Month</th>
<th class="text-end">Sales</th>
<th class="text-end">COGS</th>
<th class="text-end">Gross Profit</th>
<th class="text-end">Margin</th>
<th class="text-end"></th>
</tr>
`;
showLoader();

const { year } = getCurrentFilters();

const res = await fetch(
"{{ route('gross-profit.division.monthly') }}?division=" + encodeURIComponent(division) + "&year=" + year
);

if(!res.ok){
    alert("Failed to load division monthly data");
    hideLoader();
    return;
}

const data = await res.json();

const body = document.getElementById('divisionGpBody');
body.innerHTML = "";

const monthMap = {};
const months = [];
const gpValues = [];

const monthIndexMap = {
    JANUARY:1, FEBRUARY:2, MARCH:3, APRIL:4,
    MAY:5, JUNE:6, JULY:7, AUGUST:8,
    SEPTEMBER:9, OCTOBER:10, NOVEMBER:11, DECEMBER:12
};

data.forEach(r => {

    let key;

    if (!isNaN(r.MONTH)) {
        key = Number(r.MONTH);
    } else {
        key = monthIndexMap[(r.MONTH || r.month)?.toUpperCase()];
    }

    if(key){
        monthMap[key] = r;
    }
});
for(let m=1;m<=12;m++){

const r = monthMap[m];

const sales = r ? Number(r.sales) : 0;
const cogs = r ? Number(r.cogs) : 0;
const gp = sales - cogs;

const monthName = new Date(2000,m-1)
.toLocaleString('default',{month:'short'});
months.push(monthName);
gpValues.push(gp);

const margin = sales > 0 ? ((gp/sales)*100).toFixed(2) : 0;

body.innerHTML += `
<tr>
<td>${m}</td>
<td>${monthName}</td>
<td class="text-end">₱ ${sales.toLocaleString()}</td>
<td class="text-end">₱ ${cogs.toLocaleString()}</td>
<td class="text-end">₱ ${gp.toLocaleString()}</td>
<td class="text-end">${margin}%</td>
<td></td>
</tr>
`;
}
document.getElementById("divisionChartContainer").style.display = "block";

if(window.divisionChart){
    window.divisionChart.destroy();
}

window.divisionChart = new Chart(
    document.getElementById('divisionMonthlyChart'),
    {
        type:'line',
        data:{
            labels: months,
            datasets:[{
                label:'Gross Profit',
                data: gpValues,
                borderColor:'#ef4444',
                backgroundColor:'rgba(239,68,68,.15)',
                fill:true,
                tension:.3
            }]
        },
        options:{
            maintainAspectRatio:false,
            plugins:{ legend:{ display:false } }
        }
    }
);
hideLoader();
};
});

    } catch (err) {
        console.error("DIVISION ERROR:", err);
        alert("Failed to load division data");
    } finally {
         hideLoader();
    }
}

const drillCards = document.querySelectorAll('.drill-card');

if (drillCards.length > 0) {
    drillCards.forEach(card => {

card.addEventListener('click', () => {

const loader = document.getElementById('pageLoader');

if(loader && loader.style.display === 'flex'){
    console.warn('Blocked due to loader');
    return;
}

const type = card.dataset.type;

if(type === 'customer') openGrossProfitCustomers();
if(type === 'product') openGrossProfitProducts();
if(type === 'agent') openGrossProfitAgents();
if(type === 'division') openGrossProfitDivisions();

});

});
}
const customerBackBtn = document.getElementById("customerBackBtn");

if (customerBackBtn) {
    customerBackBtn.addEventListener("click", async () => {

document.getElementById("customerBackBtn").style.display = "none";
document.getElementById("exportCustomerExcelLvl1").style.display = "inline-block";
document.getElementById("exportCustomerExcelLvl2").style.display = "none";

document.getElementById("customerChartContainer").style.display = "none";

document.querySelector("#customerGpModal .modal-title")
.innerText = "Customer Gross Profit Breakdown";

await openGrossProfitCustomers(1);

});
}

const productBackBtn = document.getElementById("productBackBtn");

if (productBackBtn) {
    productBackBtn.addEventListener("click", () => {

        document.getElementById("exportProductExcel").onclick = () => {
    const { year, month } = getCurrentFilters();

    window.location.href =
        `/gross-profit/product/export?year=${year}&month=${month}`;
};

document.querySelector("#productGpModal thead").innerHTML = `
<tr>
<th style="width:40px">#</th>
<th>Product</th>
<th class="text-end">Sales</th>
<th class="text-end">COGS</th>
<th class="text-end">Gross Profit</th>
<th class="text-end">Margin %</th>
<th class="text-end">Share %</th>
</tr>
`;
document.getElementById("productBackBtn").style.display = "none";

document.getElementById("productChartContainer").style.display = "none";

document.getElementById("productModalTitle").innerText =
"Product Profitability Analysis";

if(window.productChart){
window.productChart.destroy();
window.productChart = null;
}

/* INSTANT UI RESET */
document.getElementById("productGpBody").innerHTML = `
<tr>
<td colspan="7" class="text-center py-4">
<div class="spinner-border spinner-border-sm me-2"></div>
Loading products...
</td>
</tr>
`;

/* LOAD LEVEL 1 AGAIN */
openGrossProfitProducts(1);

});

}
const agentBackBtn = document.getElementById("agentBackBtn");
const divisionBackBtn = document.getElementById("divisionBackBtn");
if (divisionBackBtn) {
    divisionBackBtn.addEventListener("click", () => {

        divisionBackBtn.style.display = "none";

        document.querySelector("#divisionGpModal .modal-title")
        .innerText = "Division Performance";

        document.getElementById("divisionChartContainer").style.display = "none";

if(window.divisionChart){
    window.divisionChart.destroy();
    window.divisionChart = null;
}
        // ✅ ADD THIS HERE
        document.querySelector("#divisionGpModal thead").innerHTML = `
        <tr>
        <th>#</th>
        <th>Division</th>
        <th class="text-end">Sales</th>
        <th class="text-end">COGS</th>
        <th class="text-end">Gross Profit</th>
        <th class="text-end">Margin</th>
        <th class="text-end">Share %</th>
        </tr>
        `;

        openGrossProfitDivisions(); // reload list

    });
}
if (agentBackBtn) {
    agentBackBtn.addEventListener("click", () => {

        document.getElementById("agentBackBtn").style.display = "none";

        document.getElementById("agentChartContainer").style.display = "none";

        document.querySelector("#agentGpModal .modal-title")
        .innerText = "Agent Performance";

        if(window.agentChart){
            window.agentChart.destroy();
            window.agentChart = null;
        }

        /* RESET TABLE */
     document.getElementById("agentGpBody").innerHTML = `
<tr>
<td colspan="7" class="text-center py-4">
<div class="spinner-border spinner-border-sm me-2"></div>
Fetching agent data...
</td>
</tr>
`;

        /* RELOAD LEVEL 1 */
        openGrossProfitAgents();

    });
}
const gpTrendCanvas = document.getElementById('gpTrend');

if (gpTrendCanvas && typeof Chart !== 'undefined') {

new Chart(gpTrendCanvas,{
type:'line',
data:{
labels:@json($monthLabels),
datasets:[{
data:@json(array_values($monthlyGpMap)).map(v => Number(v) || 0),
borderColor:'#0f766e',
backgroundColor:'rgba(15,118,110,.15)',
fill:true,
tension:.35
}]
},
options:{
maintainAspectRatio:false,
interaction:{
mode:'index',
intersect:false
},
plugins:{
legend:{ display:false }
},
scales:{
x:{ grid:{display:false} },
y:{
grid:{color:'#e5e7eb'},
ticks:{ callback:v=>'₱ '+Number(v).toLocaleString() }
}
}
}
});

}

const gpClassCanvas = document.getElementById('gpByClass');

if (gpClassCanvas && typeof Chart !== 'undefined') {

new Chart(gpClassCanvas,{
type:'bar',
data:{
labels:[''],
datasets:@json($barDatasets)
},
options:{
maintainAspectRatio:false,
plugins:{
legend:{
position:'bottom',
labels:{ usePointStyle:true }
},
tooltip:{
callbacks:{
label: function(c){
    return c.dataset.label + ": " + c.dataset.actual + "%";
}
}
}
},
scales:{
y:{
min:0,
max:100,
ticks:{ callback:v=>v+'%' }
}
}
}
});

}
window.openGrossProfitCustomers = openGrossProfitCustomers;
window.openGrossProfitProducts = openGrossProfitProducts;
});
</script>
@endpush
