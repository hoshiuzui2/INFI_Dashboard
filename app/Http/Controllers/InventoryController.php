<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function index()
    {
        $year = request('year', now()->year);
        $division = request('division');
        $sort = request('sort', 'value');
$dir  = request('dir', 'desc');
        /* ================= KPI METRICS ================= */

        $inventoryTotal = DB::table('vInventoryMovement')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
    ->sum('Totals');


       $totalItems = DB::table('vInventoryMovement')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
    ->distinct('StockCode')
    ->count('StockCode');


$inStock = DB::table(DB::raw("
    (
        SELECT StockCode
        FROM vInventoryMovement
        " . ($division ? "WHERE DrawOfficeNum = ?" : "") . "
        GROUP BY StockCode
        HAVING SUM(EndingBalances) > 0
    ) AS t
"))
->when($division, fn ($q) => $q->addBinding($division))
->count();

$outOfStock = DB::table(DB::raw("
    (
        SELECT StockCode
        FROM vInventoryMovement
        " . ($division ? "WHERE DrawOfficeNum = ?" : "") . "
        GROUP BY StockCode
        HAVING SUM(EndingBalances) <= 0
    ) AS t
"))
->when($division, fn ($q) => $q->addBinding($division))
->count();



        $availabilityRate = $totalItems > 0
            ? round(($inStock / $totalItems) * 100, 1)
            : 0;

        $agingValue = DB::table('vInventoryMovement')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
    ->where('DateLastStockMove', '<', Carbon::now()->subDays(90))
    ->sum('Totals');

       $reorderCount = DB::table('vInventoryMovement')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
    ->where('EndingBalances', '<=', 0)
    ->distinct('StockCode')
    ->count('StockCode');
        /* ================= INVENTORY TREND ================= */

        $trendRaw = DB::table('vInventoryMovement')
    ->selectRaw('
        YEAR(DateLastStockMove) as year,
        MONTH(DateLastStockMove) as month,
        SUM(Totals) as total
    ')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
    ->whereYear('DateLastStockMove', $year)
    ->groupByRaw('YEAR(DateLastStockMove), MONTH(DateLastStockMove)')
    ->orderByRaw('MONTH(DateLastStockMove)')
    ->get();

        $trendLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $trendValues = array_fill(0, 12, 0);

        foreach ($trendRaw as $row) {
            $trendValues[$row->month - 1] = (float) $row->total;
        }

// ✅ AJAX: Inventory Trend ONLY (JSON)
if (
    request()->ajax()
    && request()->has('year')
) {
    return response()->json([
        'labels' => $trendLabels,
        'values' => $trendValues,
    ]);
}


        /* ================= WAREHOUSE ================= */

        $warehouseRaw = DB::table('vInventoryMovement')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
    ->selectRaw('Warehouse, SUM(Totals) as total')
    ->groupBy('Warehouse')
    ->orderByDesc('total')
    ->get();

        $warehouseLabels = $warehouseRaw->pluck('Warehouse')->toArray();
        $warehouseValues = $warehouseRaw->pluck('total')->map(fn ($v) => (float)$v)->toArray();

        /* ================= DIVISION ================= */

$divisionRaw = DB::table('vInventoryMovement')
    ->selectRaw('DrawOfficeNum as division, SUM(Totals) as total')
    ->whereNotNull('DrawOfficeNum')
    ->groupBy('DrawOfficeNum')
    ->orderByDesc('total')
    ->get();

$divisionLabels = $divisionRaw->pluck('division')->toArray();
$divisionValues = $divisionRaw->pluck('total')->map(fn ($v) => (float)$v)->toArray();


        /* ================= INVENTORY TABLE ================= */

        $search = request('search');

$inventoryRows = DB::table('vInventoryMovement')
   ->select(
    'StockCode',
    DB::raw('MAX(Description) as Description'),
    DB::raw('MAX(LongDesc) as LongDesc'),
    DB::raw('MAX(StockUom) as StockUom'),
    DB::raw('MAX(DrawOfficeNum) as Division'),
    DB::raw('MAX(UserField1) as UserField1'),
    DB::raw('SUM(EndingBalances) as EndingBalances'),
    DB::raw('SUM(Totals) as Totals')
)
    ->when($search, function ($query) use ($search) {
        $terms = preg_split('/\s+/', trim($search));

        $query->where(function ($q) use ($terms) {
            foreach ($terms as $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('StockCode', 'like', "%{$term}%")
                        ->orWhere('Description', 'like', "%{$term}%")
                        ->orWhere('LongDesc', 'like', "%{$term}%")
                        ->orWhere('UserField1', 'like', "%{$term}%");
                });
            }
        });
    })
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division)) 
    ->groupBy('StockCode')
    ->orderByRaw(match (true) {
    $sort === 'qty' && $dir === 'asc'  => 'SUM(EndingBalances) ASC',
    $sort === 'qty' && $dir === 'desc' => 'SUM(EndingBalances) DESC',

    $sort === 'value' && $dir === 'asc'  => 'SUM(Totals) ASC',
    $sort === 'value' && $dir === 'desc' => 'SUM(Totals) DESC',

    default => 'SUM(Totals) DESC' // ORIGINAL
})
    ->paginate(50)
    ->withQueryString();

    $divisions = DB::table('vInventoryMovement')
    ->select('DrawOfficeNum')
    ->whereNotNull('DrawOfficeNum')
    ->distinct()
    ->orderBy('DrawOfficeNum')
    ->pluck('DrawOfficeNum');
// 🔥 ADD THIS
if (
    request()->ajax()
    && request()->header('X-Requested-With') === 'XMLHttpRequest'
    && !request()->wantsJson()
) {
    return view('inventory.partials.rows', [
        'inventoryRows' => $inventoryRows
    ]);
}

        return view('inventory.index', compact(
            'inventoryTotal',
            'totalItems',
            'inStock',
            'outOfStock',
            'agingValue',
            'reorderCount',
            'availabilityRate',
            'trendLabels',
            'trendValues',
            'warehouseLabels',
            'warehouseValues',
            'divisionLabels',
            'divisionValues',
            'inventoryRows',
            'divisions' 
        ));
    }

 public function export()
{
    $division = request('division');
    $year = request('year');

    $rows = DB::table('vInventoryMovement')
        ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
        ->when($year, fn ($q) => $q->whereYear('DateLastStockMove', $year))
        ->select([
            'StockCode',
            DB::raw('MAX(Warehouse) as Warehouse'),
            DB::raw('MAX(Description) as Description'),
            DB::raw('MAX(LongDesc) as LongDesc'),
            DB::raw('MAX(StockUom) as StockUom'),
            DB::raw('MAX(DrawOfficeNum) as Division'),
            DB::raw('MAX(UserField1) as Category'),
            DB::raw('SUM(EndingBalances) as Qty'),
            DB::raw('SUM(Totals) as TotalValue'),
        ])
        ->groupBy('StockCode')
->orderByRaw(match (true) {
    request('sort') === 'qty'   && request('dir') === 'asc'  => 'SUM(EndingBalances) ASC',
    request('sort') === 'qty'   && request('dir') === 'desc' => 'SUM(EndingBalances) DESC',

    request('sort') === 'value' && request('dir') === 'asc'  => 'SUM(Totals) ASC',
    request('sort') === 'value' && request('dir') === 'desc' => 'SUM(Totals) DESC',

    default => 'SUM(Totals) DESC'
})
->get();

    $filename = 'inventory_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');

        // CSV HEADERS
        fputcsv($out, [
            'Stock Code',
            'Warehouse',
            'Long Description',
'UOM',
            'Division',
            'Category',
            'Quantity',
            'Total Value'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->StockCode,
                $r->Warehouse,
                $r->LongDesc,
$r->StockUom,
                $r->Division,
                $r->Category,
                number_format((float) $r->Qty, 2),
                number_format((float) $r->TotalValue, 2),
            ]);
        }

        fclose($out);
    }, $filename, [
        'Content-Type' => 'text/csv',
    ]);
}

public function ajaxDetail($code)
{
    $summary = DB::table('vInventoryMovement')
        ->select([
            'StockCode',
            DB::raw('MAX(Description) as Description'),
            DB::raw('MAX(LongDesc) as LongDesc'),
            DB::raw('MAX(StockUom) as StockUom'),
            DB::raw('MAX(UserField1) as Category'),
            DB::raw('SUM(EndingBalances) as TotalQty'),
            DB::raw('SUM(Totals) as TotalValue'),
        ])
        ->where('StockCode', $code)
        ->groupBy('StockCode')
        ->first();

    if (!$summary) {
        return '<div class="text-center text-muted py-4">No data found.</div>';
    }

    $warehouses = DB::table('vInventoryMovement')
        ->where('StockCode', $code)
        ->orderBy('Warehouse')
        ->get();

    return view('inventory.partials.stock-detail', compact(
        'summary',
        'warehouses'
    ));
}

  public function ajaxAgingStock()
{   
    $division = request('division');
    $bucket = request('bucket');
    $sort   = request('sort', 'value');
    $dir    = request('dir', 'desc');

    $rows = DB::table('vInventoryMovement')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
        ->select([
            'StockCode',
            DB::raw('MAX(Description) as Description'),
            DB::raw('MAX(LongDesc) as LongDesc'),
            DB::raw('MAX(StockUom) as StockUom'),
            DB::raw('MAX(UserField1) as Category'),
            DB::raw('SUM(EndingBalances) as Qty'),
            DB::raw('MAX(DateLastStockMove) as LastMove'),
            DB::raw('SUM(Totals) as TotalValue'),
   DB::raw("
    CASE
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 0 AND 30 THEN '0-30'
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 31 AND 60 THEN '31-60'
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 61 AND 90 THEN '61-90'
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 91 AND 120 THEN '91-120'
        ELSE '120+'
    END AS Aging
")


        ])
        ->groupBy('StockCode')

        // 🔒 BUCKET FILTER FIRST
        ->when($bucket, function ($q) use ($bucket) {
            $q->havingRaw("
                CASE
                    WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 0 AND 30 THEN '0-30'
                    WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 31 AND 60 THEN '31-60'
                    WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 61 AND 90 THEN '61-90'
                    WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 91 AND 120 THEN '91-120'
                    ELSE '120+'
                END = ?
            ", [$bucket]);
        })

        // 🔒 SORT APPLIED AFTER BUCKET
        ->orderByRaw(match ($sort) {
            'qty'       => "SUM(EndingBalances) $dir",
            'last_move' => "MAX(DateLastStockMove) $dir",
            'value'     => "SUM(Totals) $dir",
            default     => "SUM(Totals) DESC"
        })

        ->paginate(100)
        ->withQueryString();

    return view('inventory.partials.aging-stock', compact('rows'));
}

public function ajaxBreakdown($code)
{
    $rows = DB::table('vInventoryMovement')
        ->where('StockCode', $code)
        ->orderBy('Warehouse')
        ->get();

    return view('inventory.partials.breakdown', compact('rows', 'code'));
}
public function ajaxStockStatus()
{
    $division = request('division');
    $type = request('type'); // in | out

    // Build base query
    $baseQuery = DB::table('vInventoryMovement')
        ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division));

    // ✅ Calculate grand totals from ALL items (before pagination)
// We need to wrap in subquery to sum the grouped results
$grandTotalsQuery = (clone $baseQuery)
    ->selectRaw('
        SUM(EndingBalances) as Qty, 
        SUM(Totals) as TotalValue
    ')
    ->groupBy('StockCode')
    ->havingRaw(
        $type === 'in'
            ? 'SUM(EndingBalances) > 0'
            : 'SUM(EndingBalances) <= 0'
    );

$grandTotals = DB::table(DB::raw("({$grandTotalsQuery->toSql()}) as subquery"))
    ->mergeBindings($grandTotalsQuery)
    ->selectRaw('SUM(Qty) as TotalQty, SUM(TotalValue) as TotalValue')
    ->first();

      // Main query for pagination (separate from grand totals)
    $rows = (clone $baseQuery)
        ->select([
            'StockCode',
            DB::raw('MAX(Description) as Description'),
            DB::raw('MAX(LongDesc) as LongDesc'),
            DB::raw('MAX(StockUom) as StockUom'),
            DB::raw('MAX(UserField1) as Category'),
            DB::raw('SUM(EndingBalances) as Qty'),
            DB::raw('SUM(Totals) as TotalValue'),
        ])
        ->groupBy('StockCode')
        ->havingRaw(
            $type === 'in'
                ? 'SUM(EndingBalances) > 0'
                : 'SUM(EndingBalances) <= 0'
        )
        ->orderByDesc('TotalValue')
        ->paginate(100)
        ->withQueryString(); // ✅ Preserves type=in parameter

    // ✅ Explicitly append type parameter to pagination links
    $rows->appends(['type' => $type]);

    return view('inventory.partials.stock-status', compact(
        'rows', 
        'type',
        'grandTotals'
    ));
}
public function ajaxReorderAlerts()
{
   $division = request('division');

$rows = DB::table('vInventoryMovement')
    ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
    ->select([
        'StockCode',
        DB::raw('MAX(LongDesc) as LongDesc'),
        DB::raw('MAX(StockUom) as StockUom'),
        DB::raw('MAX(UserField1) as Category'),
        DB::raw('SUM(EndingBalances) as Qty'),
        DB::raw('SUM(Totals) as TotalValue'),
    ])
    ->groupBy('StockCode')
    ->havingRaw('SUM(EndingBalances) <= 0')
    ->orderByDesc('TotalValue')
    ->paginate(20);

    return view('inventory.partials.reorder-alerts', compact('rows'));
}
public function exportAging()
{
    $division = request('division');
    $bucket = request('bucket');
    $sort   = request('sort', 'value');
    $dir    = request('dir', 'desc');

    $rows = DB::table('vInventoryMovement')
        ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
        ->select([
            'StockCode',
            DB::raw('MAX(Description) as Description'),
            DB::raw('MAX(LongDesc) as LongDesc'),
            DB::raw('MAX(StockUom) as StockUom'),
            DB::raw('MAX(UserField1) as Category'),
            DB::raw('SUM(EndingBalances) as Qty'),
            DB::raw('MAX(DateLastStockMove) as LastMove'),
            DB::raw('SUM(Totals) as TotalValue'),
           DB::raw("
    CASE
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 0 AND 30 THEN '0-30'
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 31 AND 60 THEN '31-60'
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 61 AND 90 THEN '61-90'
        WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 91 AND 120 THEN '91-120'
        ELSE '120+'
    END AS Aging
")

        ])
        ->groupBy('StockCode')

     ->when($bucket, function ($q) use ($bucket) {
    $q->havingRaw("
        CASE
            WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 0 AND 30 THEN '0-30'
            WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 31 AND 60 THEN '31-60'
            WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 61 AND 90 THEN '61-90'
            WHEN DATEDIFF(day, MAX(DateLastStockMove), GETDATE()) BETWEEN 91 AND 120 THEN '91-120'
            ELSE '120+'
        END = ?
    ", [$bucket]);
})
        ->orderByRaw(match ($sort) {
            'qty'       => "SUM(EndingBalances) $dir",
            'last_move' => "MAX(DateLastStockMove) $dir",
            'value'     => "SUM(Totals) $dir",
            default     => "SUM(Totals) DESC"
        })
        ->get();

    $filename = 'aging_stock_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($rows) {
        $out = fopen('php://output', 'w');

        // ✅ HEADERS
        fputcsv($out, [
    'Stock Code',
    'Long Description',
    'UOM',
    'Category',
    'Qty',
    'Aging',
    'Last Move',
    'Value'
]);

       foreach ($rows as $r) {
    fputcsv($out, [
    $r->StockCode,
    $r->LongDesc,
    $r->StockUom,
    $r->Category,

        // Qty → formatted with commas + 2 decimals
        number_format((float) $r->Qty, 2),

        // Aging bucket
        $r->Aging,

        // Last Stock Move → FIXED
        $r->LastMove
            ? \Carbon\Carbon::parse($r->LastMove)->format('Y-m-d')
            : '',

        // Value → formatted with commas + 2 decimals
        number_format((float) $r->TotalValue, 2),
    ]);
}

        fclose($out);
    }, $filename);
 }

 public function exportStockStatus()
{
    $division = request('division');
    $type = request('type', 'in'); // in | out

    // Build query for ALL items - MUST MATCH ajaxStockStatus() exactly
    $rows = DB::table('vInventoryMovement')
        ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
        ->select([
            'StockCode',
            DB::raw('MAX(Description) as Description'),
            DB::raw('MAX(LongDesc) as LongDesc'),
            DB::raw('MAX(StockUom) as StockUom'),
            DB::raw('MAX(UserField1) as Category'),
            DB::raw('SUM(EndingBalances) as Qty'),
            DB::raw('SUM(Totals) as TotalValue'),
        ])
        ->groupBy('StockCode')
        ->havingRaw(
            $type === 'in'
                ? 'SUM(EndingBalances) > 0'
                : 'SUM(EndingBalances) <= 0'
        )
        ->orderByDesc('TotalValue')
        ->orderBy('StockCode', 'asc')  // ✅ Secondary sort for consistency
        ->get();

    // Calculate grand total
    $grandTotal = $rows->sum('TotalValue');
    $grandQty = $rows->sum('Qty');

    $filename = ($type === 'in' ? 'In_Stock' : 'Out_of_Stock') . 
                '_Items_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($rows, $grandQty, $grandTotal) {
        $out = fopen('php://output', 'w');

        // CSV HEADERS
        fputcsv($out, [
            'Stock Code',
            'Description',
            'Long Description',
            'UOM',
            'Category',
            'Quantity',
            'Total Value'
        ]);

        foreach ($rows as $r) {
            $qty = (float) $r->Qty;
            $totalValue = (float) $r->TotalValue;
            
            fputcsv($out, [
                $r->StockCode,
                $r->Description,
                $r->LongDesc,
                $r->StockUom,
                $r->Category,
                number_format($qty, 2, '.', ','),
                number_format($totalValue, 2, '.', ',')
            ]);
        }

        // Add blank row
        fputcsv($out, []);
        
        // Add GRAND TOTAL row
        fputcsv($out, [
            'GRAND TOTAL',
            '',
            '',
            '',
            '',
            number_format($grandQty, 2, '.', ','),
            number_format($grandTotal, 2, '.', ',')
        ]);

        fclose($out);
    }, $filename, [
        'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
}
public function exportReorderAlerts()
{
    $division = request('division');

    // Build query for ALL items below threshold (qty <= 0)
    $rows = DB::table('vInventoryMovement')
        ->when($division, fn ($q) => $q->where('DrawOfficeNum', $division))
        ->select([
            'StockCode',
            DB::raw('MAX(Description) as Description'),
            DB::raw('MAX(LongDesc) as LongDesc'),
            DB::raw('MAX(StockUom) as StockUom'),
            DB::raw('MAX(UserField1) as Category'),
            DB::raw('SUM(EndingBalances) as Qty'),
            DB::raw('SUM(Totals) as TotalValue'),
        ])
        ->groupBy('StockCode')
        ->havingRaw('SUM(EndingBalances) <= 0')
        ->orderByDesc('TotalValue')
        ->orderBy('StockCode', 'asc')  // ✅ Secondary sort for consistency
        ->get();

    // Calculate grand total
    $grandTotal = $rows->sum('TotalValue');
    $grandQty = $rows->sum('Qty');

    $filename = 'Reorder_Alerts_' . now()->format('Ymd_His') . '.csv';

    return response()->streamDownload(function () use ($rows, $grandQty, $grandTotal) {
        $out = fopen('php://output', 'w');

        // CSV HEADERS
        fputcsv($out, [
            'Stock Code',
            'Description',
            'Long Description',
            'UOM',
            'Category',
            'Quantity',
            'Total Value'
        ]);

        foreach ($rows as $r) {
            $qty = (float) $r->Qty;
            $totalValue = (float) $r->TotalValue;
            
            fputcsv($out, [
                $r->StockCode,
                $r->Description,
                $r->LongDesc,
                $r->StockUom,
                $r->Category,
                number_format($qty, 2, '.', ','),
                number_format($totalValue, 2, '.', ',')
            ]);
        }

        // Add blank row
        fputcsv($out, []);
        
        // Add GRAND TOTAL row
        fputcsv($out, [
            'GRAND TOTAL',
            '',
            '',
            '',
            '',
            number_format($grandQty, 2, '.', ','),
            number_format($grandTotal, 2, '.', ',')
        ]);

        fclose($out);
    }, $filename, [
        'Content-Type' => 'text/csv; charset=UTF-8',
    ]);
}

}
