<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterProductStream\FilterProductStreamTranslation;

use Acris\Filter\Custom\Aggregate\FilterProductStream\FilterProductStreamDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class FilterProductStreamTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'acris_filter_product_stream_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FilterProductStreamTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return FilterProductStreamTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return FilterProductStreamDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('pro_stream_title', 'proStreamTitle'))->addFlags(new Required(), new ApiAware()),
            (new StringField('pro_stream_slug', 'proStreamSlug'))->addFlags(new Required(), new ApiAware()),
        ]);
    }
}
