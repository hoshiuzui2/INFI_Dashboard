<div id="reorder-alerts-wrapper">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Reorder Alerts</h6>
    
    <a href="{{ route('inventory.export-reorder-alerts', ['division' => request('division')]) }}" 
       class="btn btn-sm btn-outline-warning">
        <i class="bi bi-download"></i> Export
    </a>
</div>
@if($rows->count())
<table class="table table-hover align-middle">
    <thead>
        <tr>
            <th>Stock Code</th>
            <th>Description</th>
            <th>Category</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Value</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $r)
        <tr>
         <td>
    <button class="btn btn-link p-0 fw-semibold stock-link"
            data-code="{{ $r->StockCode }}">
        {{ $r->StockCode }}
    </button>
</td>

<td>
    <div class="fw-semibold">
        {{ $r->LongDesc }}
    </div>

    <div class="text-muted small">
        UOM: {{ $r->StockUom ?? '-' }}
    </div>
</td>

<td>{{ $r->Category }}</td>
            <td class="text-end">{{ number_format($r->Qty) }}</td>
            <td class="text-end fw-bold">
                ₱{{ number_format($r->TotalValue,2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-3 d-flex justify-content-center">
    {{ $rows->links('pagination::bootstrap-5') }}
</div>

@else
<div class="text-center text-muted py-4">
    No reorder alerts found.
</div>
@endif

</div>
