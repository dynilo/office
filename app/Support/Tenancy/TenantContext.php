<?php

namespace App\Support\Tenancy;

use App\Models\Organization;
use Closure;

final class TenantContext
{
    private ?string $organizationId = null;

    public function organizationId(): ?string
    {
        return $this->organizationId;
    }

    public function set(?Organization $organization): void
    {
        $this->organizationId = $organization?->id;
    }

    public function setOrganizationId(?string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function clear(): void
    {
        $this->organizationId = null;
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function run(Organization|string $organization, Closure $callback): mixed
    {
        $previous = $this->organizationId;
        $this->organizationId = $organization instanceof Organization ? $organization->id : $organization;

        try {
            return $callback();
        } finally {
            $this->organizationId = $previous;
        }
    }
}
