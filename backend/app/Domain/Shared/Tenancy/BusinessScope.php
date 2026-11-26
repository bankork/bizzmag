<?php

namespace App\Domain\Shared\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits business-owned models to the current business. With no current
 * business the query returns nothing: the scope fails closed.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
final class BusinessScope implements Scope
{
    /**
     * @param  Builder<covariant TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $businessId = app(CurrentBusiness::class)->id();

        if ($businessId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('business_id'), $businessId);
    }
}
