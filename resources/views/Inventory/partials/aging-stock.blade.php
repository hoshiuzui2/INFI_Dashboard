{{-- =========================================
   AGING STOCK TABLE
   Based on DateLastStockMove
   Portfolio-level (NOT warehouse-level)
========================================= --}}
<div class="text-muted small mb-3"> 
    Inventory aging highlights slow-moving stock and capital risk.
</div>
{{-- ===== STOCK DETAILS TOOLBAR ===== --}}
<div class="d-flex justify-content-between align-items-center mb-4">

    {{-- Aging Filters --}}
    <div class="btn-group btn-group-sm aging-filters" role="group">
        @foreach(['0-30','31-60','61-90','91-120','120+'] as $b)

            <button
                class="btn btn-outline-secondary aging-filter-btn {{ request('bucket') === $b ? 'active' : '' }}"
                data-bucket="{{ $b }}">
                {{ $b }} days
            </button>
        @endforeach
    </div>

    {{-- Export --}}
    <a
       href="{{ route('inventory.export.aging', [
    'bucket' => request('bucket'),
    'sort'   => request('sort', 'value'),
    'dir'    => request('dir', 'desc')
]) }}"

        class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-download"></i>
        Export
    </a>

</div>


@if($rows->count())

@php
    $currentSort = request('sort', 'value');
    $currentDir  = request('dir', 'desc');
@endphp
<table class="table table-hover align-middle aging-stock-table">
    <thead>
        <tr>
            <th>Stock Code</th>
            <th>Description</th>
            <th>Category</th>
            <th class="text-end">
    <a href="#" class="aging-sort" data-sort="qty">
        Qty
        @if($currentSort === 'qty')
            {{ $currentDir === 'asc' ? '↑' : '↓' }}
        @else
            <span class="text-muted">↕</span>
        @endif
    </a>
</th>

<th class="text-end">Aging</th>

<th class="text-end">
    <a href="#" class="aging-sort" data-sort="last_move">
        Last Move
        @if($currentSort === 'last_move')
            {{ $currentDir === 'asc' ? '↑' : '↓' }}
        @else
            <span class="text-muted">↕</span>
        @endif
    </a>
</th>

<th class="text-end">
    <a href="#" class="aging-sort" data-sort="value">
        Value
        @if($currentSort === 'value')
            {{ $currentDir === 'asc' ? '↑' : '↓' }}
        @else
            <span class="text-muted">↕</span>
        @endif
    </a>
</th>

        </tr>
    </thead>

    <tbody>
        @foreach($rows as $r)
        <tr>
            <td class="aging-code">
                <button
                    class="btn btn-link p-0 fw-semibold stock-link"
                    data-code="{{ $r->StockCode }}">
                    {{ $r->StockCode }}
                </button>
            </td>

            <td class="aging-desc">

    <div class="fw-semibold text-dark mb-1">
        {{ $r->LongDesc }}
    </div>

    <div class="text-muted small lh-sm">
        UOM: {{ $r->StockUom ?? '-' }}
    </div>

</td>

            <td>{{ $r->Category }}</td>

            <td class="text-end">
                {{ number_format($r->Qty) }}
            </td>

            <td class="text-end">
               @php
$bucketClass = match($r->Aging) {
    '0-30'   => 'bg-success',
    '31-60'  => 'bg-info',
    '61-90'  => 'bg-warning text-dark',
    '91-120' => 'bg-warning',
    default  => 'bg-danger'
};
@endphp

                <span class="badge {{ $bucketClass }}">
                    {{ $r->Aging }}
                </span>
            </td>

            <td class="text-end">
                {{ \Carbon\Carbon::parse($r->LastMove)->format('M d, Y') }}
            </td>

            <td class="text-end fw-bold">
                ₱{{ number_format($r->TotalValue, 2) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
<div class="mt-3 d-flex justify-content-center">
    <div class="mt-3 d-flex justify-content-center aging-pagination">
    {{ $rows->links('pagination::bootstrap-5') }}
</div>
</div>
@endif

@else
<div class="text-center text-muted py-4">
    No aging stock found.
</div>
@endif
