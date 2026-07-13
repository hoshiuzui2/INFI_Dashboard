@if($row)
<table class="table table-sm">
    <tr>
        <th>Stock Code</th>
        <td>{{ $row->StockCode }}</td>
    </tr>

    <tr>
        <th>Short Description</th>
        <td>{{ $row->Description }}</td>
    </tr>

    <tr>
        <th>Long Description</th>
        <td class="text-muted">{{ $row->LongDesc }}</td>
    </tr>

    
    <tr>
        <th>Category</th>
        <td>{{ $row->UserField1 }}</td>
    </tr>

    <tr>
        <th>Warehouse</th>
        <td>{{ $row->Warehouse }}</td>
    </tr>

    <tr>
        <th>Quantity</th>
        <td>{{ number_format($row->EndingBalances) }}</td>
    </tr>

    <tr>
        <th>Unit Cost</th>
        <td>₱{{ number_format($row->UnitCost,6) }}</td>
    </tr>

    <tr class="fw-bold">
        <th>Total Value</th>
        <td>₱{{ number_format($row->Totals,2) }}</td>
    </tr>
</table>
<div class="text-end mt-3">
    <button class="btn btn-sm btn-outline-warning stock-breakdown-btn"
            data-code="{{ $row->StockCode }}">
        View Stock Breakdown
    </button>
</div>

@else
<div class="text-center text-muted py-4">No data found.</div>
@endif
