@extends('layouts.app')

@section('content')
<h1>Device scans</h1>
<p class="muted">Scanner payloads live in MongoDB. They are not the stock ledger, and each payload is capped at 512 characters. Proof-of-delivery files are not stored here.</p>

<section class="panel">
    @if ($error)
        <div class="error" role="alert">{{ $error }}</div>
    @endif
    @if ($events === [])
        <p>No scans recorded. A PDA client posts them to <code>POST /api/v1/scan-events</code> with the telemetry.write scope.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>When</th>
                    <th>Device</th>
                    <th>Symbology</th>
                    <th>Payload</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td>{{ $event->recordedAt }}</td>
                        <td>{{ $event->deviceId }}</td>
                        <td>{{ $event->symbology }}</td>
                        <td>{{ $event->payload }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>
@endsection
