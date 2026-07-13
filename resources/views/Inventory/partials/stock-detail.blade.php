<h6 class="mb-2">{{ $summary->StockCode }}</h6>

<div class="text-muted small mb-3">
    {{ $summary->LongDesc }}
    <br>
    UOM: {{ $summary->StockUom ?? '-' }}
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <strong>Total Qty:</strong>
        {{ number_format($summary->TotalQty) }}
    </div>
    <div class="col-md-6 text-end">
        <strong>Total Value:</strong>
        ₱{{ number_format($summary->TotalValue, 2) }}
    </div>
</div>

<hr>

<h6 class="mb-2">Warehouse Breakdown</h6>

<table class="table table-sm table-hover align-middle">
    <thead>
        <tr>
            <th>Warehouse</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Unit Cost</th>
            <th class="text-end">Total</th>
            <th>Last Move</th>
        </tr>
    </thead>
    <tbody>
     @foreach($warehouses as $w)
<tr>
    <td class="fw-semibold text-primary">
        {{ $w->Warehouse }}
    </td>
    <td class="text-end">{{ number_format($w->EndingBalances) }}</td>
    <td class="text-end">₱{{ number_format($w->UnitCost,6) }}</td>
    <td class="text-end fw-bold">₱{{ number_format($w->EndingBalances * $w->UnitCost, 2) }}</td>
    <td>{{ \Carbon\Carbon::parse($w->DateLastStockMove)->format('M d, Y') }}</td>
</tr>
@endforeach

    </tbody>
</table>