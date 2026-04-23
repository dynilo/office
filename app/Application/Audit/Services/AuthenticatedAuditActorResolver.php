<?php

namespace App\Application\Audit\Services;

use App\Application\Audit\Data\AuditActorData;
use Illuminate\Contracts\Auth\Authenticatable;

final class AuthenticatedAuditActorResolver
{
    public function resolve(?Authenticatable $user): ?AuditActorData
    {
        if ($user === null) {
            return null;
        }

        return new AuditActorData(
            type: 'user',
            id: (string) $user->getAuthIdentifier(),
        );
    }
}
