@forelse($inventoryRows as $row)
<tr class="inventory-row" data-code="{{ $row->StockCode }}" style="cursor:pointer;">
   <td>
    <button
        class="btn btn-link p-0 fw-semibold stock-link"
        data-code="{{ $row->StockCode }}">
        {{ $row->StockCode }}
    </button>
</td>


<td>
    <div class="fw-semibold">
        {{ $row->LongDesc }}
    </div>
</td>

<td>
    {{ $row->StockUom ?? '-' }}
</td>

<td>
    {{ $row->Division ?? '-' }}
</td>
    
    <td>{{ $row->UserField1 }}</td>

    <td class="text-end">{{ number_format($row->EndingBalances) }}</td>


    <td class="text-end fw-bold">
        ₱{{ number_format($row->Totals, 2) }}
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center text-muted py-4">
        No inventory records found
    </td>
</tr>
@endforelse