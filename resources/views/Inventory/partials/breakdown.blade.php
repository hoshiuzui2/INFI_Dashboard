<h6 class="mb-3">Stock Breakdown — {{ $code }}</h6>

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
        @foreach($rows as $r)
        <tr>
            <td>{{ $r->Warehouse }}</td>
            <td class="text-end">{{ number_format($r->EndingBalances) }}</td>
            <td class="text-end">₱{{ number_format($r->UnitCost,6) }}</td>
            <td class="text-end fw-bold">₱{{ number_format($r->EndingBalances * $r->UnitCost, 2) }}</td>
            <td>{{ \Carbon\Carbon::parse($r->DateLastStockMove)->format('M d, Y') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot class="fw-bold">
<tr>
    <td class="text-end">TOTAL</td>
    <td class="text-end">
        {{ number_format($rows->sum('EndingBalances')) }}
    </td>
    <td></td>
    <td class="text-end">
    ₱{{ number_format($rows->sum(fn($r) => $r->EndingBalances * $r->UnitCost), 2) }}
</td>
    <td></td>
</tr>
</tfoot>
</table>
