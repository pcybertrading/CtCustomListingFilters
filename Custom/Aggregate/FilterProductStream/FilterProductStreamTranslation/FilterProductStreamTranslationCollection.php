<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterProductStream\FilterProductStreamTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FilterProductStreamTranslationEntity>
 */
class FilterProductStreamTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FilterProductStreamTranslationEntity::class;
    }
}
