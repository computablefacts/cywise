<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * This trait scopes a query using the `user_id` field of the model it is added to.
 *
 * @property ?int user_id
 * @deprecated
 */
trait HasTenant2
{
    protected static function booted()
    {
        parent::booted();
        static::addGlobalScope('tenant_scope_2', function (Builder $builder) {

            $user = Auth::user();
            $tenantId = $user?->tenant_id;

            if ($tenantId) {

                $customerId = $user->customer_id;

                if ($customerId) {
                    $users = User::select('id')->where('tenant_id', $tenantId)->where('customer_id', $customerId);
                } else {
                    $users = User::select('id')->where('tenant_id', $tenantId);
                }

                $builder->whereIn("{$builder->getModel()->getTable()}.user_id", $users)
                    ->orWhereNull("{$builder->getModel()->getTable()}.user_id");
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tenant(): ?Tenant
    {
        return $this->createdBy?->tenant();
    }
}
