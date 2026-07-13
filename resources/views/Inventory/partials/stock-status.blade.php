<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">
        {{ $type === 'in' ? 'In Stock Items' : 'Out of Stock Items' }}
    </h6>
    
    <a href="{{ route('inventory.export-stock-status', ['type' => $type, 'division' => request('division')]) }}" 
       class="btn btn-sm btn-outline-warning">
        <i class="bi bi-download"></i> Export 
    </a>
</div>

@if($rows->count())
<table class="table table-sm table-hover align-middle">
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
                <button class="btn btn-link p-0 stock-link"
                        data-code="{{ $r->StockCode }}">
                    {{ $r->StockCode }}
                </button>
            </td>
            <td>
                <div class="fw-semibold">{{ $r->Description }}</div>
                <div class="text-muted small">{{ $r->LongDesc }}</div>
            </td>
            <td>{{ $r->Category }}</td>
            <td class="text-end">{{ number_format($r->Qty) }}</td>
            <td class="text-end fw-bold">
                ₱{{ number_format($r->TotalValue,2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
    
    {{-- ✅ ADD THIS FOOTER --}}
    <tfoot class="fw-bold" style="background: #f8f9fa; border-top: 2px solid #dee2e6;">
        <tr>
            <td colspan="3" class="text-end">TOTAL ({{ $type === 'in' ? 'In Stock' : 'Out of Stock' }}):</td>
            <td class="text-end">{{ number_format($grandTotals->TotalQty) }}</td>
            <td class="text-end">₱{{ number_format($grandTotals->TotalValue, 2) }}</td>
        </tr>
    </tfoot>
</table>
@if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-3 d-flex justify-content-center">
        {{ $rows->links('pagination::bootstrap-5') }}
    </div>
@endif

@else
<div class="text-center text-muted py-4">
    No items found.
</div>
@endif
