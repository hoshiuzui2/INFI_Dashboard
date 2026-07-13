<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\GrossProfitController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| All page + AJAX routes for the Executive Dashboard
|--------------------------------------------------------------------------
*/

// ✅ Default route — goes to Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// ✅ Optional test route
Route::get('/okkaayyy', function () {
    return 'Hello, World!';
});

// ✅ Sales summary page (normal view)
Route::get('/sales', [DashboardController::class, 'sales'])->name('sales');

// ✅ Real-time JSON endpoint (for AJAX auto-updates)
Route::get('/sales/realtime', [DashboardController::class, 'sales'])->name('sales.realtime');

// Customer details for modal (FIXED — NO route parameter!)
Route::get('/sales/customer-details', [DashboardController::class, 'customerDetails'])
    ->name('sales.customer.details');
    
Route::get(
    '/sales/product/details',
    [DashboardController::class, 'productInvoiceDetails']
)->name('sales.product.details');

Route::get('/ar', [DashboardController::class, 'arIndex'])->name('ar.index');
Route::get('/ar/customer/details', [DashboardController::class, 'arCustomerDetails'])->name('ar.customer.details');
Route::get('/ar/export', [DashboardController::class, 'arExport'])->name('ar.export'); // optional CSV export
Route::get('/ar/export/overdue', [DashboardController::class, 'exportOverdue'])
    ->name('ar.export.overdue');
Route::get('/ar/data', [DashboardController::class, 'arData'])->name('ar.data');


Route::get('/ap', [DashboardController::class, 'apIndex'])->name('ap.index');
Route::get('/ap/data', [DashboardController::class, 'apData'])->name('ap.data'); // returns JSON (AJAX)
Route::get('/ap/customer/details', [DashboardController::class, 'apCustomerDetails'])->name('ap.customer.details');
Route::get('/ap/export-supplier-invoices', [DashboardController::class, 'exportSupplierInvoices'])
    ->name('ap.export.supplier');
    
Route::get('/inventory', [InventoryController::class, 'index'])
    ->name('inventory.index');

Route::get('/inventory/export', [InventoryController::class, 'export'])
    ->name('inventory.export');

Route::get('/inventory/search', [InventoryController::class, 'search']);
Route::get('/inventory/ajax-search', [InventoryController::class, 'ajaxSearch']);
Route::get('/inventory/ajax-suggest', [InventoryController::class, 'ajaxSuggest']);
Route::get('/inventory/ajax-detail/{code}', [InventoryController::class, 'ajaxDetail']);
Route::get('/inventory/ajax-aging-stock', [InventoryController::class, 'ajaxAgingStock']);
Route::get(
    '/inventory/ajax-breakdown/{code}',
    [InventoryController::class, 'ajaxBreakdown']
);

Route::get(
    '/inventory/ajax-stock-status',
    [InventoryController::class, 'ajaxStockStatus']
);
Route::get(
    '/inventory/ajax-reorder-alerts',
    [InventoryController::class, 'ajaxReorderAlerts']
);

Route::get(
    '/inventory/export-aging',
    [InventoryController::class, 'exportAging']
)->name('inventory.export.aging');

Route::get('/inventory/export-stock-status', [InventoryController::class, 'exportStockStatus'])
    ->name('inventory.export-stock-status');

Route::get('/inventory/export-reorder-alerts', [InventoryController::class, 'exportReorderAlerts'])
    ->name('inventory.export-reorder-alerts');    

Route::get('/gross-profit', [GrossProfitController::class, 'index'])
    ->name('gross-profit.index');
    
Route::get('/gross-profit/customer-data', 
    [GrossProfitController::class, 'customerData']
)->name('gross-profit.customer.data');
Route::get('/gross-profit/customer/export', [GrossProfitController::class, 'exportCustomers'])->name('gross-profit.customer.export');
Route::get('/gross-profit/customer/monthly-export', [GrossProfitController::class, 'exportCustomerMonthly'])->name('gross-profit.customer.monthly-export');

Route::get('/gross-profit/category-breakdown',
    [GrossProfitController::class,'categoryBreakdown']
)->name('gross-profit.category.breakdown');

Route::get('/gross-profit/product-data',
    [GrossProfitController::class,'productData'])
    ->name('gross-profit.product.data');
Route::get('/gross-profit/product/export', [GrossProfitController::class, 'exportProducts']);
Route::get(
    '/gross-profit/product/monthly-export',
    [GrossProfitController::class, 'exportProductMonthly']
);

Route::get('/gross-profit/agent-data',
    [GrossProfitController::class,'agentData'])
    ->name('gross-profit.agent.data');

    Route::get(
    '/gross-profit/agent/export',
    [GrossProfitController::class, 'exportAgents']
)->name('gross-profit.agent.export');

Route::get('/gross-profit/agent/monthly-export', [GrossProfitController::class, 'exportAgentMonthly'])
    ->name('gross-profit.agent.monthly-export');

Route::get('/gross-profit/agent/monthly', [GrossProfitController::class, 'agentMonthly'])
->name('gross-profit.agent.monthly');    

Route::get('/gross-profit/division-data',
    [GrossProfitController::class,'divisionData'])
    ->name('gross-profit.division.data');

Route::get('/gross-profit/division-monthly', [GrossProfitController::class, 'divisionMonthly'])
    ->name('gross-profit.division.monthly');  

Route::get('/gross-profit/division/export',
    [GrossProfitController::class, 'exportDivisions']
)->name('gross-profit.division.export');

Route::get('/gross-profit/division/monthly-export',
    [GrossProfitController::class, 'exportDivisionMonthly']
)->name('gross-profit.division.monthly-export');

Route::get('/gross-profit/product-monthly',
[GrossProfitController::class,'productMonthly'])
->name('gross-profit.product.monthly');

Route::get('/gross-profit/monthly-export',
    [GrossProfitController::class, 'exportMonthlyAll']
)->name('gross-profit.monthly.export');

