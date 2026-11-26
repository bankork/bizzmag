<?php

namespace App\Domain\Shared\Tenancy;

use Illuminate\Database\Eloquent\Model;

/**
 * For models with a business_id column. Applies BusinessScope and fills
 * business_id from the current business when a record is created.
 *
 * @mixin Model
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('business_id') === null) {
                $model->setAttribute('business_id', app(CurrentBusiness::class)->require());
            }
        });
    }
}
