<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
class GrossProfitController extends Controller
{
    public function index(Request $request)
    {
     //$year = $request->input('year');
     //$month = $request->input('month');
     $year = $request->input('year') !== null ? (int) $request->input('year') : (int) date('Y');
$month = $request->input('month') !== null ? (int) $request->input('month') : (int) date('n'); // 'n' represents 1-12

if ($year === '' || $year === null) {
    $year = null;
} else {
    $year = (int) $year;
}

 if ($month !== null && $month !== '') {
        $month = (int) $month;
    } else {
        $month = null;
    }

$rows = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
    ->whereRaw("[CODE] = 'CATEGORY'")
    ->whereRaw("CAST([MONTH] AS INT) BETWEEN 1 AND 12")
    ->selectRaw("
      CAST([YEAR] AS INT) as trnyear,
CAST([MONTH] AS INT) as month,
        [CLASS] as CLASS,
        [CATEGORY] as subcategory,
        SUM([SALES]) as totalsales,
SUM([COGS]) as cogs
    ")
    ->when($year !== null, function ($q) use ($year) {
    $q->whereRaw("CAST([YEAR] AS INT) = ?", [$year]);
})
   ->when($month !== null, function ($q) use ($month) {
    $q->whereRaw("CAST([MONTH] AS INT) = ?", [$month]);
})
->groupByRaw("
    CAST([YEAR] AS INT),
    CAST([MONTH] AS INT),
    [CLASS],
    [CATEGORY]
")
    ->get();

$chartRows = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
    ->whereRaw("[CODE] = 'CATEGORY'")
    ->whereRaw("CAST([MONTH] AS INT) BETWEEN 1 AND 12")
    ->selectRaw("
        CAST([YEAR] AS INT) as trnyear,

        CAST([MONTH] AS INT) as month,

        SUM([SALES]) as totalsales,
        SUM([COGS]) as cogs
    ")
    ->when($year !== null, function ($q) use ($year) {
    $q->whereRaw("CAST([YEAR] AS INT) = ?", [$year]);
})
->when($month !== null, function ($q) use ($month) {
    $q->whereRaw("CAST([MONTH] AS INT) = ?", [$month]);
})
    ->groupByRaw("
    CAST([YEAR] AS INT),
    CAST([MONTH] AS INT)
")
    ->get();


    $rows = $rows->map(function ($r) {

    $sales = (float) $r->totalsales;
    $cogs  = (float) $r->cogs;

    $gp = $sales - $cogs;

    $r->gp_amount = $gp;
    $r->gp_percentage = $sales > 0
        ? round(($gp / $sales) * 100, 2)
        : 0;

    return $r;
});

    $gpTotalSales  = $rows->sum('totalsales');
$gpTotalCogs = $rows->sum('cogs');
$gpTotalAmount = $rows->sum('gp_amount');

$gpMarginPercent = $gpTotalSales > 0
    ? ($gpTotalAmount / $gpTotalSales) * 100
    : 0;
    $isAllYears = $year === null;

$yearlyData = $rows
    ->groupBy('trnyear')
    ->map(function ($group) {

        $sales = $group->sum('totalsales');
        $cogs  = $group->sum('cogs');
        $gp    = $group->sum('gp_amount');

        return [
            'year' => $group->first()->trnyear,
            'sales' => $sales,
            'cogs' => $cogs,
            'gp' => $gp,
            'margin' => $sales > 0 ? ($gp / $sales) * 100 : 0
        ];
    })
    ->sortByDesc('year')
    ->values();
/** ================= MONTHLY MAPS ================= */
$monthlyGpMap = array_fill(1, 12, 0);
$monthlySalesMap = array_fill(1, 12, 0);
$monthlyCogsMap = array_fill(1, 12, 0);

foreach ($chartRows as $r) {

    $rowMonth = (int) $r->month;

    if ($rowMonth < 1 || $rowMonth > 12) continue;

    $sales = (float) $r->totalsales;
    $cogs  = (float) $r->cogs;

    $monthlySalesMap[$rowMonth] += $sales;
    $monthlyCogsMap[$rowMonth] += $cogs;
    $monthlyGpMap[$rowMonth] += ($sales - $cogs);
}

if ($month === null) {
    for ($i = 1; $i <= 12; $i++) {
        $monthlySalesMap[$i] = $monthlySalesMap[$i] ?? 0;
        $monthlyCogsMap[$i] = $monthlyCogsMap[$i] ?? 0;
        $monthlyGpMap[$i] = $monthlyGpMap[$i] ?? 0;
    }
}
ksort($monthlyGpMap);
ksort($monthlySalesMap);
ksort($monthlyCogsMap);
        /** ================= CLASS → MARGIN MAP ================= */
$classMargins = $rows->groupBy('CLASS')->map(function ($g) {
    $sales = $g->sum(fn ($r) => (float) $r->totalsales);
    $gp    = $g->sum(fn ($r) => (float) $r->gp_amount);

    return $sales > 0 
        ? round(($gp / $sales) * 100, 2)
        : 0;
});

        return view('gross-profit.index', compact(
            'rows',
            'gpTotalSales',
            'gpTotalCogs',
            'gpTotalAmount',
            'gpMarginPercent',
            'year',
            'month',
            'monthlyGpMap',
            'monthlySalesMap',
            'monthlyCogsMap',
            'classMargins',
            'yearlyData',      
    'isAllYears' 
        ));
    }
public function customerData(Request $request)
{
    $year = $request->input('year');
    $month = $request->input('month');

    \Log::info('Customer Data Request', [
        'year' => $year,
        'month' => $month
    ]);

    try {
        // Map integer month to name (e.g., 1 => 'January')
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $monthName = isset($monthNames[(int)$month]) ? $monthNames[(int)$month] : null;

        // Query the View directly and aggregate by Customer
        $data = DB::table('vGrossProfitPerCustomer')
            ->selectRaw("
                Customer as CUSTOMER,
                SUM(TotalSales) as sales,
                SUM(Cogs) as cogs,
                SUM(GP_Amount) as gp,
                CASE 
                    WHEN SUM(TotalSales) <> 0 
                    THEN (SUM(GP_Amount) / SUM(TotalSales)) * 100 
                    ELSE 0 
                END as margin
            ")
            ->when($year, function ($q) use ($year) {
                $q->where('Year', $year);
            })
            ->when($monthName, function ($q) use ($monthName) {
                $q->where('Month', $monthName);
            })
            ->groupBy('Customer')
            ->havingRaw('SUM(TotalSales) <> 0') // Only show customers with actual sales
            ->orderByDesc('sales')
            ->get();

        \Log::info('Customer Data Count', ['count' => $data->count()]);

        // Transform the data to match frontend expectations
        $transformed = $data->map(function ($r) {
            return [
                'CUSTOMER' => $r->CUSTOMER,
                'sales' => (float) $r->sales,
                'cogs' => (float) $r->cogs,
                'gp' => (float) $r->gp,
                'margin' => round((float) $r->margin, 2)
            ];
        });

        return response()->json([
            'data' => $transformed,
            'current_page' => 1,
            'last_page' => 1,
            'total' => $transformed->count(),
            'per_page' => 25
        ]);

    } catch (\Exception $e) {
        \Log::error('Customer Data Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}
public function categoryBreakdown(Request $request)
{
    try {
        $year = $request->input('year');
        $customer = $request->input('category');

        if (!$customer) {
            return response()->json([]);
        }

        \Log::info('Category Breakdown Request', [
            'customer' => $customer,
            'year' => $year
        ]);

        // Map integer year to filter
        $year = $year ? (int) $year : null;

        // Query the View directly for monthly breakdown (same as customerData)
        $rows = DB::table('vGrossProfitPerCustomer')
            ->selectRaw("
                CASE Month
                    WHEN 'January' THEN 1
                    WHEN 'February' THEN 2
                    WHEN 'March' THEN 3
                    WHEN 'April' THEN 4
                    WHEN 'May' THEN 5
                    WHEN 'June' THEN 6
                    WHEN 'July' THEN 7
                    WHEN 'August' THEN 8
                    WHEN 'September' THEN 9
                    WHEN 'October' THEN 10
                    WHEN 'November' THEN 11
                    WHEN 'December' THEN 12
                END as month,
                Month,
                SUM(TotalSales) as sales,
                SUM(Cogs) as cogs,
                SUM(GP_Amount) as gp
            ")
            ->where('Customer', $customer)
            ->when($year, fn($q) => $q->where('Year', $year))
            ->groupBy('Month')
            ->orderByRaw("
                CASE Month
                    WHEN 'January' THEN 1
                    WHEN 'February' THEN 2
                    WHEN 'March' THEN 3
                    WHEN 'April' THEN 4
                    WHEN 'May' THEN 5
                    WHEN 'June' THEN 6
                    WHEN 'July' THEN 7
                    WHEN 'August' THEN 8
                    WHEN 'September' THEN 9
                    WHEN 'October' THEN 10
                    WHEN 'November' THEN 11
                    WHEN 'December' THEN 12
                END
            ")
            ->get();

        \Log::info('Category Breakdown Results', ['count' => $rows->count()]);

        return response()->json($rows);

    } catch (\Throwable $e) {
        \Log::error('categoryBreakdown error', [
            'message' => $e->getMessage(),
            'customer' => $request->input('category'),
            'year' => $request->input('year'),
        ]);

        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
        ], 500);
    }
}
public function productData(Request $request)
{
    $year = $request->input('year');
    $month = $request->input('month');

    \Log::info('Product Data Request', [
        'year' => $year,
        'month' => $month
    ]);

    try {
        // Map integer month to name (e.g., 1 => 'January')
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        $monthName = isset($monthNames[(int)$month]) ? $monthNames[(int)$month] : null;

        // ✅ USE the existing vGrossProfitPerItem view
        $data = DB::table('vGrossProfitPerItem')
            ->selectRaw("
                SubCategory as PRODUCT,
                SUM(TotalSales) as sales,
                SUM(Cogs) as cogs,
                SUM(GP_Amount) as gp,
                CASE 
                    WHEN SUM(TotalSales) <> 0 
                    THEN (SUM(GP_Amount) / SUM(TotalSales)) * 100 
                    ELSE 0 
                END as margin
            ")
            ->when($year, function ($q) use ($year) {
                $q->where('TrnYear', $year);
            })
            ->when($monthName, function ($q) use ($monthName) {
                $q->where('Month', $monthName);  // ✅ Filter by month NAME not number
            })
            ->whereNotNull('SubCategory')
            ->where('SubCategory', '<>', '')
            ->groupBy('SubCategory')
            ->havingRaw('SUM(TotalSales) <> 0')
            ->orderByDesc('sales')
            ->get();

        \Log::info('Product Data Count', ['count' => $data->count()]);

        // Calculate total sales for share calculation
        $totalSales = $data->sum('sales');

        $transformed = $data->map(function ($r) use ($totalSales) {
            $sales = (float) $r->sales;
            $cogs = (float) $r->cogs;
            $gp = (float) $r->gp;

            return [
                'ItemCode' => $r->PRODUCT,
                'PRODUCT' => $r->PRODUCT,
                'sales' => $sales,
                'cogs' => $cogs,
                'gp' => $gp,
                'margin' => round((float) $r->margin, 2),
                'share' => $totalSales > 0 ? round(($sales / $totalSales) * 100, 2) : 0
            ];
        });

        return response()->json([
            'data' => $transformed,
            'current_page' => 1,
            'last_page' => 1,
            'total' => $transformed->count(),
            'per_page' => 25
        ]);

    } catch (\Exception $e) {
        \Log::error('Product Data Error', [
            'message' => $e->getMessage()
        ]);
        
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}
public function productMonthly(Request $request)
{
    $product = $request->input('product');
    $year = $request->input('year');

    \Log::info('Product Monthly Request', [
        'product' => $product,
        'year' => $year
    ]);

    /* PROTECT AGAINST EMPTY PRODUCT */
    if(!$product){
        return response()->json([]);
    }

    $year = $year ? (int) $year : null;

    // ✅ USE THE VIEW for consistency
    $rows = DB::table('vGrossProfitPerItem')
        ->selectRaw("
            CASE Month
                WHEN 'January' THEN 1
                WHEN 'February' THEN 2
                WHEN 'March' THEN 3
                WHEN 'April' THEN 4
                WHEN 'May' THEN 5
                WHEN 'June' THEN 6
                WHEN 'July' THEN 7
                WHEN 'August' THEN 8
                WHEN 'September' THEN 9
                WHEN 'October' THEN 10
                WHEN 'November' THEN 11
                WHEN 'December' THEN 12
            END as month,
            Month,
            SUM(TotalSales) as sales,
            SUM(Cogs) as cogs,
            SUM(GP_Amount) as gp
        ")
        ->where('SubCategory', $product)
        ->when($year, fn($q) => $q->where('TrnYear', $year))
        ->groupBy('Month')
        ->orderByRaw("
            CASE Month
                WHEN 'January' THEN 1
                WHEN 'February' THEN 2
                WHEN 'March' THEN 3
                WHEN 'April' THEN 4
                WHEN 'May' THEN 5
                WHEN 'June' THEN 6
                WHEN 'July' THEN 7
                WHEN 'August' THEN 8
                WHEN 'September' THEN 9
                WHEN 'October' THEN 10
                WHEN 'November' THEN 11
                WHEN 'December' THEN 12
            END
        ")
        ->get();

    \Log::info('Product Monthly Result', ['count' => $rows->count()]);

    return response()->json($rows);
}
public function agentData(Request $request)
{
    try {

        $year = $request->input('year');
        $month = $request->input('month');
        $query = DB::table('dbo.vSummarySalesCongsMonthYear_01_Agent_01')
            ->selectRaw("
                AGENT,
                SUM(COALESCE(SALES,0)) as sales,
                SUM(COALESCE(COGS,0)) as cogs
            ")
            ->whereNotNull('AGENT')
            ->where('AGENT', '<>', '')

        ->when($year, function ($q) use ($year) {
                $q->whereRaw('[YEAR] = ?', [$year]);
            })

            ->when($month, function ($q) use ($month) {
    $q->whereRaw("CAST([MONTH] AS INT) = ?", [$month]);
});
        
        // ✅ MAIN DATA
    $key = "agent_data_" 
    . ($year ?? 'all') . "_" 
    . ($month ?? 'all') . "_page_" 
    . request()->get('page', 1);

$rows = Cache::remember($key, 120, function () use ($query) {
    return $query
        ->groupBy('AGENT')
        ->orderByDesc('sales')
        ->paginate(25);
});

        // ✅ TOTAL SALES (CLONE QUERY)
        $totalSales = DB::table('vSummarySalesCongsMonthYear_01_Agent_01')
            ->when($year, fn($q) => $q->whereRaw('[YEAR] = ?', [$year]))
 ->when($month, function ($q) use ($month) {
    $q->whereRaw("CAST([MONTH] AS INT) = ?", [$month]);
})
            ->sum(DB::raw('COALESCE(SALES,0)'));

        // ✅ TRANSFORM
        $rows->getCollection()->transform(function ($r) use ($totalSales) {

            $gp = $r->sales - $r->cogs;

            return [
                'AGENT' => $r->AGENT,
                'sales' => (float) $r->sales,
                'cogs' => (float) $r->cogs,
                'gp' => (float) $gp,
                'margin' => $r->sales > 0 ? round(($gp / $r->sales) * 100, 2) : 0,
                'share' => $totalSales > 0 ? round(($r->sales / $totalSales) * 100, 2) : 0
            ];
        });

        return response()->json($rows);

    } catch (\Throwable $e) {

        \Log::error('AGENT ERROR', [
            'message' => $e->getMessage(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}
public function agentMonthly(Request $request)
{
    try {

        $agent = $request->input('agent');
        $year = $request->input('year');

        if (!$agent) {
            return response()->json([]);
        }
$rows = DB::table('vSummarySalesCongsMonthYear_01_Agent_01')
    ->selectRaw("
        MONTH,
        SUM(COALESCE(SALES,0)) as sales,
        SUM(COALESCE(COGS,0)) as cogs
    ")
    ->where('AGENT', $agent)
    ->when($year, fn($q) => $q->whereRaw('[YEAR] = ?', [$year]))
    ->groupBy('MONTH')
    ->orderBy('MONTH')
    ->get();

\Log::info('AGENT MONTHLY RESULT', [
    'agent' => $agent,
    'rows' => $rows
]);

        return response()->json($rows);

    } catch (\Throwable $e) {

        \Log::error('AGENT MONTHLY ERROR', [
            'message' => $e->getMessage(),
            'agent' => $agent,
            'year' => $year
        ]);

        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}
public function divisionData(Request $request)
{
    try {
        $year = $request->input('year');
        $month = $request->input('month');
        
        \Log::info('Division Data Request', ['year' => $year]);

        // Build the query
        $query = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
            ->selectRaw("
                [CLASS] as DIVISION,
                SUM(ISNULL(SALES,0)) as sales,
                SUM(ISNULL(COGS,0)) as cogs
            ")
            ->whereRaw("[CODE] = 'CATEGORY'")
            ->whereRaw("[CLASS] IS NOT NULL")
            ->whereRaw("[CLASS] <> ''");
            
        if ($year) {
            $query->whereRaw("[YEAR] = ?", [(int) $year]);
        }
        if ($month) {
    $query->whereRaw("CAST([MONTH] AS INT) = ?", [(int) $month]); // ✅ ADD THIS
}

        \Log::info('Division Query', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);

        $rows = $query
            ->groupByRaw("[CLASS]")
            ->orderByDesc('sales')
            ->get();

        \Log::info('Division Rows Fetched', ['count' => $rows->count()]);

        if ($rows->isEmpty()) {
            \Log::warning('No division data found', ['year' => $year]);
            return response()->json([]);
        }

        // Calculate total sales (MUST INCLUDE MONTH FILTER)
$totalSalesQuery = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
    ->whereRaw("[CODE] = 'CATEGORY'");
    
if ($year) {
    $totalSalesQuery->whereRaw("[YEAR] = ?", [(int) $year]);
}

if ($month) {  // ✅ ADD THIS - CRITICAL FIX
    $totalSalesQuery->whereRaw("CAST([MONTH] AS INT) = ?", [(int) $month]);
}

$totalSales = $totalSalesQuery->sum(DB::raw("ISNULL(SALES,0)"));
        
        \Log::info('Total Sales', ['total' => $totalSales]);

        // Transform the data
        $rows = $rows->map(function ($r) use ($totalSales) {
            $gp = $r->sales - $r->cogs;

            return [
                'DIVISION' => $r->DIVISION,
                'sales' => (float) $r->sales,
                'cogs' => (float) $r->cogs,
                'gp' => (float) $gp,
                'margin' => $r->sales > 0
                    ? round(($gp / $r->sales) * 100, 2)
                    : 0,
                'share' => $totalSales > 0
                    ? round(($r->sales / $totalSales) * 100, 2)
                    : 0
            ];
        });

        return response()->json($rows);

    } catch (\Throwable $e) {
        \Log::error('DIVISION ERROR', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function divisionMonthly(Request $request)
{
    try {
        $division = $request->input('division');
        $year = $request->input('year');
        $month = $request->input('month');
        \Log::info('Division Monthly Request', [
            'division' => $division,
            'year' => $year
        ]);

        if (!$division) {
            return response()->json([]);
        }

        $key = "division_monthly_" . md5($division . '_' . ($year ?? ''));

        $rows = Cache::remember($key, 300, function () use ($division, $year) {
            $query = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
                ->selectRaw("
                    [MONTH],
                    SUM(ISNULL(SALES,0)) as sales,
                    SUM(ISNULL(COGS,0)) as cogs
                ")
                ->whereRaw("[CODE] = 'CATEGORY'")
                ->whereRaw("[CLASS] = ?", [$division]);
                
            if ($year) {
                $query->whereRaw("[YEAR] = ?", [(int) $year]);
            }
            
            return $query
                ->groupByRaw("[MONTH]")
                ->orderByRaw("
                    CASE 
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'JAN%' THEN 1
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'FEB%' THEN 2
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'MAR%' THEN 3
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'APR%' THEN 4
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'MAY%' THEN 5
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'JUN%' THEN 6
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'JUL%' THEN 7
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'AUG%' THEN 8
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'SEP%' THEN 9
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'OCT%' THEN 10
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'NOV%' THEN 11
                        WHEN UPPER(LTRIM(RTRIM([MONTH]))) LIKE 'DEC%' THEN 12
                        ELSE 99
                    END
                ")
                ->get();
        });

        \Log::info('Division Monthly Result', [
            'division' => $division,
            'count' => $rows->count()
        ]);

        return response()->json($rows);

    } catch (\Throwable $e) {
        \Log::error('DIVISION MONTHLY ERROR', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}
public function exportCustomers(Request $req)
{
    $year = $req->input('year');
    $month = $req->input('month');

    // Map integer month to name
    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    $monthName = isset($monthNames[(int)$month]) ? $monthNames[(int)$month] : null;

    // Query the View - get raw sums, calculate GP and Margin in PHP
    $data = DB::table('vGrossProfitPerCustomer')
        ->selectRaw("
            Customer,
            SUM(TotalSales) as TotalSales,
            SUM(Cogs) as TotalCOGS,
            SUM(GP_Amount) as GrossProfit
        ")
        ->when($year, function ($q) use ($year) {
            $q->where('Year', $year);
        })
        ->when($monthName, function ($q) use ($monthName) {
            $q->where('Month', $monthName);
        })
        ->groupBy('Customer')
        ->havingRaw('SUM(Cogs) <> 0')
        ->orderByDesc('TotalCOGS')
        ->get();

    // Debug logging
    \Log::info('Export Customers Debug', [
        'total_rows' => $data->count(),
        'total_cogs' => $data->sum('TotalCOGS'),
        'total_sales' => $data->sum('TotalSales'),
        'total_gp' => $data->sum('GrossProfit'),
        'expected_rows' => 885,
        'expected_cogs' => 67011385.43
    ]);

    $response = new StreamedResponse(function () use ($data, $year, $monthName) {
        $handle = fopen('php://output', 'w');

        // Header
        $yearLabel = $year ?? 'All Years';
        $monthLabel = $monthName ?? 'All Months';

        fputcsv($handle, ['Customer Gross Profit Report', $yearLabel, $monthLabel]);
        fputcsv($handle, []);
        fputcsv($handle, ['Customer', 'Net Sales', 'Net COGS', 'Gross Profit', 'Margin %']);

        $totalSales = 0;
        $totalCogs = 0;
        $totalGp = 0;
        $validRows = 0;

        foreach ($data as $r) {
            $sales = (float) $r->TotalSales;
            $cogs = (float) $r->TotalCOGS;
            $gp = (float) $r->GrossProfit;
            
            // ✅ Calculate margin correctly: (GP / Sales) * 100
            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;

            $totalSales += $sales;
            $totalCogs += $cogs;
            $totalGp += $gp;
            $validRows++;

            fputcsv($handle, [
                $r->Customer,
                number_format($sales, 2),
                number_format($cogs, 2),
                number_format($gp, 2),
                number_format($margin, 2) . '%',
            ]);
        }

        // Totals - calculate overall margin from totals
        $totalMargin = $totalSales > 0 ? ($totalGp / $totalSales) * 100 : 0;

        fputcsv($handle, []);
        fputcsv($handle, [
            'TOTAL (' . $validRows . ' customers)',
            number_format($totalSales, 2),
            number_format($totalCogs, 2),
            number_format($totalGp, 2),
            number_format($totalMargin, 2) . '%'
        ]);

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set(
        'Content-Disposition', 
        'attachment; filename="customer_gp_' . ($year ?? 'all') . '_' . ($month ?? 'all') . '.csv"'
    );

    return $response;
}
public function exportCustomerMonthly(Request $req)
{
    $customer = $req->input('customer');
    $year = $req->input('year');

    if (!$customer) {
        abort(400, 'Customer is required');
    }

    $year = $year ? (int) $year : null;

    // Query the View directly for consistency
    $rows = DB::table('vGrossProfitPerCustomer')
        ->selectRaw("
            CASE Month
                WHEN 'January' THEN 1
                WHEN 'February' THEN 2
                WHEN 'March' THEN 3
                WHEN 'April' THEN 4
                WHEN 'May' THEN 5
                WHEN 'June' THEN 6
                WHEN 'July' THEN 7
                WHEN 'August' THEN 8
                WHEN 'September' THEN 9
                WHEN 'October' THEN 10
                WHEN 'November' THEN 11
                WHEN 'December' THEN 12
            END as month,
            Month,
            SUM(TotalSales) as sales,
            SUM(Cogs) as cogs,
            SUM(GP_Amount) as gp
        ")
        ->where('Customer', $customer)
        ->when($year, fn($q) => $q->where('Year', $year))
        ->groupBy('Month')
        ->get();

    // Map months 1–12
    $monthMap = [];
    foreach ($rows as $r) {
        $monthMap[(int)$r->month] = $r;
    }

    $filename = "customer_monthly_" . now()->timestamp . ".csv";

    return response()->streamDownload(function () use ($monthMap, $customer, $year) {
        $handle = fopen('php://output', 'w');

        // Header
        $yearLabel = $year ?? 'All Years';
        fputcsv($handle, ['Customer Monthly Report', $customer, $yearLabel]);
        fputcsv($handle, []);
        fputcsv($handle, ['Month', 'Sales', 'COGS', 'Gross Profit', 'Margin %']);

        $totalSales = 0;
        $totalCogs = 0;
        $totalGp = 0;

        for ($m = 1; $m <= 12; $m++) {
            $r = $monthMap[$m] ?? null;
            $sales = $r ? (float)$r->sales : 0;
            $cogs  = $r ? (float)$r->cogs : 0;
            $gp    = $r ? (float)$r->gp : 0;  // ✅ USE GP from view (already correct)
            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;

            $totalSales += $sales;
            $totalCogs  += $cogs;
            $totalGp    += $gp;

            fputcsv($handle, [
                date('M', mktime(0,0,0,$m,1)),
                number_format($sales, 2),
                number_format($cogs, 2),
                number_format($gp, 2),
                number_format($margin, 2) . '%'
            ]);
        }

        // Totals
        $totalMargin = $totalSales > 0 ? ($totalGp / $totalSales) * 100 : 0;
        fputcsv($handle, []);
        fputcsv($handle, [
            'TOTAL',
            number_format($totalSales, 2),
            number_format($totalCogs, 2),
            number_format($totalGp, 2),
            number_format($totalMargin, 2) . '%'
        ]);

        fclose($handle);
    }, $filename);
}
public function exportProducts(Request $req)
{
    $year = $req->input('year');
    $month = $req->input('month');

    // Map integer month to name
    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    $monthName = isset($monthNames[(int)$month]) ? $monthNames[(int)$month] : null;

    // ✅ EXACT match to your SQL query - NO filtering on SubCategory
    $data = DB::table('vGrossProfitPerItem')
        ->selectRaw("
            SubCategory,
            SUM(TotalSales) as TotalSales,
            SUM(Cogs) as TotalCOGS,
            SUM(GP_Amount) as GrossProfit
        ")
        ->when($year, function ($q) use ($year) {
            $q->where('TrnYear', $year);
        })
        ->when($monthName, function ($q) use ($monthName) {
            $q->where('Month', $monthName);
        })
        ->groupBy('SubCategory')
        ->havingRaw('SUM(TotalSales) <> 0 OR SUM(Cogs) <> 0')  // ✅ Include products with COGS even if sales=0
        ->orderByDesc('TotalSales')
        ->get();

    // Calculate totals
    $grandTotalSales = $data->sum('TotalSales');
    $grandTotalCogs = $data->sum('TotalCOGS');
    $grandTotalGp = $data->sum('GrossProfit');

    // Debug logging
    \Log::info('Export Products Debug', [
        'total_rows' => $data->count(),
        'total_cogs' => $grandTotalCogs,
        'total_sales' => $grandTotalSales,
        'total_gp' => $grandTotalGp,
        'expected_rows' => 44,
        'expected_cogs' => 67011385.43,
        'expected_gp' => 18178053.23
    ]);

    $response = new StreamedResponse(function () use ($data, $year, $monthName, $grandTotalSales) {
        $handle = fopen('php://output', 'w');

        // Header
        $yearLabel = $year ?? 'All Years';
        $monthLabel = $monthName ?? 'All Months';

        fputcsv($handle, ['Product Gross Profit Report', $yearLabel, $monthLabel]);
        fputcsv($handle, []);
        fputcsv($handle, ['Product', 'Net Sales', 'Net COGS', 'Gross Profit', 'Margin %', 'Share %']);

        $totalSales = 0;
        $totalCogs = 0;
        $totalGp = 0;
        $validRows = 0;

        foreach ($data as $r) {
            $sales = (float) $r->TotalSales;
            $cogs = (float) $r->TotalCOGS;
            $gp = (float) $r->GrossProfit;
            
            // ✅ Calculate margin: (GP / Sales) * 100
            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;
            
            // ✅ Calculate share: (Product Sales / Total Sales) * 100
            $share = $grandTotalSales > 0 ? ($sales / $grandTotalSales) * 100 : 0;

            $totalSales += $sales;
            $totalCogs += $cogs;
            $totalGp += $gp;
            $validRows++;

            fputcsv($handle, [
                $r->SubCategory,
                number_format($sales, 2),
                number_format($cogs, 2),
                number_format($gp, 2),
                number_format($margin, 2) . '%',
                number_format($share, 2) . '%',
            ]);
        }

        // Totals
        $totalMargin = $totalSales > 0 ? ($totalGp / $totalSales) * 100 : 0;

        fputcsv($handle, []);
        fputcsv($handle, [
            'TOTAL (' . $validRows . ' products)',
            number_format($totalSales, 2),
            number_format($totalCogs, 2),
            number_format($totalGp, 2),
            number_format($totalMargin, 2) . '%',
            '100.00%'
        ]);

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set(
        'Content-Disposition', 
        'attachment; filename="product_gp_' . ($year ?? 'all') . '_' . ($month ?? 'all') . '.csv"'
    );

    return $response;
}
public function exportProductMonthly(Request $req)
{
    $product = $req->input('product');
    $year = $req->input('year');

    if (!$product) {
        abort(400, 'Product is required');
    }

    $year = $year ? (int) $year : null;

    // ✅ USE THE VIEW for consistency
    $rows = DB::table('vGrossProfitPerItem')
        ->selectRaw("
            CASE Month
                WHEN 'January' THEN 1
                WHEN 'February' THEN 2
                WHEN 'March' THEN 3
                WHEN 'April' THEN 4
                WHEN 'May' THEN 5
                WHEN 'June' THEN 6
                WHEN 'July' THEN 7
                WHEN 'August' THEN 8
                WHEN 'September' THEN 9
                WHEN 'October' THEN 10
                WHEN 'November' THEN 11
                WHEN 'December' THEN 12
            END as month,
            Month,
            SUM(TotalSales) as sales,
            SUM(Cogs) as cogs,
            SUM(GP_Amount) as gp
        ")
        ->where('SubCategory', $product)
        ->when($year, fn($q) => $q->where('TrnYear', $year))
        ->groupBy('Month')
        ->get();

    // Map months 1–12
    $monthMap = [];
    foreach ($rows as $r) {
        $monthMap[(int)$r->month] = $r;
    }

    $filename = "product_monthly_" . now()->timestamp . ".csv";

    return response()->streamDownload(function () use ($monthMap, $product, $year) {
        $handle = fopen('php://output', 'w');

        // Header
        $yearLabel = $year ?? 'All Years';
        fputcsv($handle, ['Product Monthly Report', $product, $yearLabel]);
        fputcsv($handle, []);
        fputcsv($handle, ['Month', 'Sales', 'COGS', 'Gross Profit', 'Margin %']);

        $totalSales = 0;
        $totalCogs = 0;
        $totalGp = 0;

        for ($m = 1; $m <= 12; $m++) {
            $r = $monthMap[$m] ?? null;
            $sales = $r ? (float)$r->sales : 0;
            $cogs  = $r ? (float)$r->cogs : 0;
            $gp    = $r ? (float)$r->gp : 0;  // ✅ USE GP from view (already correct)
            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;

            $totalSales += $sales;
            $totalCogs  += $cogs;
            $totalGp    += $gp;

            fputcsv($handle, [
                date('M', mktime(0,0,0,$m,1)),
                number_format($sales, 2),
                number_format($cogs, 2),
                number_format($gp, 2),
                number_format($margin, 2) . '%'
            ]);
        }

        // Totals
        $totalMargin = $totalSales > 0 ? ($totalGp / $totalSales) * 100 : 0;
        fputcsv($handle, []);
        fputcsv($handle, [
            'TOTAL',
            number_format($totalSales, 2),
            number_format($totalCogs, 2),
            number_format($totalGp, 2),
            number_format($totalMargin, 2) . '%'
        ]);

        fclose($handle);
    }, $filename);
}
public function exportAgents(Request $req)
{
    $year = $req->input('year');
    $month = $req->input('month');

    // ✅ Normalize inputs
    $year = $year !== '' ? (int) $year : null;
    $month = $month !== '' ? (int) $month : null;

    $monthName = $month
        ? date('F', mktime(0, 0, 0, $month, 1))
        : null;

    // ✅ Fetch data
    $data = DB::table('dbo.vSummarySalesCongsMonthYear_01_Agent_01')
        ->selectRaw("
            AGENT,
            SUM(COALESCE(SALES,0)) as sales,
            SUM(COALESCE(COGS,0)) as cogs
        ")
        ->whereNotNull('AGENT')
        ->where('AGENT', '<>', '')
        ->when($year, fn($q) => $q->whereRaw('[YEAR] = ?', [$year]))
        ->when($month, fn($q) => $q->whereRaw('CAST([MONTH] AS INT) = ?', [$month]))
        ->groupBy('AGENT')
        ->orderByDesc('sales')
        ->get();

    // ✅ Total Sales (for Share %)
    $totalSales = $data->sum('sales');

    // ✅ Dynamic filename
    $filename = 'agents_'
        . ($monthName ? strtolower($monthName) : 'all-months')
        . '_'
        . ($year ?? 'all-years')
        . '.csv';

    return response()->streamDownload(function () use ($data, $year, $monthName, $totalSales) {

        $handle = fopen('php://output', 'w');

        // ✅ Report Header
        fputcsv($handle, [
            'Agent Performance Report',
            $monthName ?? 'All Months',
            $year ?? 'All Years'
        ]);

        fputcsv($handle, []);

        // ✅ Column Headers
        fputcsv($handle, [
            'Agent',
            'Sales',
            'COGS',
            'Gross Profit',
            'Margin %',
            'Share %'
        ]);

        $totalCogs = 0;

        foreach ($data as $r) {

            $sales = (float) $r->sales;
            $cogs  = (float) $r->cogs;
            $gp    = $sales - $cogs;

            $margin = $sales > 0
                ? ($gp / $sales) * 100
                : 0;

            $share = $totalSales > 0
                ? ($sales / $totalSales) * 100
                : 0;

            $totalCogs += $cogs;

            fputcsv($handle, [
                $r->AGENT,
                number_format($sales, 2),
                number_format($cogs, 2),
                number_format($gp, 2),
                number_format($margin, 2) . '%',
                number_format($share, 2) . '%'
            ]);
        }

        // ✅ TOTAL ROW
        $totalGp = $totalSales - $totalCogs;
        $totalShare = $totalSales > 0 ? 100 : 0;
        $totalMargin = $totalSales > 0
            ? ($totalGp / $totalSales) * 100
            : 0;

        fputcsv($handle, []);

        fputcsv($handle, [
    'TOTAL',
    number_format($totalSales, 2),
    number_format($totalCogs, 2),
    number_format($totalGp, 2),
    number_format($totalMargin, 2) . '%',
    number_format($totalShare, 2) . '%'
]);
        fclose($handle);

    }, $filename);
}
public function exportAgentMonthly(Request $req)
{
    $agent = $req->input('agent');
    $year = $req->input('year');

    if (!$agent) {
        abort(400, 'Agent is required');
    }

    $rows = DB::table('vSummarySalesCongsMonthYear_01_Agent_01')
        ->selectRaw("
            MONTH,
            SUM(COALESCE(SALES,0)) as sales,
            SUM(COALESCE(COGS,0)) as cogs
        ")
        ->where('AGENT', $agent)
        ->when($year, fn($q) => $q->whereRaw('[YEAR] = ?', [$year]))
        ->groupBy('MONTH')
        ->orderBy('MONTH')
        ->get();

    // ✅ normalize to 1–12 months
    $monthMap = [];
    foreach ($rows as $r) {
        $monthMap[(int)$r->MONTH] = $r;
    }

    return response()->streamDownload(function () use ($monthMap, $agent, $year) {

        $handle = fopen('php://output', 'w');

        // ✅ HEADER (match your Excel style)
        fputcsv($handle, [
            'Agent Performance Report',
            $agent,
            $year ?? 'All Years'
        ]);

        fputcsv($handle, []);

        fputcsv($handle, [
            'Month','Sales','COGS','Gross Profit','Margin %'
        ]);

        $totalSales = 0;
        $totalCogs = 0;
        $totalGp = 0;

        for ($m = 1; $m <= 12; $m++) {

            $r = $monthMap[$m] ?? null;

            $sales = $r ? (float)$r->sales : 0;
            $cogs  = $r ? (float)$r->cogs : 0;
            $gp    = $sales - $cogs;

            $totalSales += $sales;
            $totalCogs += $cogs;
            $totalGp += $gp;

            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;

            fputcsv($handle, [
                date('M', mktime(0,0,0,$m,1)),
                number_format($sales, 2),
                number_format($cogs, 2),
                number_format($gp, 2),
                number_format($margin, 2) . '%'
            ]);
        }

        // ✅ TOTAL ROW
        $totalMargin = $totalSales > 0
            ? ($totalGp / $totalSales) * 100
            : 0;

        fputcsv($handle, []);
        fputcsv($handle, [
            'TOTAL',
            number_format($totalSales, 2),
            number_format($totalCogs, 2),
            number_format($totalGp, 2),
            number_format($totalMargin, 2) . '%'
        ]);

        fclose($handle);

    }, 'agent_monthly.csv');
}
public function exportDivisions(Request $req)
{
    $year = $req->input('year');
    $month = $req->input('month');  // ✅ ADD THIS

    // ✅ Normalize inputs
    $year = $year !== '' && $year !== null ? (int) $year : null;
    $month = $month !== '' && $month !== null ? (int) $month : null;

    // ✅ BASE QUERY
    $data = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
        ->selectRaw("
            [CLASS] as DIVISION,
            SUM(ISNULL(SALES,0)) as sales,
            SUM(ISNULL(COGS,0)) as cogs
        ")
        ->whereRaw("[CODE] = 'CATEGORY'")
        ->whereNotNull(DB::raw('[CLASS]'))
        ->where(DB::raw('[CLASS]'), '<>', '')
        ->when($year, fn($q)=>$q->whereRaw('[YEAR] = ?', [$year]))
        ->when($month, fn($q)=>$q->whereRaw('CAST([MONTH] AS INT) = ?', [$month]))  // ✅ ADD THIS
        ->groupByRaw("[CLASS]")
        ->orderByDesc('sales')
        ->get();

    // ✅ TOTAL SALES (FOR SHARE %)
    $totalSales = $data->sum('sales');

    return response()->streamDownload(function () use ($data, $totalSales) {

        $handle = fopen('php://output', 'w');

        // ✅ HEADER UPDATED
        fputcsv($handle, [
            'Division','Sales','COGS','Gross Profit','Margin %','Share %'
        ]);

                foreach ($data as $r) {

            $sales = (float) $r->sales;
            $cogs  = (float) $r->cogs;
            $gp    = $sales - $cogs;

            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;

            // ✅ SHARE CALCULATION
            $share = $totalSales > 0
                ? ($sales / $totalSales) * 100
                : 0;

            fputcsv($handle, [
                $r->DIVISION,
                number_format($sales, 2),
                number_format($cogs, 2),
                number_format($gp, 2),
                number_format($margin, 2) . '%',
                number_format($share, 2) . '%'
            ]);
        }

        // ✅ ADD TOTAL ROW (THIS IS WHAT YOU'RE MISSING)
        $totalCogs = $data->sum('cogs');
        $totalGp = $totalSales - $totalCogs;
        $totalMargin = $totalSales > 0 ? ($totalGp / $totalSales) * 100 : 0;

        fputcsv($handle, []); // Empty row
        fputcsv($handle, [
            'TOTAL',
            number_format($totalSales, 2),
            number_format($totalCogs, 2),
            number_format($totalGp, 2),
            number_format($totalMargin, 2) . '%',
            '100.00%'
        ]);

        fclose($handle);

      }, 'divisions_' . ($year ?? 'all-years') . '_' . ($month ?? 'all-months') . '.csv');
}
public function exportDivisionMonthly(Request $req)
{
    $division = $req->input('division');
    $year = $req->input('year');

    if (!$division) abort(400, 'Division required');

    $rows = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
        ->selectRaw("
            [MONTH],
            SUM(ISNULL(SALES,0)) as sales,
            SUM(ISNULL(COGS,0)) as cogs
        ")
        ->whereRaw("[CODE] = 'CATEGORY'")
        ->whereRaw("[CLASS] = ?", [$division])
        ->when($year, fn($q)=>$q->whereRaw('[YEAR] = ?', [$year]))
        ->groupByRaw("[MONTH]")
        ->get();

    $monthMap = [];
    foreach ($rows as $r) {
        $monthMap[(int)$r->MONTH] = $r;
    }

    return response()->streamDownload(function () use ($monthMap) {

        $handle = fopen('php://output', 'w');

        fputcsv($handle, ['Month','Sales','COGS','Gross Profit','Margin %']);
        

$totalSales = 0;
$totalCogs  = 0;
$totalGp    = 0;

        for ($m=1; $m<=12; $m++) {

            $r = $monthMap[$m] ?? null;

            $sales = $r ? $r->sales : 0;
            $cogs  = $r ? $r->cogs : 0;
            $gp    = $sales - $cogs;
            $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;

            fputcsv($handle, [
                date('M', mktime(0,0,0,$m,1)),
                number_format($sales,2),
                number_format($cogs,2),
                number_format($gp,2),
                number_format($margin,2).'%',
            ]);
            
        }
        $totalMargin = $totalSales > 0
    ? ($totalGp / $totalSales) * 100
    : 0;

// ✅ ADD THIS BLOCK (THIS IS WHAT YOU'RE MISSING)
fputcsv($handle, []);

fputcsv($handle, [
    'TOTAL',
    number_format($totalSales, 2),
    number_format($totalCogs, 2),
    number_format($totalGp, 2),
    number_format($totalMargin, 2) . '%'
]);


        fclose($handle);

    }, 'division_monthly.csv');
}
public function exportMonthlyAll(Request $req)
{
    $year = $req->input('year');

    $query = DB::table('dbo.vSummarySalesCongsMonthYear_Combined')
        ->whereRaw("[CODE] = 'CATEGORY'")
        ->whereRaw("CAST([MONTH] AS INT) BETWEEN 1 AND 12")
        ->selectRaw("
            CAST([YEAR] AS INT) as year,
            CAST([MONTH] AS INT) as month,
            SUM([SALES]) as sales,
            SUM([COGS]) as cogs
        ");

    if (!empty($year)) {
        $query->whereRaw("CAST([YEAR] AS INT) = ?", [(int)$year]);
    }

    $rows = $query
        ->groupByRaw("CAST([YEAR] AS INT), CAST([MONTH] AS INT)")
        ->orderByRaw("CAST([YEAR] AS INT), CAST([MONTH] AS INT)")
        ->get();

    $grouped = $rows->groupBy('year');

    return response()->streamDownload(function () use ($grouped) {

        $handle = fopen('php://output', 'w');

        foreach ($grouped as $year => $months) {

            fputcsv($handle, [$year]);

            fputcsv($handle, [
                'Month','Sales','COGS','Gross Profit','Margin %'
            ]);

            $monthMap = [];
            foreach ($months as $r) {
                $monthMap[(int)$r->month] = $r;
            }

            $totalSales = 0;
            $totalCogs  = 0;
            $totalGp    = 0;

            for ($m = 1; $m <= 12; $m++) {

                $r = $monthMap[$m] ?? null;

                $sales = $r ? (float)$r->sales : 0;
                $cogs  = $r ? (float)$r->cogs : 0;
                $gp    = $sales - $cogs;

                $totalSales += $sales;
                $totalCogs  += $cogs;
                $totalGp    += $gp;

                $margin = $sales > 0 ? ($gp / $sales) * 100 : 0;

                fputcsv($handle, [
                    date('F', mktime(0,0,0,$m,1)),
                    number_format($sales, 2),
                    number_format($cogs, 2),
                    number_format($gp, 2),
                    number_format($margin, 2) . '%'
                ]);
            }

            $totalMargin = $totalSales > 0
                ? ($totalGp / $totalSales) * 100
                : 0;

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                number_format($totalSales, 2),
                number_format($totalCogs, 2),
                number_format($totalGp, 2),
                number_format($totalMargin, 2) . '%'
            ]);

            fputcsv($handle, []);
            fputcsv($handle, []);
        }

        fclose($handle);

    }, 'gross_profit_monthly.csv');
}
}
