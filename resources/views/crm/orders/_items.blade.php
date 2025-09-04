@if(isset($items) && $items->count())
<div class="table-responsive mt-2">
    <table class="table table-sm table-borderless mb-2">
        <thead>
            <tr class="text-muted">
                <th style="width:35%">Položka</th>
                <th>Kód</th>
                <th>Množství</th>
                <th>Cena/ks</th>
                <th>Sleva/ks</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->sku ?? $item->alt_code ?? '-' }}</td>
                <td>{{ number_format($item->qty, 3, ',', ' ') }}</td>
                <td>{{ $item->unit_price !== null ? number_format($item->unit_price, 2, ',', ' ') . ' Kč' : '-' }}</td>
                <td>{{ $item->unit_price_disc !== null ? number_format($item->unit_price_disc, 2, ',', ' ') . ' Kč' : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Zobrazeno {{ $items->firstItem() }}–{{ $items->lastItem() }} z {{ $items->total() }} položek
        </div>
        <div>
            {{ $items->withQueryString()->links() }}
        </div>
    </div>
</div>
@else
<div class="text-muted small">Žádné položky</div>
@endif
