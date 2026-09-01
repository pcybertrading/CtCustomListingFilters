<?php declare(strict_types=1);

namespace Acris\Filter\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FilterEntity>
 */
class FilterCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FilterEntity::class;
    }
}
