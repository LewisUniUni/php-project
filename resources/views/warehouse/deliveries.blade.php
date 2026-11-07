@extends('layouts.app')

@section('content')
<h1>Delivery stops</h1>
<p class="muted">A held reservation writes an outbox row. The relay creates one planned stop for that reference. Marking it delivered consumes the reservation.</p>

<section class="panel">
    @if ($stops === [])
        <p>No stops yet. Hold stock with a delivery reference, then run the outbox relay. On a local stack the queue worker does that after the request.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Next</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stops as $stop)
                    <tr>
                        <td>{{ $stop->reference }}</td>
                        <td><span class="chip">{{ $stop->status }}</span></td>
                        <td>{{ $stop->skuCode }}</td>
                        <td>{{ $stop->quantity }}</td>
                        <td>
                            @can('warehouse.mutate')
                                <div class="actions">
                                    @forelse ($stop->allowedTransitions as $next)
                                        <form method="POST" action="{{ route('warehouse.deliveries.transition', $stop->id) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $next }}">
                                            <button type="submit">{{ str_replace('_', ' ', $next) }}</button>
                                        </form>
                                    @empty
                                        <span class="muted">No further moves</span>
                                    @endforelse
                                </div>
                            @else
                                <span class="muted">Planner view</span>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection
