@extends('layouts.app')

@section('content')
<h1>Stock on the floor</h1>
<p class="muted">On-hand and reserved quantities come from the MySQL ledger. A reservation does not leave the building until a delivery stop is marked delivered.</p>

@can('warehouse.mutate')
<div class="grid">
    <section class="panel">
        <h2>Receive</h2>
        <form method="POST" action="{{ route('warehouse.receipts.store') }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <label>SKU code <input name="sku_code" value="{{ old('sku_code', 'SKU-PALLET-WRAP') }}" required></label>
            <label>Location <input name="location_code" value="{{ old('location_code', 'LOC-A-01') }}" required></label>
            <label>Quantity <input name="quantity" type="number" min="1" max="100000" value="{{ old('quantity', 24) }}" required></label>
            <button type="submit">Post receipt</button>
        </form>
    </section>
    <section class="panel">
        <h2>Hold for a delivery</h2>
        <form method="POST" action="{{ route('warehouse.reservations.store') }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <label>SKU code <input name="sku_code" value="{{ old('sku_code', 'SKU-PALLET-WRAP') }}" required></label>
            <label>Location <input name="location_code" value="{{ old('location_code', 'LOC-A-01') }}" required></label>
            <label>Quantity <input name="quantity" type="number" min="1" max="100000" value="{{ old('quantity', 2) }}" required></label>
            <label>Delivery reference <input name="reference" value="{{ old('reference', 'DEL-1001') }}" required></label>
            <label>Warehouse <input name="warehouse_code" value="{{ old('warehouse_code', auth()->user()->warehouse_code) }}" required></label>
            <button type="submit">Hold stock</button>
        </form>
    </section>
</div>
@endcan

<section class="panel">
    <h2>Balances</h2>
    @if ($balances === [])
        <p>No stock is on hand yet. Receive a quantity into a location to open the ledger.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Location</th>
                    <th>On hand</th>
                    <th>Reserved</th>
                    <th>Available</th>
                    <th>Version</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($balances as $balance)
                    <tr>
                        <td>{{ $balance->skuCode }}</td>
                        <td>{{ $balance->locationCode }}</td>
                        <td>{{ $balance->onHand }}</td>
                        <td>{{ $balance->reserved }}</td>
                        <td>{{ $balance->available }}</td>
                        <td>{{ $balance->version }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection
