<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\TransitionStopRequest;
use App\Models\User;
use App\Modules\Delivery\Actions\ListStops;
use App\Modules\Delivery\Actions\TransitionStop;
use App\Modules\Delivery\Enums\StopStatus;
use App\Modules\Identity\CurrentActor;
use App\Modules\Inventory\Actions\ListBalances;
use App\Modules\Inventory\Actions\ReceiveStock;
use App\Modules\Inventory\Actions\ReleaseReservation;
use App\Modules\Inventory\Actions\ReserveStock;
use App\Modules\Telemetry\Actions\ListScanEvents;
use App\Support\Actor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

final class WarehouseController extends Controller
{
    public function stock(ListBalances $balances): View
    {
        return view('warehouse.stock', [
            'balances' => $balances->execute(),
        ]);
    }

    public function receive(StoreReceiptRequest $request, ReceiveStock $receive, CurrentActor $actors): RedirectResponse
    {
        $result = $receive->execute(
            skuCode: $request->string('sku_code')->toString(),
            locationCode: $request->string('location_code')->toString(),
            quantity: $request->integer('quantity'),
            idempotencyKey: $request->string('idempotency_key')->toString(),
            actor: $this->actor($request, $actors),
        );

        return back()->with('status', $result->replayed
            ? 'That receipt was already recorded.'
            : 'Received '.$request->integer('quantity').' into '.$request->string('location_code')->toString().'.');
    }

    public function reserve(StoreReservationRequest $request, ReserveStock $reserve, CurrentActor $actors): RedirectResponse
    {
        $result = $reserve->execute(
            skuCode: $request->string('sku_code')->toString(),
            locationCode: $request->string('location_code')->toString(),
            quantity: $request->integer('quantity'),
            reference: $request->string('reference')->toString(),
            warehouseCode: $request->string('warehouse_code')->toString(),
            idempotencyKey: $request->string('idempotency_key')->toString(),
            actor: $this->actor($request, $actors),
        );

        return redirect()->route('warehouse.deliveries')->with('status', $result->replayed
            ? 'That reservation was already held.'
            : 'Held '.$result->quantity.' for '.$result->reference.'. The delivery stop appears after the outbox relay.');
    }

    public function release(Request $request, string $reservation, ReleaseReservation $release, CurrentActor $actors): RedirectResponse
    {
        $release->execute($reservation, $this->actor($request, $actors));

        return back()->with('status', 'Reservation released. Stock is available again. A planned delivery stop is not recalled.');
    }

    public function deliveries(ListStops $stops): View
    {
        return view('warehouse.deliveries', [
            'stops' => $stops->execute(),
        ]);
    }

    public function transition(TransitionStopRequest $request, string $stop, TransitionStop $transition, CurrentActor $actors): RedirectResponse
    {
        $status = StopStatus::from($request->string('status')->toString());
        $result = $transition->execute($stop, $status, $this->actor($request, $actors));

        return back()->with('status', 'Stop '.$result->reference.' is now '.$result->status.'.');
    }

    public function scans(ListScanEvents $events): View
    {
        try {
            $rows = $events->execute();
            $error = null;
        } catch (Throwable) {
            $rows = [];
            $error = 'Scan history is unavailable. The document store did not answer.';
        }

        return view('warehouse.scans', [
            'events' => $rows,
            'error' => $error,
        ]);
    }

    private function actor(Request $request, CurrentActor $actors): Actor
    {
        $user = $request->user();

        if ($user instanceof User) {
            return new Actor('user', (string) $user->getAuthIdentifier());
        }

        return $actors->fromRequest($request);
    }
}
