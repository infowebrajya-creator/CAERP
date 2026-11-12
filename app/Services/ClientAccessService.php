<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Enforces role-based client & work visibility across staff and client portal users.
 */
class ClientAccessService
{
    public function visibleClientsQuery(User $user): Builder
    {
        $query = Client::query();

        if ($user->isAdminOrPartner()) {
            return $query;
        }

        if ($user->isClient()) {
            return $query->where('user_id', $user->id);
        }

        if ($user->hasRole(User::ROLE_MANAGER)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('assigned_ca_id', $user->id)
                ->orWhereHas('staff', fn (Builder $sq) => $sq->where('users.id', $user->id));
        });
    }

    public function canAccessClient(User $user, Client $client): bool
    {
        if ($user->isAdminOrPartner() || $user->hasRole(User::ROLE_MANAGER)) {
            return true;
        }

        if ($user->isClient()) {
            return (int) $client->user_id === (int) $user->id;
        }

        return (int) $client->assigned_ca_id === (int) $user->id
            || $client->staff()->where('users.id', $user->id)->exists();
    }

    public function visibleTasksQuery(User $user): Builder
    {
        $query = Task::query();

        if ($user->isManagerOrAbove()) {
            return $query;
        }

        if ($user->isClient()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('assigned_to', $user->id)
                ->orWhere('created_by', $user->id);
        });
    }

    public function canAccessTask(User $user, Task $task): bool
    {
        if ($user->isManagerOrAbove()) {
            return true;
        }

        if ($user->isClient()) {
            return false;
        }

        return (int) $task->assigned_to === (int) $user->id
            || (int) $task->created_by === (int) $user->id;
    }

    public function visibleAuditsQuery(User $user): Builder
    {
        $query = Audit::query();

        if ($user->isManagerOrAbove()) {
            return $query;
        }

        if ($user->isClient()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('lead_auditor_id', $user->id)
                ->orWhereHas('teamMembers', fn (Builder $tq) => $tq->where('users.id', $user->id));
        });
    }

    public function canAccessAudit(User $user, Audit $audit): bool
    {
        if ($user->isManagerOrAbove()) {
            return true;
        }

        if ($user->isClient()) {
            return false;
        }

        return (int) $audit->lead_auditor_id === (int) $user->id
            || $audit->teamMembers()->where('users.id', $user->id)->exists();
    }

    public function portalClientsQuery(User $user): Builder
    {
        return Client::query()->where('user_id', $user->id);
    }

    public function portalClientIds(User $user): Collection
    {
        return $this->portalClientsQuery($user)->pluck('id');
    }

    public function resolveClientForPortalUser(User $user): Client
    {
        return $this->portalClientsQuery($user)->firstOrFail();
    }
}
