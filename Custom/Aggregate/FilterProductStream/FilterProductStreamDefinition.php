<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterProductStream;

use Acris\Filter\Custom\Aggregate\FilterProductStream\FilterProductStreamTranslation\FilterProductStreamTranslationDefinition;
use Shopware\Core\Content\ProductStream\ProductStreamDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class FilterProductStreamDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'acris_filter_product_stream';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FilterProductStreamCollection::class;
    }

    public function getEntityClass(): string
    {
        return FilterProductStreamEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('parent_id', 'parentId', self::class))->addFlags(new ApiAware()),
            (new FkField('pro_stream_id', 'proStreamId', ProductStreamDefinition::class))->addFlags(new ApiAware()),

            (new BoolField('pro_stream_active', 'proStreamActive'))->addFlags(new ApiAware()),
            (new IntField('pro_stream_position', 'proStreamPosition'))->addFlags(new ApiAware()),
            (new BoolField('hide', 'hide'))->addFlags(new ApiAware()),
            (new TranslatedField('proStreamTitle'))->addFlags(new Required(), new ApiAware()),
            (new TranslatedField('proStreamSlug'))->addFlags(new Required(), new ApiAware()),

            (new TranslationsAssociationField(FilterProductStreamTranslationDefinition::class, 'acris_filter_product_stream_id'))->addFlags(new ApiAware()),

            (new ManyToOneAssociationField('proStream', 'pro_stream_id', ProductStreamDefinition::class, 'id', false))->addFlags(new ApiAware()),
            new ManyToOneAssociationField('parent', 'parent_id', self::class, 'id', false),
            (new OneToManyAssociationField('dynamicProductGroups', self::class, 'parent_id', 'id'))->addFlags(new CascadeDelete(), new ApiAware()),
        ]);
    }
}
