<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // =========================================================================
    // 1. DASHBOARD OVERVIEW (Index)
    // =========================================================================
    public function index()
    {
        $today = date('Y-m-d');

        $salesCacheKey = "dashboard:sales:total";
        $totalSales = Cache::remember($salesCacheKey, 300, function () {
            return (float) DB::table('vSummarySalesCogs')->selectRaw('SUM([Amount]) AS TotalSales')->value('TotalSales') ?: 0.0;
        });
        $formattedSales = $this->formatCurrency($totalSales);

$rows = $this->getArRows($today, 'A');
$grossAR = 0.0;
$creditsAR = 0.0;

foreach ($rows as $r) {
    $bal = floatval($r['Total'] ?? $r['Balances'] ?? $r['Balance'] ?? 0);
    if ($bal >= 0) {
        $grossAR += $bal;
    } else {
        $creditsAR += abs($bal);
    }
}

$formattedAr = $this->formatCurrency($grossAR - $creditsAR);


        // ================= AR KPI Sparkline (Real Monthly AR) =================
$arMonths = [];
$arValues = [];
$today = Carbon::today();

for ($i = 5; $i >= 0; $i--) {
   $monthEnd = Carbon::today()->subMonths($i)->endOfMonth();
if ($monthEnd->greaterThan($today)) {
    continue; // safety guard
}


    $arRowsMonth = $this->getArRows($monthEnd->toDateString(), 'A');


    $monthTotal = 0.0;
  foreach ($arRowsMonth as $r) {
  $monthTotal += (float) ($r['Total'] ?? $r['Balances'] ?? $r['Balance'] ?? 0);
}

    $arMonths[] = Carbon::parse($monthEnd)->format('M Y');
    $arValues[] = round($monthTotal, 2);
}

        $inventoryTotal = Cache::remember('dashboard:inventory:total', 300, function () {
    return (float) DB::table('vInventoryMovement')
        ->selectRaw('SUM([Totals]) as total')
        ->value('total') ?: 0;
});
$formattedInventory = $this->formatCurrency($inventoryTotal);

$grossProfitTotal = Cache::remember('dashboard:grossprofit:total', 300, function () {
    return (float) DB::table('vGrossProfitPerItem')
        ->selectRaw('SUM([GP_Amount]) as total')
        ->value('total') ?: 0;
});
$formattedGrossProfit = $this->formatCurrency($grossProfitTotal);

// ================= AP TOTAL (Dashboard KPI) =================
$apRows = $this->getApRows($today, 'A');

$apTotal = $this->computeApTotalFromRows($apRows);
$formattedAP = $this->formatCurrency($apTotal);


       $data = [
    'salesBookings' => $formattedSales,
    'totalAR'       => $formattedAr,
    'arSparkLabels' => $arMonths,
    'arSparkValues' => $arValues,
    'totalAP'       => $formattedAP,
    'inventory'     => $formattedInventory,
    'grossProfit'   => $formattedGrossProfit,
];



        return view('dashboard', compact('data'));
    }

    // =========================================================================
    // 2. SALES PAGE (kept minimal here)
    // =========================================================================
    public function sales(Request $request)
    {
        if (function_exists('set_time_limit')) { @set_time_limit(180); }
        @ini_set('max_execution_time', '180');

        $year = $request->input('year');
        $month = $request->input('month');
// =====================================================
// DIVISION LEVEL 1 — GET DIVISIONS
// =====================================================
if ($request->has('getDivisions')) {
$divisions = DB::table('vSummarySalesCogs')
    ->select(
        'Class as Division',
        DB::raw('SUM(Amount) as TotalAmount')
    )
    ->when($year, fn($q) => $q->where('TrnYear', $year))
    ->when($month, fn($q) => $q->where('TrnMonth', $month))
    ->groupBy('Class')
    ->orderByDesc(DB::raw('SUM(Amount)'))
    ->get();

    return response()->json([
        'divisions' => $divisions
    ]);
}

// =====================================================
// DIVISION LEVEL 2 — DIVISION → AGENTS
// =====================================================
if ($request->has('division')) {

    $division = $request->input('division');

   $agents = DB::table('vSummarySalesCogs')
    ->select(
        'Agent Name',
        DB::raw('SUM(Amount) as TotalAmount')
    )
    ->where('Class', $division)
    ->when($year, fn($q) => $q->where('TrnYear', $year))
    ->when($month, fn($q) => $q->where('TrnMonth', $month))
    ->groupBy('Agent Name')
    ->orderByDesc(DB::raw('SUM(Amount)'))
    ->get();
    return response()->json([
        'divisionAgents' => $agents
    ]);
}
        if ($request->has('getAgents')) {

    $agents = DB::table('vSummarySalesCogs')
        ->select(
            DB::raw('[Agent Name]'),
            DB::raw('SUM([Amount]) as TotalAmount')
        )
        ->when($year, fn($q) => $q->where('TrnYear', $year))
        ->when($month, fn($q) => $q->where('TrnMonth', $month))
        ->groupBy(DB::raw('[Agent Name]'))
        ->orderByDesc(DB::raw('SUM([Amount])'))
        ->get();

    return response()->json([
        'agents' => $agents
    ]);
}

if ($request->has('agent')) {

    $agent = $request->input('agent');

    $customers = DB::table('vSummarySalesCogs')
        ->select(
            DB::raw('[Customer Name] as CustomerName'),
            DB::raw('SUM([Amount]) as TotalAmount')
        )
        ->where(DB::raw('[Agent Name]'), $agent)
        ->when($year, fn($q) => $q->where('TrnYear', $year))
        ->when($month, fn($q) => $q->where('TrnMonth', $month))
        ->groupBy(DB::raw('[Customer Name]'))
        ->orderByDesc(DB::raw('SUM([Amount])'))
        ->get();

    return response()->json([
        'agentCustomers' => $customers
    ]);
}

        $base = DB::table('vSummarySalesCogs')
            ->when($year, fn($q) => $q->where('TrnYear', $year))
            ->when($month, fn($q) => $q->where('TrnMonth', $month));

 $perPage = 25; // you can change this later (25, 50, etc.)

$salesData = (clone $base)
    ->select(
        'TrnYear',
        'TrnMonth',
        'Customer Name',
        'Invoice',
        'ItemCode',
        DB::raw('[Item Description] as ItemDescription'),
        'Class',
        'Qty',
        'Amount'
    )
    ->orderByDesc('TrnYear')
    ->orderByDesc('TrnMonth')
    ->paginate($perPage);


        $totalSales = DB::table('vSummarySalesCogs')
            ->when($year, fn($q) => $q->where('TrnYear', $year))
            ->sum('Amount');

        $totalQty = $salesData->sum('Qty');

        $cacheSuffix = ($year ?: 'all') . ':' . ($month ?: 'all');
        $cachePrefix = 'sales:' . $cacheSuffix . ':';

        $salesByMonth = Cache::remember($cachePrefix . 'byMonth', 300, function () use ($year, $month) {
            return DB::table('vSummarySalesCogs')
                ->select('TrnMonth', DB::raw('SUM([Amount]) as MonthlySales'))
                ->when($year, fn($q) => $q->where('TrnYear', $year))
                ->when($month, fn($q) => $q->where('TrnMonth', $month))
                ->groupBy('TrnMonth')->orderBy('TrnMonth')->get();
        });

        $salesByYear = Cache::remember($cachePrefix . 'byYear', 300, function () {
            return DB::table('vSummarySalesCogs')
                ->select('TrnYear', DB::raw('SUM([Amount]) as YearlySales'))
                ->groupBy('TrnYear')->orderBy('TrnYear')->get();
        });

        $topCustomers = Cache::remember($cachePrefix . 'topCustomers', 300, function () use ($year, $month) {
            return DB::table('vSummarySalesCogs')
                ->select('Customer Name', DB::raw('SUM([Amount]) as TotalAmount'))
                ->when($year, fn($q) => $q->where('TrnYear', $year))
                ->when($month, fn($q) => $q->where('TrnMonth', $month))
                ->groupBy('Customer Name')
                ->orderByDesc(DB::raw('SUM([Amount])'))->limit(10)->get();
        });

        if ($request->has('getAllCustomers')) {

            $allCustomers = DB::table('vSummarySalesCogs')
                ->select('Customer Name', DB::raw('SUM([Amount]) as TotalAmount'))
                ->when($year, fn($q) => $q->where('TrnYear', $year))
                ->when($month, fn($q) => $q->where('TrnMonth', $month))
                ->groupBy('Customer Name')
                ->orderByDesc(DB::raw('SUM([Amount])'))
                ->get();

            return response()->json(['allCustomers' => $allCustomers]);
        }
 if ($request->has('getProducts')) {
    $perPage = 50;
    $page = (int) $request->input('page', 1);

    $products = DB::table('vSummarySalesCogs')
        ->select(
            DB::raw("COALESCE(NULLIF(LTRIM(RTRIM([Item Description])), ''), 'NO DESCRIPTION') as ItemDescription"),
            DB::raw('MIN(ItemCode) as ItemCode'),
            DB::raw('SUM([Amount]) as TotalAmount')
        )
        ->when($year, fn($q) => $q->where('TrnYear', $year))
        ->when($month, fn($q) => $q->where('TrnMonth', $month))
        ->groupBy(
            DB::raw("COALESCE(NULLIF(LTRIM(RTRIM([Item Description])), ''), 'NO DESCRIPTION')")
        )
        ->orderByDesc(DB::raw('SUM([Amount])'))
        ->paginate($perPage, ['*'], 'page', $page);

    // ✅ ADD THIS: Calculate grand total from ALL products
    $grandTotal = DB::table('vSummarySalesCogs')
        ->when($year, fn($q) => $q->where('TrnYear', $year))
        ->when($month, fn($q) => $q->where('TrnMonth', $month))
        ->sum('Amount');

    return response()->json([
        'products' => $products->items(),
        'pagination' => [
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'per_page'     => $products->perPage(),
            'total'        => $products->total(),
        ],
        'grandTotal' => (float) $grandTotal  // ✅ ADD THIS
    ]);
}

       $availableYears = Cache::remember('sales:availableYears', 3600, function () {
    return DB::table('vSummarySalesCogs')
        ->select('TrnYear')
        ->distinct()
        ->orderBy('TrnYear', 'desc')
        ->pluck('TrnYear');
});
    $availableMonths = Cache::remember('sales:availableMonths', 3600, function () {
    return DB::table('vSummarySalesCogs')
        ->selectRaw('DISTINCT CAST(TrnMonth AS INT) as TrnMonth')
        ->orderBy('TrnMonth', 'asc')
        ->pluck('TrnMonth');
});

        $format = fn($value) => $this->formatCurrency($value);
        $totalSalesFormatted = $format($totalSales);

        $allTimeTotal = Cache::remember('sales:all_time_total', 300, fn() => (float) DB::table('vSummarySalesCogs')->sum('Amount') ?: 0.0);
        $allTimeTotalFormatted = $this->formatCurrency($allTimeTotal);

        $selectedMonthSales = $month ? ($salesByMonth->where('TrnMonth', $month)->first()->MonthlySales ?? 0) : $salesByMonth->sum('MonthlySales');
        $salesPerMonthFormatted = $format($selectedMonthSales);

        $distinctCount = (clone $base)->distinct()->count('Customer Name');
        $avgSales = $distinctCount > 0 ? ($totalSales / $distinctCount) : 0;
        $avgSalesPerCustomerFormatted = $format($avgSales);

       if ($request->ajax()) {
    return response()->json([
        'salesData' => $salesData->items(),

        'pagination' => [
            'current_page' => $salesData->currentPage(),
            'last_page'    => $salesData->lastPage(),
            'per_page'     => $salesData->perPage(),
            'total'        => $salesData->total(),
        ],

        'salesByMonth' => $salesByMonth,
        'salesByYear'  => $salesByYear,
        'topCustomers' => $topCustomers,
        'totalSalesFormatted' => $totalSalesFormatted,
        'salesPerMonthFormatted' => $salesPerMonthFormatted,
        'avgSalesPerCustomerFormatted' => $avgSalesPerCustomerFormatted,
        'allTimeTotalFormatted' => $allTimeTotalFormatted,
    ]);
}


        return view('sales', compact(
            'salesData','salesByMonth','salesByYear','topCustomers','totalSales','totalQty',
            'year','month','availableYears','availableMonths','totalSalesFormatted','salesPerMonthFormatted','avgSalesPerCustomerFormatted','allTimeTotalFormatted'
        ));
    }

    // =========================================================================
    // 3. AR (kept for completeness)
    // =========================================================================
    public function arIndex(Request $request)
    {
         if ($request->get('export') === 'csv') {
        $dateTo = $request->input('dateTo', date('Y-m-d'));
        $rows = $this->getArRows($dateTo, 'A');

        $filename = 'AR_Detailed_' . $dateTo . '.csv';

        return response()->streamDownload(function () use ($rows, $dateTo) {
            $out = fopen('php://output', 'w');
            // 🔒 CSV helpers (export-only)
$fmtNumber = function ($v) {
    return number_format((float) ($v ?? 0), 2, '.', ',');
};

$fmtDate = function ($v) {
    if (
        !$v ||
        $v === '0' ||
        $v === '0000-00-00' ||
        $v === '00:00.0'
    ) return '';

    try {
        return \Carbon\Carbon::parse(trim($v))->format('Y-m-d');
    } catch (\Throwable $e) {
        return '';
    }
};


            // CSV HEADER
fputcsv($out, [
    'Agent',
    'Customer',
    'Invoice',
    'Invoice Date',
    'Payment Terms',
    'Current',
    '30 Days',
    '60 Days',
    '90 Days',
    'Over 120 Days',
    'Overdue Amount',
    'PDC',
    'Total Balance'
]);

$totalCurrent = 0;
$total30 = 0;
$total60 = 0;
$total90 = 0;
$total120 = 0;
$totalOverdue = 0;
$totalPDC = 0;
$totalBalance = 0;

           foreach ($rows as $index => $r) {

    $current  = (float) ($r['CurrentDay'] ?? $r['Current'] ?? 0);
    $d30      = (float) ($r['30DAYS'] ?? 0);
    $d60      = (float) ($r['60DAYS'] ?? 0);
    $d90      = (float) ($r['90DAYS'] ?? 0);
    $d120     = (float) ($r['Over120DAYS'] ?? 0);

   

// ================= TERM-BASED OVERDUE =================
$dueDays     = $this->extractDueDays($r['Terms'] ?? $r['Description'] ?? '');
$invoiceDate = $r['InvoiceDate'] ?? null;
$balance     = (float) ($r['Total'] ?? $r['Balance'] ?? 0);

$overdue = 0;

if ($invoiceDate && $balance > 0) {
    try {
        $dueDate = \Carbon\Carbon::parse($invoiceDate)
            ->addDays($dueDays)
            ->endOfDay();

        if ($dueDate->lt(\Carbon\Carbon::parse($dateTo))) {
            $overdue = $balance;
        }
    } catch (\Throwable $e) {}
}
$totalCurrent += $current;
$total30 += $d30;
$total60 += $d60;
$total90 += $d90;
$total120 += $d120;
$totalOverdue += $overdue;
$totalPDC += (float) ($r['PDC'] ?? 0);
$totalBalance += (float) ($r['Total'] ?? $r['Balance'] ?? 0);
// ================= CSV OUTPUT =================
fputcsv($out, [
    $r['Agent'] ?? $r['SalesAgent'] ?? '',
    $r['CustomerName'] ?? '',
    $r['Invoice'] ?? '',
    $fmtDate(
    $r['InvoiceDate']
    ?? $r['DocDate']
    ?? $r['RefDate']
    ?? $r['TaxDate']
    ?? null
),
    $r['Terms'] ?? $r['Description'] ?? '',
    $fmtNumber($current),
    $fmtNumber($d30),
    $fmtNumber($d60),
    $fmtNumber($d90),
    $fmtNumber($d120),
    $fmtNumber($overdue), // ✅ NOW INCLUDED
    $fmtNumber($r['PDC'] ?? 0),
    $fmtNumber($r['Total'] ?? $r['Balance'] ?? 0),
]);


}

/*
|--------------------------------------------------------------------------
| TOTAL ROW
|--------------------------------------------------------------------------
*/
fputcsv($out, [
    '',
    'TOTAL',
    '',
    '',
    '',
    number_format($totalCurrent, 2, '.', ','),
    number_format($total30, 2, '.', ','),
    number_format($total60, 2, '.', ','),
    number_format($total90, 2, '.', ','),
    number_format($total120, 2, '.', ','),
    number_format($totalOverdue, 2, '.', ','),
    number_format($totalPDC, 2, '.', ','),
    number_format($totalBalance, 2, '.', ','),
]);

fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

$hasYearFilter = $request->has('sparkYear');

$year = $hasYearFilter
    ? (int) $request->input('sparkYear')
    : null;
$today = Carbon::today();

if ($hasYearFilter) {
    // 🔒 User selected a year → freeze at year-end
    $asOf = Carbon::create($year, 12, 31);
} else {
    // 🟢 First load → use TODAY (real current)
    $asOf = $today;
}

$dateTo = min($asOf, $today)->toDateString();
// ================= AR Sparkline (Jan–Dec for selected year) =================
$arMonths = [];
$arValues = [];
$today = Carbon::today();

for ($m = 1; $m <= 12; $m++) {
$baseYear = $year ?? Carbon::today()->year;
$monthEnd = Carbon::create($baseYear, $m, 1)->endOfMonth();
// ⛔ Do NOT compute AR for future months
if ($monthEnd->greaterThan($today)) {
    $arMonths[] = $monthEnd->format('M');
    $arValues[] = 0; // future month = zero
    continue;
}


   $arRowsMonth = $this->getArRows($monthEnd->toDateString(), 'A');


    $monthTotal = 0.0;
   foreach ($arRowsMonth as $r) {
$monthTotal += (float) ($r['Balance'] ?? $r['Total'] ?? 0);
}

    $arMonths[] = Carbon::create($baseYear, $m, 1)->format('M');
    $arValues[] = round($monthTotal, 2);
}





        $rows = $this->getArRows($dateTo, 'A');
// ================= Current (0–29 days) Sparkline (Jan–Dec, YEAR-LOCKED) =================
$currentSpark = [];
$currentMonths = [];

for ($m = 1; $m <= 12; $m++) {
    $baseYear = $year ?? Carbon::today()->year;
$monthEnd = Carbon::create($baseYear, $m, 1)->endOfMonth();

    // Do not compute future months
    if ($monthEnd->greaterThan(Carbon::today())) {
        $currentMonths[] = $monthEnd->format('M');
        $currentSpark[] = 0;
        continue;
    }

    $rowsMonth = $this->getArRows($monthEnd->toDateString(), 'A');

    $currentTotal = 0.0;
    $currentTotal = 0.0;

    foreach ($rowsMonth as $r) {
    $currentTotal += (float) ($r['CurrentDay'] ?? $r['Current'] ?? 0);
}

    $currentMonths[] = $monthEnd->format('M');
    $currentSpark[] = round($currentTotal, 2);
}


$sums = [
    'signedTotal' => 0.0, // raw DB sum (can be negative)
    'gross'       => 0.0, // positive balances only
    'credits'     => 0.0, // absolute value of negatives
    'net'         => 0.0, // gross - credits

    'current' => 0.0,
    'd30'     => 0.0,
    'd60'     => 0.0,
    'd90'     => 0.0,
    'd120'    => 0.0,

    'trueOverdue' => 0.0
];

foreach ($rows as $index => $r) {
    $bal = (float) ($r['Total'] ?? $r['Balance'] ?? 0);

    // signed total (raw)
    $sums['signedTotal'] += $bal;

    // gross / credits split
    if ($bal >= 0) {
        $sums['gross'] += $bal;
    } else {
        $sums['credits'] += abs($bal);
    }

$sums['current'] += (float) ($r['CurrentDay'] ?? $r['Current'] ?? 0);
    $sums['d30']     += (float) ($r['30DAYS'] ?? 0);
    $sums['d60']     += (float) ($r['60DAYS'] ?? 0);
    $sums['d90']     += (float) ($r['90DAYS'] ?? 0);
    $sums['d120']    += (float) ($r['Over120DAYS'] ?? 0);
$invoiceDate =
    $r['InvoiceDate']
    ?? $r['DocDate']
    ?? $r['RefDate']
    ?? $r['TaxDate']
    ?? null;

$terms =
    $r['Terms']
    ?? $r['Description']
    ?? '';

$balance = (float) ($r['Total'] ?? $r['Balance'] ?? 0);

$dueDays = $this->extractDueDays($terms);

if ($invoiceDate && $balance > 0) {
    try {
        $invoice = Carbon::parse($invoiceDate)->startOfDay();
        $asOf    = Carbon::parse($dateTo)->endOfDay();

        $dueDate = $invoice->copy()->addDays($dueDays);

        if ($dueDate->lt($asOf)) {
            $sums['trueOverdue'] += $balance;

            // 🔥 Mark row as overdue (for frontend)
           $rows[$index]['IsOverdue'] = true;
        } else {
            $rows[$index]['IsOverdue'] = false;
        }

    } catch (\Throwable $e) {
        $rows[$index]['IsOverdue'] = false;
    }
}
}

// net AR (executive value)
$sums['net'] = round($sums['gross'] - $sums['credits'], 2);

      



// 🔥 ALIGN KPI CURRENT VALUE CORRECTLY (YEAR-AWARE)



$isSparkOnly = $request->hasHeader('X-Spark-Only');
if (($request->ajax() || $request->wantsJson()) && $isSparkOnly) {
    return response()->json([
        'meta' => [
            'sparkLabels'  => $arMonths,
            'sparkValues'  => $arValues,
            'currentSpark' => $currentSpark,
            'sums'         => $sums,
             'asOfDate'     => $dateTo,   // ✅ NOW IT EXISTS
        ]
    ]);
}


        $rows = $this->utf8ize($rows);

     $customers = [];

foreach ($rows as $r) {
    $name = trim((string)($r['CustomerName'] ?? 'Unknown'));

    if ($name === '') $name = 'Unknown';

    if (!isset($customers[$name])) {
    $customers[$name] = [
        'Agent' => $r['Agent'] ?? $r['SalesAgent'] ?? '—',
        'CustomerName' => $name,
        'Current' => 0,
        'd30' => 0,
        'd60' => 0,
        'd90' => 0,
        'Over120' => 0,
        'Overdue' => 0,
        'PDC' => 0,
        'Total' => 0,

        // ✅ ADD THESE
        'InvoiceCount' => 0,
        'OldestInvoiceDate' => null,
        'AvailableDate' => null,
         'invoices' => [],
    ];
}
$invoiceDate =
    $r['InvoiceDate']
    ?? $r['DocDate']
    ?? $r['RefDate']
    ?? $r['TaxDate']
    ?? null;

$terms =
    $r['Terms']
    ?? $r['Description']
    ?? '';

$balance = (float) ($r['Total'] ?? $r['Balance'] ?? 0);

if ($invoiceDate && $balance > 0) {
    try {
        $invoice = Carbon::parse($invoiceDate)->startOfDay();
        $dueDays = $this->extractDueDays($terms);
        $dueDate = $invoice->copy()->addDays($dueDays);

      $customers[$name]['Current'] += (float) ($r['CurrentDay'] ?? $r['Current'] ?? 0);

    } catch (\Throwable $e) {}
}
    $customers[$name]['d30'] += (float) ($r['30DAYS'] ?? 0);
    $customers[$name]['d60'] += (float) ($r['60DAYS'] ?? 0);
    $customers[$name]['d90'] += (float) ($r['90DAYS'] ?? 0);
    $customers[$name]['Over120'] += (float) ($r['Over120DAYS'] ?? 0);
    $customers[$name]['Total'] += (float) ($r['Total'] ?? $r['Balance'] ?? 0);
    $customers[$name]['InvoiceCount']++;
    $customers[$name]['invoices'][] = $r;

    if (!empty($r['InvoiceDate'])) {
    if (
        empty($customers[$name]['OldestInvoiceDate']) ||
        $r['InvoiceDate'] < $customers[$name]['OldestInvoiceDate']
    ) {
        $customers[$name]['OldestInvoiceDate'] = $r['InvoiceDate'];
    }
}

    if (!empty($r['IsOverdue'])) {
        $customers[$name]['Overdue'] += (float) ($r['Total'] ?? $r['Balance'] ?? 0);
    }

    // Only add PDC once per unique PDC date (not per invoice)
if (!empty($r['PDC']) && !empty($r['AvailableDate'])) {
    $pdcKey = ($r['PDC'] ?? 0) . '_' . ($r['AvailableDate'] ?? '');
    
    // Track unique PDC entries to avoid duplication
    if (!isset($customers[$name]['_pdc_tracked'])) {
        $customers[$name]['_pdc_tracked'] = [];
    }
    
    if (!in_array($pdcKey, $customers[$name]['_pdc_tracked'])) {
        $customers[$name]['_pdc_tracked'][] = $pdcKey;
        $customers[$name]['PDC'] += (float) $r['PDC'];
    }
}
    if (!empty($r['PDC']) && !empty($r['AvailableDate'])) {

    $date = $r['AvailableDate'];

    if (
        empty($customers[$name]['AvailableDate']) ||
        $date < $customers[$name]['AvailableDate']
    ) {
        $customers[$name]['AvailableDate'] = $date;
    }
}
}

$customersAggregated = array_values($customers);
        $payload = [
    'meta' => [
        'sums' => $sums,
        'totalRows' => count($rows),
        'pageSize' => 50,
        'sparkLabels' => $arMonths,
        'sparkValues' => $arValues,
        'currentSpark' => $currentSpark,
         'asOfDate'     => $dateTo, 
         'customersAggregated' => $customersAggregated,
    ],
    'rows' => $rows,
    'customers' => array_values(array_column($customersAggregated, 'CustomerName')),
    'dateTo' => $dateTo
];


        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($payload);
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ================= Customer Master (AR) =================
$arCustomers = DB::table('ArCustomer')
    ->select([
        'Customer',
        DB::raw('[Name] as CustomerName'),        // ✅ FIX
        'ShortName',
        DB::raw('[Salesperson] as SalesPerson'), // ✅ FIX
        'Area',
        'Telephone',
        'Contact',
        'Fax',
        DB::raw('[DocFaxContact] as FaxContact'), // ✅ FIX
        'Email',
        'CreditLimit'
    ])
    ->orderBy('Customer')
    ->get();
    
        return view('ar', [
            'jsonPayload' => $jsonPayload,
            'dateTo' => $dateTo,
            'arCustomers' => $arCustomers
        ]);
    }
public function exportOverdue(Request $request)
{
    $dateTo = $request->input('dateTo', date('Y-m-d'));

    $rows = $this->getArRows($dateTo, 'A');

    $filename = 'AR_Overdue_' . $dateTo . '.csv';

    return response()->streamDownload(function () use ($rows, $dateTo) {

        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Customer',
            'Invoice',
            'Invoice Date',
            'Terms',
            'Balance',
            'Age (Days)'
        ]);

        $totalBalance = 0;
        
        // ✅ MATCH DASHBOARD: Use endOfDay for asOf date
        $asOfDate = \Carbon\Carbon::parse($dateTo)->endOfDay();

        foreach ($rows as $r) {

            $dueDays = $this->extractDueDays(
                $r['Terms'] ?? $r['Description'] ?? ''
            );
            
            $invoiceDate =
                $r['InvoiceDate']
                ?? $r['DocDate']
                ?? $r['RefDate']
                ?? $r['TaxDate']
                ?? null;
                
            $balance = (float) ($r['Total'] ?? $r['Balance'] ?? 0);

            $isOverdue = false;

            if ($invoiceDate && $balance > 0) {
                try {
                    // ✅ MATCH DASHBOARD EXACTLY: NO endOfDay on dueDate
                    $dueDate = \Carbon\Carbon::parse($invoiceDate)
                        ->addDays($dueDays);

                    // Compare with endOfDay asOf (same as dashboard)
                    if ($dueDate->lt($asOfDate)) {
                        $isOverdue = true;
                    }
                } catch (\Throwable $e) {}
            }

            if (!$isOverdue) {
                continue;
            }
            
            $ageDays = 0;
            $totalBalance += $balance;
            
            if (!empty($invoiceDate)) {
                try {
                    // Calculate age from invoice to asOf date
                    $ageDays = \Carbon\Carbon::parse($invoiceDate)
                        ->startOfDay()
                        ->diffInDays($asOfDate);
                } catch (\Throwable $e) {
                    $ageDays = 0;
                }
            }
            
            fputcsv($out, [
                $r['CustomerName'] ?? '',
                $r['Invoice'] ?? '',
                $invoiceDate
                    ? \Carbon\Carbon::parse($invoiceDate)->format('Y-m-d')
                    : '',
                $r['Terms'] ?? $r['Description'] ?? '',
                number_format($balance, 2, '.', ','),
                $ageDays
            ]);
        }
        
        fputcsv($out, []);

        fputcsv($out, [
            'TOTAL',
            '',
            '',
            '',
            number_format($totalBalance, 2, '.', ','),
            ''
        ]);
        
        fclose($out);

    }, $filename, [
        'Content-Type' => 'text/csv'
    ]);
}
    public function arCustomerDetails(Request $request)
    {
        $customer = (string)$request->query('customer', '');
        $dateTo = $request->query('dateTo', date('Y-m-d'));

        if (trim($customer) === '') {
            return response()->json(['details' => []]);
        }

        $rows = $this->getArRows($dateTo, 'A');
        $rows = $this->utf8ize($rows);

        $search = trim($customer);
        $details = array_values(array_filter($rows, function ($row) use ($search) {
            $name = trim((string)($row['CustomerName'] ?? ''));
            $code = trim((string)($row['Customer'] ?? ''));
            if ($name === '') return false;
            if (strcasecmp($name, $search) === 0 || strcasecmp($code, $search) === 0) return true;
            if (stripos($name, $search) !== false) return true;
            if ($code !== '' && stripos($code, $search) !== false) return true;
            return false;
        }));

        return response()->json(['details' => $details]);
    }

    // =========================================================================
    // 4. AP - main page (server-rendered + JSON)
    // =========================================================================
    public function apIndex(Request $request)
    {
        $dateTo = $request->input('dateTo', date('Y-m-d'));
        $type   = $request->input('type', 'A');

        $asOf = Carbon::parse($dateTo)->endOfDay();

        // fetch invoice rows via stored proc helper (caching inside)
        $rows = $this->getApRows($dateTo, $type);
        $rows = $this->utf8ize($rows);

        // aggregate and sums
        $sums = [
            'total' => 0.0, 'current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0, 'd120' => 0.0
        ];
        $suppliers = [];
        
        foreach ($rows as $r) {
            $supplierName = trim((string)($r['SupplierName'] ?? $r['Supplier'] ?? 'Unknown'));
            if ($supplierName === '') $supplierName = 'Unknown';

            $current = (float) ($r['CurrentDay'] ?? 0);
            $d30 = (float) ($r['30 DAYS'] ?? $r['30DAYS'] ?? 0);
            $d60 = (float) ($r['60 DAYS'] ?? $r['60DAYS'] ?? 0);
            $d90 = (float) ($r['90 DAYS'] ?? $r['90DAYS'] ?? 0);
            $d120 = (float) ($r['OVER 120 DAYS'] ?? $r['Over120Days'] ?? 0);
            $pdc = max(0, (float) ($r['PDC'] ?? 0));



            $sums['current'] += $current;
            $sums['d30'] += $d30;
            $sums['d60'] += $d60;
            $sums['d90'] += $d90;
            $sums['d120'] += $d120;
if (!isset($suppliers[$supplierName])) {
    $suppliers[$supplierName] = [
        'SupplierName' => $supplierName,
        'PaymentTerms' => $r['Description'] ?? $r['PaymentTerms'] ?? $r['Terms'] ?? '—',
        'Current' => 0.0,
        'd30' => 0.0,
        'd60' => 0.0,
        'd90' => 0.0,
        'd120' => 0.0,
        'PDC' => 0.0,
        'Total' => 0.0,
        'invoices' => []
    ];
}

            $suppliers[$supplierName]['Current'] += $current;
            $suppliers[$supplierName]['d30'] += $d30;
            $suppliers[$supplierName]['d60'] += $d60;
            $suppliers[$supplierName]['d90'] += $d90;
            $suppliers[$supplierName]['d120'] += $d120;
            $suppliers[$supplierName]['PDC'] += $pdc;


$invoiceBalance =
      $current
    + $d30
    + $d60
    + $d90
    + $d120
    - $pdc;
$suppliers[$supplierName]['Total'] += $invoiceBalance;

$suppliers[$supplierName]['invoices'][] = [
    'Invoice' => $r['Invoice'] ?? null,
    'InvoiceDate' => $r['InvoiceDate'] ?? null,
    'Amount' => (float) ($r['OrigInvValue'] ?? 0),
    'Balance' => $invoiceBalance,
    'ChequeDate' => $r['ChequeDate'] ?? $r['Cheque_Date'] ?? null,
    'AgeingDays' => intval($r['AgeingDays'] ?? ($r['Age'] ?? 0)),
    'PaymentTerms' => $r['Description'] ?? $r['PaymentTerms'] ?? $r['Terms'] ?? '—',
];

        }
$sums['total'] = $this->computeApTotalFromRows($rows);
        $supplierList = array_values($suppliers);
        usort($supplierList, fn($a, $b) => ($b['Total'] ?? 0) <=> ($a['Total'] ?? 0));

        $topVendors = array_slice($supplierList, 0, 6);
        usort($topVendors, fn($a,$b) => ($a['Total'] ?? 0) <=> ($b['Total'] ?? 0));
        $criticalInvoices = array_filter($rows, function($r){
    $d90  = (float) ($r['90 DAYS'] ?? $r['90DAYS'] ?? 0);
    $d120 = (float) ($r['OVER 120 DAYS'] ?? $r['Over120Days'] ?? 0);

    return ($d90 + $d120) > 0;
});
usort($criticalInvoices, function($a, $b) {
    $aTotal = (float)($a['90 DAYS'] ?? $a['90DAYS'] ?? 0)
            + (float)($a['OVER 120 DAYS'] ?? $a['Over120Days'] ?? 0);

    $bTotal = (float)($b['90 DAYS'] ?? $b['90DAYS'] ?? 0)
            + (float)($b['OVER 120 DAYS'] ?? $b['Over120Days'] ?? 0);

    return $bTotal <=> $aTotal;
});

        $criticalInvoices = array_slice($criticalInvoices, 0, 6);

  $months = [];
$mEnd = Carbon::parse($dateTo)->startOfMonth();

// 🔥 LAST 12 MONTHS (including current month)
for ($i = 11; $i >= 0; $i--) {
    $dt = (clone $mEnd)->subMonths($i);
    $months[] = $dt->format('Y-m');
}


$monthly = array_fill_keys($months, 0.0);
$criticalMonthly = array_fill_keys($months, 0.0);

foreach ($months as $m) {
    $monthEndDate = Carbon::parse($m . '-01')
        ->endOfMonth()
        ->toDateString();

    $rowsAtMonthEnd = $this->getApRows($monthEndDate, $type);

    // ✅ Total AP snapshot
    $monthly[$m] = $this->computeApTotalFromRows($rowsAtMonthEnd);

    // ✅ Critical AP snapshot (>90 days)
    $crit = 0.0;
    foreach ($rowsAtMonthEnd as $r) {
        $crit +=
            (float) ($r['90 DAYS'] ?? $r['90DAYS'] ?? 0)
          + (float) ($r['OVER 120 DAYS'] ?? $r['Over120Days'] ?? 0);
    }
    $criticalMonthly[$m] = round($crit, 2);
}


$sparkValues = array_values($monthly);
$criticalSpark = array_values($criticalMonthly);



        // ================= CURRENT (0–29 DAYS) Sparkline =================
$currentMonthly = array_fill_keys($months, 0.0);

foreach ($rows as $r) {
    $invDate = $r['InvoiceDate'] ?? $r['InvDate'] ?? null;
    if (!$invDate) continue;

    try {
        $key = Carbon::parse($invDate)->format('Y-m');
        if (isset($currentMonthly[$key])) {
            // STRICTLY current (0–29 days) only
            $currentMonthly[$key] += (float) ($r['CurrentDay'] ?? 0);
        }
    } catch (\Throwable $e) {}
}

$currentSpark = array_values($currentMonthly);



        $payload = [
            'meta' => [
                'sums' => $sums,
                'dateTo' => $asOf->toDateString(),
                'months' => $months
            ],
            'rows' => $rows,
            'suppliers' => $supplierList,
            'topVendors' => $topVendors,
            'criticalInvoices' => $criticalInvoices,
            'sparkValues' => $sparkValues,
            'criticalSpark' => $criticalSpark,
            'currentSpark' => $currentSpark
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($payload);
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('ap', [
            'jsonPayload' => $jsonPayload,
            'dateTo' => $dateTo,
            'type' => $type
        ]);
    }

    public function apData(Request $request)
    {
        return $this->apIndex($request);
    }

    public function apCustomerDetails(Request $request)
    {
        $supplier = (string)$request->query('supplier', '');
        $dateTo = $request->query('dateTo', date('Y-m-d'));
        $type   = $request->query('type', 'A');

        if (trim($supplier) === '') {
            return response()->json(['details' => []]);
        }

        $rows = $this->getApRows($dateTo, $type);
        $rows = $this->utf8ize($rows);

        $search = trim($supplier);
        $details = array_values(array_filter($rows, function ($row) use ($search) {
            $name = trim((string)($row['SupplierName'] ?? ''));
            $code = trim((string)($row['Supplier'] ?? ''));
            if ($name === '') return false;
            if (strcasecmp($name, $search) === 0 || strcasecmp($code, $search) === 0) return true;
            if (stripos($name, $search) !== false) return true;
            if ($code !== '' && stripos($code, $search) !== false) return true;
            return false;
        }));

        return response()->json(['details' => $details]);
    }
    public function exportSupplierInvoices(Request $request)
{
    $supplier = $request->input('supplier');
    $dateTo = $request->input('dateTo', date('Y-m-d'));
    
    if (!$supplier) {
        return response()->json(['error' => 'Supplier name required'], 400);
    }
    
    $rows = $this->getApRows($dateTo, 'A');
    $rows = $this->utf8ize($rows);
    
    // Filter by supplier
    $supplierInvoices = array_filter($rows, function($row) use ($supplier) {
        $rowSupplier = trim($row['SupplierName'] ?? $row['Supplier'] ?? '');
        return strcasecmp($rowSupplier, $supplier) === 0;
    });
    
    // Create CSV
    $filename = 'supplier_invoices_' . str_replace(' ', '_', $supplier) . '_' . date('Y-m-d') . '.csv';
    
    return response()->streamDownload(function () use ($supplierInvoices, $supplier) {
        $out = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($out, [
            'Supplier Name',
            'Invoice',
            'Invoice Date',
            'Amount',
            'Balance',
            'Payment Terms'
        ]);
        
        // CSV Data
        foreach ($supplierInvoices as $r) {
            fputcsv($out, [
                $supplier,
                $r['Invoice'] ?? '',
                $r['InvoiceDate'] ?? '',
                number_format((float)($r['OrigInvValue'] ?? 0), 2),
                number_format((float)($r['Balance'] ?? 0), 2),
                $r['Description'] ?? $r['PaymentTerms'] ?? ''
            ]);
        }
        
        fclose($out);
    }, $filename, [
        'Content-Type' => 'text/csv',
    ]);
}
    // =========================================================================
    // Helper: call stored proc for AP w/ caching
    // =========================================================================
 private function getApRows(string $dateTo, string $type): array
{
    $dateKey = $dateTo ?: date('Y-m-d');
    $type = in_array($type, ['A','B']) ? $type : 'A';
    $cacheKey = "ap:data:v1:{$dateKey}:{$type}";

    $useCache = env('APP_DEBUG') ? false : true;

    if ($useCache) {
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
    }

    try {
        // Derive year/month from selected date
        $dt = \Carbon\Carbon::parse($dateTo);
        $year = (int) $dt->year;
        $period = (int) $dt->month;

        // ✅ CORRECTED: Separate SET NOCOUNT and EXEC statements
        // Use proper SQL Server stored procedure syntax
        $sql = "EXEC sp_APAgeing_Summary @DateTo = ?, @Year = ?, @Period = ?, @Type = ?";
        
        \Log::info("Executing AP Stored Procedure", [
            'dateTo' => $dateTo,
            'year' => $year,
            'period' => $period,
            'type' => $type
        ]);

        $raw = DB::select($sql, [$dateTo, $year, $period, $type]);

        \Log::info("AP Stored Procedure Result", [
            'rows_count' => count($raw),
            'first_row' => !empty($raw) ? (array)$raw[0] : null
        ]);

        // Convert to array
        $rows = array_map(fn($r) => (array)$r, $raw);

        // ✅ ONLY filter if year/period are explicitly provided (not null)
        // Remove this entire block if the stored procedure already filters correctly
        /*
        if ($year !== null || $period !== null) {
            $rows = array_filter($rows, function($row) use ($year, $period) {
                $invoiceDate = $row['InvoiceDate'] ?? $row['InvDate'] ?? null;
                if (!$invoiceDate) return true; // Keep rows without date
                
                try {
                    $dt = \Carbon\Carbon::parse($invoiceDate);
                    if ($year !== null && $dt->year != $year) return false;
                    if ($period !== null && $dt->month != $period) return false;
                    return true;
                } catch (\Throwable $e) {
                    return true; // Keep on error
                }
            });
        }
        */

        // Cache the results
        Cache::put($cacheKey, $rows, 300);
        
        \Log::info("AP rows fetched successfully", [
            'count' => count($rows), 
            'date' => $dateTo, 
            'type' => $type
        ]);
        
        return $rows;
        
    } catch (\Throwable $e) {
        \Log::error('AP stored procedure failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'dateTo' => $dateTo,
            'type' => $type
        ]);
        return [];
    }
}

    // =========================================================================
    // Helper: AR proc
    // =========================================================================
    private function getArRows(string $dateTo, string $type): array
    {
       
        $cacheKey = "ar:data:fresh:v3:{$dateTo}:{$type}";
        $useCache = env('APP_DEBUG') ? false : true;

        if ($useCache) {
            $rows = Cache::get($cacheKey);
            if (is_array($rows)) {
                return $rows;
            }
        }

        try {
            $query = "SET NOCOUNT ON; EXEC sp_ARAgeing_Detailed ?, ?";
            $raw = DB::select($query, [$dateTo, $type]);
            $rows = array_map(fn($r) => (array)$r, $raw);
            \Log::info("AR Data Fetched: " . count($rows) . " rows found.");
            Cache::put($cacheKey, $rows, 300);
            return $rows;
        } catch (\Throwable $e) {
            \Log::error('AR proc failed: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // Small utilities
    // =========================================================================
    private function utf8ize($d) {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = $this->utf8ize($v);
            }
        } else if (is_string($d)) {
            return mb_convert_encoding($d, 'UTF-8', 'UTF-8');
        }
        return $d;
    }

    private function computeApTotalFromRows(array $rows): float
{
    $total = 0.0;

    foreach ($rows as $r) {
        // If Balance already exists, trust it
        if (isset($r['Balance'])) {
            $total += (float) $r['Balance'];
            continue;
        }

        // Safe fallback (same logic as invoice balance)
        $total +=
            (float) ($r['CurrentDay'] ?? 0)
          + (float) ($r['30 DAYS'] ?? $r['30DAYS'] ?? 0)
          + (float) ($r['60 DAYS'] ?? $r['60DAYS'] ?? 0)
          + (float) ($r['90 DAYS'] ?? $r['90DAYS'] ?? 0)
          + (float) ($r['OVER 120 DAYS'] ?? $r['Over120Days'] ?? 0)
          - (float) ($r['PDC'] ?? 0);
    }

    return round($total, 2);
}

    private function extractDueDays(?string $terms): int
{
    if (!$terms) return 0;

    // Extract first number from terms like "90-DAYS"
    if (preg_match('/(\d+)/', $terms, $matches)) {
        return (int) $matches[1];
    }

    $terms = strtoupper(trim($terms));

    // Handle COD / CBD / CASH
    if (in_array($terms, ['COD', 'CBD', 'CASH'])) {
        return 0;
    }

    return 0;
}
    private function formatCurrency($value)
    {
        $value = (float) ($value ?? 0);
        if ($value >= 1000000000) return '₱' . number_format($value / 1000000000, 2) . 'B';
        if ($value >= 1000000) return '₱' . number_format($value / 1000000, 2) . 'M';
        if ($value >= 1000) return '₱' . number_format($value / 1000, 2) . 'K';
        return '₱' . number_format($value, 2);
    }

   public function customerDetails(Request $request)
    {
        $customer = $request->query('customer');
        if (!$customer) return response()->json(['details' => []]);

        $year  = $request->query('year');
        $month = $request->query('month');

        $details = DB::table('vSummarySalesCogs')
            ->select(['Invoice','ItemCode',DB::raw('[Item Description] as ItemDescription'),'Class','Qty','Amount','Category','Brand',DB::raw('[Sub Category] as SubCategory')])
            ->where('Customer Name', $customer)
            ->when($year, fn($q) => $q->where('TrnYear', $year))
            ->when($month, fn($q) => $q->where('TrnMonth', $month))
            ->orderByDesc('Amount')->get();

        return response()->json(['details' => $details]);
    }
    
public function productInvoiceDetails(Request $request)
{
    $item  = $request->query('item');
    $year  = $request->query('year');
    $month = $request->query('month');

    if (!$item) {
        return response()->json(['details' => []]);
    }

$rows = DB::table('vSummarySalesCogs')
    ->select([
        'Invoice',
        'ItemCode',
        'Class',
        DB::raw('[Customer Name] as CustomerName'),
        'TrnYear',
        'TrnMonth',
        'Qty',
        'Amount',
        DB::raw('[Item Description] as ItemDescription')
    ])

        ->where('Item Description', $item)
        ->when($year, fn($q) => $q->where('TrnYear', $year))
        ->when($month, fn($q) => $q->where('TrnMonth', $month))
        ->orderByDesc('Amount')
        ->get();

    return response()->json(['details' => $rows]);
}
}
