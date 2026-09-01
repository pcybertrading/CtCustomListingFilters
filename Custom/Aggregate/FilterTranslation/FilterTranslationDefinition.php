<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterTranslation;

use Acris\Filter\Custom\FilterDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;

class FilterTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'acris_filter_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FilterTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return FilterTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return FilterDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('title', 'title'))->addFlags(new ApiAware()),
            (new StringField('slug', 'slug'))->addFlags(new ApiAware()),
        ]);
    }
}
