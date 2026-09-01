<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<FilterTranslationEntity>
 */
class FilterTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return FilterTranslationEntity::class;
    }
}
