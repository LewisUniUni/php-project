<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\ListBalances;
use Illuminate\Http\JsonResponse;

final class StockBalanceController extends Controller
{
    public function index(ListBalances $balances): JsonResponse
    {
        $rows = $balances->execute();

        return response()->json([
            'data' => array_map(static fn ($row) => $row->toArray(), $rows),
            'meta' => ['limit' => 100],
        ]);
    }
}
