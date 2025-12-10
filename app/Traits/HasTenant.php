<?php

namespace App\Traits;

use App\Models\AssetTag;
use App\Models\AssetTagHash;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * This trait scopes a query using the `created_by` field of the model it is added to.
 *
 * @property ?int $created_by
 */
trait HasTenant
{
    protected static function booted()
    {
        parent::booted();
        static::addGlobalScope('tenant_scope', function (Builder $builder) {

            $user = Auth::user();
            $tenantId = $user?->tenant_id;

            if ($tenantId) {

                $customerId = $user->customer_id;

                if ($customerId) {
                    $users = User::select('id')->where('tenant_id', $tenantId)->where('customer_id', $customerId);
                } else {
                    $users = User::select('id')->where('tenant_id', $tenantId);
                }

                if ($builder->getModel()->getTable() === 'am_assets') {
                    // Get all hashes for current user
                    $hashes = AssetTagHash::withoutGlobalScope('tenant_scope')->where('hash', '=', $user->email)->get();
                    // dump('---- $hashes->toArray() ----');
                    // dump($hashes->toArray());

                    // Get all tags from hashes (tag and hash should have the same created_by ID)
                    $tags = $hashes->flatMap(function ($hash) {
                        return AssetTag::withoutGlobalScope('tenant_scope')
                            ->where('tag', $hash->tag)
                            ->where('created_by', $hash->created_by)
                            ->get();
                    });
                    // dump('---- $tags->toArray() ----');
                    // dump($tags->toArray());

                    // Get all asset_ids from tags without duplicates
                    $assetIds = $tags->pluck('asset_id')->unique()->values();

                    if ($assetIds->isNotEmpty()) {
                        // dump('---- $assetIds ----');
                        // dump($assetIds);
                        $builder->whereIn("{$builder->getModel()->getTable()}.created_by", $users)
                            ->orWhereNull("{$builder->getModel()->getTable()}.created_by")
                            ->orWhereIn("{$builder->getModel()->getTable()}.id", $assetIds);
                    } else {
                        $builder->whereIn("{$builder->getModel()->getTable()}.created_by", $users)
                            ->orWhereNull("{$builder->getModel()->getTable()}.created_by");
                    }
                } else {
                    $builder->whereIn("{$builder->getModel()->getTable()}.created_by", $users)
                        ->orWhereNull("{$builder->getModel()->getTable()}.created_by");
                }
            }
        });
    }

    public function createdBy(): User
    {
        return User::where('id', $this->created_by)->firstOrFail();
    }

    public function tenant(): ?Tenant
    {
        return $this->createdBy()->tenant();
    }
}
