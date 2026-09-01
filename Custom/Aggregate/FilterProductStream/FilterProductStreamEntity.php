<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterProductStream;

use Acris\Filter\Custom\Aggregate\FilterProductStream\FilterProductStreamTranslation\FilterProductStreamTranslationCollection;
use Shopware\Core\Content\ProductStream\ProductStreamEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class FilterProductStreamEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $parentId;

    /**
     * @var FilterProductStreamEntity|null
     */
    protected $parent;

    /**
     * @var FilterProductStreamCollection|null
     */
    protected $dynamicProductGroups;

    /**
     * @var boolean
     */
    protected $proStreamActive;

    /**
     * @var int|null
     */
    protected $proStreamPosition;

    /**
     * @var boolean
     */
    protected $hide;

    /**
     * @var string|null
     */
    protected $proStreamTitle;

    /**
     * @var string|null
     */
    protected $proStreamSlug;

    /**
     * @var FilterProductStreamTranslationCollection|null
     */
    protected $translations;

    /**
     * @var string|null
     */
    protected $proStreamId;

    /**
     * @var ProductStreamEntity|null
     */
    protected $proStream;

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getParent(): ?FilterProductStreamEntity
    {
        return $this->parent;
    }

    public function setParent(?FilterProductStreamEntity $parent): void
    {
        $this->parent = $parent;
    }

    public function getDynamicProductGroups(): ?FilterProductStreamCollection
    {
        return $this->dynamicProductGroups;
    }

    public function setDynamicProductGroups(?FilterProductStreamCollection $dynamicProductGroups): void
    {
        $this->dynamicProductGroups = $dynamicProductGroups;
    }

    public function getHide(): bool
    {
        return (bool) $this->hide;
    }

    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }

    public function getProStreamActive(): bool
    {
        return (bool) $this->proStreamActive;
    }

    public function setProStreamActive(bool $proStreamActive): void
    {
        $this->proStreamActive = $proStreamActive;
    }

    public function getProStreamPosition(): ?int
    {
        return $this->proStreamPosition;
    }

    public function setProStreamPosition(?int $proStreamPosition): void
    {
        $this->proStreamPosition = $proStreamPosition;
    }

    public function getProStreamTitle(): ?string
    {
        return $this->proStreamTitle;
    }

    public function setProStreamTitle(?string $proStreamTitle): void
    {
        $this->proStreamTitle = $proStreamTitle;
    }

    public function getProStreamSlug(): ?string
    {
        return $this->proStreamSlug;
    }

    public function setProStreamSlug(?string $proStreamSlug): void
    {
        $this->proStreamSlug = $proStreamSlug;
    }

    public function getTranslations(): ?FilterProductStreamTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(?FilterProductStreamTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getProStreamId(): ?string
    {
        return $this->proStreamId;
    }

    public function setProStreamId(?string $proStreamId): void
    {
        $this->proStreamId = $proStreamId;
    }

    public function getProStream(): ?ProductStreamEntity
    {
        return $this->proStream;
    }

    public function setProStream(?ProductStreamEntity $proStream): void
    {
        $this->proStream = $proStream;
    }
}
