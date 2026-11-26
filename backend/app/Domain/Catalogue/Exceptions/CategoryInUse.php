<?php

namespace App\Domain\Catalogue\Exceptions;

use App\Domain\Shared\Exceptions\DomainError;

/**
 * A category still holds active items, so it cannot be deactivated.
 */
final class CategoryInUse extends DomainError
{
    public static function withActiveItems(int $count): self
    {
        $items = $count === 1 ? '1 active item' : "{$count} active items";

        return new self("This category still has {$items}. Move or deactivate them first.", ['active_items' => $count]);
    }

    public function errorCode(): string
    {
        return 'CATEGORY_IN_USE';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This category still has active items. Move or deactivate them first.';
    }
}
