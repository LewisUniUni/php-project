<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Models\User;
use App\Support\Actor;
use App\Support\Values\Identifier;
use Illuminate\Http\Request;
use Laravel\Passport\Client;

final class CurrentActor
{
    public function fromRequest(Request $request): Actor
    {
        $webUser = $request->user('web');
        if ($webUser instanceof User) {
            return new Actor('user', Identifier::string($webUser->getAuthIdentifier()));
        }

        $guard = auth('api');
        if (method_exists($guard, 'client')) {
            $client = $guard->client();
            if ($client instanceof Client) {
                return new Actor('client', Identifier::string($client->getKey()));
            }
        }

        return new Actor('anonymous', 'anonymous');
    }
}
