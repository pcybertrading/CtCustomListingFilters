<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterProductStream;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FilterProductStreamEntity>
 */
class FilterProductStreamCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FilterProductStreamEntity::class;
    }
}
