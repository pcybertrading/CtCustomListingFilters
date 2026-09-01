<?php declare(strict_types=1);

namespace Acris\Filter\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\CustomField\CustomFieldDefinition;

class FilterDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'acris_filter';

    public const DISPLAY_ON_ALL = 'all';
    public const DISPLAY_ON_SEARCH = 'search';
    public const DISPLAY_ON_LISTING = 'listing';

    public const LIMIT_CATEGORY_FILTER_SCOPE_DEFAULT = 'default';
    public const LIMIT_CATEGORY_FILTER_SCOPE_CURRENT_TREE = 'currentTree';

    public const IDENTIFIER_PRODUCT_STREAM = 'product_stream';

    /**
     * Filter identifiers which are filtered by a numeric product field, mapped to the field they filter on.
     */
    public const PRODUCT_RANGE_FILTERS = [
        'width' => 'product.width',
        'height' => 'product.height',
        'length' => 'product.length',
        'weight' => 'product.weight',
    ];

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return FilterCollection::class;
    }

    public function getEntityClass(): string
    {
        return FilterEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),
            (new StringField('identifier','identifier'))->addFlags(new Required()),
            new BoolField('active', 'active'),
            new IntField('position','position'),
            new StringField('filter_type','filterType'),
            new StringField('filter_text_before','filterTextBefore'),
            new StringField('filter_text_after','filterTextAfter'),
            new StringField('unit', 'unit'),
            new BoolField('display_product_stream', 'displayProductStream'),
            new BoolField('hide', 'hide'),
            new StringField('display_on', 'displayOn'),
            new StringField('limit_category_filter_scope', 'limitCategoryFilterScope'),

            new FkField('custom_field_id', 'customFieldId', CustomFieldDefinition::class),
            (new OneToOneAssociationField('customField', 'custom_field_id', 'id', CustomFieldDefinition::class)),
        ]);
    }
}
