<?php declare(strict_types=1);

namespace Acris\Filter\Custom;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\CustomField\CustomFieldEntity;

class FilterEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * Transient (non-persisted) helper used when property filters are positioned individually.
     *
     * @var string|null
     */
    protected $acrisPropertyGroupId;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var string|null
     */
    protected $unit;

    /**
     * @var string|null
     */
    protected $filterType;

    /**
     * @var string|null
     */
    protected $filterTextBefore;

    /**
     * @var string|null
     */
    protected $filterTextAfter;

    /**
     * @var boolean
     */
    protected $hide;

    /**
     * @var string
     */
    protected $customFieldId;

    /**
     * @var CustomFieldEntity|null
     */
    protected $customField;

    // the columns are nullable, so keep defaults here - an unset typed property would throw on access
    protected string $displayOn = FilterDefinition::DISPLAY_ON_ALL;

    protected bool $displayProductStream = false;

    /**
     * @var string|null
     */
    protected $limitCategoryFilterScope;

    public function getDisplayProductStream(): bool
    {
        return $this->displayProductStream;
    }

    public function setDisplayProductStream(bool $displayProductStream): void
    {
        $this->displayProductStream = $displayProductStream;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active ?? false;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position ?? 0;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return string|null
     */
    public function getFilterType(): ?string
    {
        return $this->filterType;
    }

    /**
     * @param string|null $filterType
     */
    public function setFilterType(?string $filterType): void
    {
        $this->filterType = $filterType;
    }

    public function getFilterTextBefore(): ?string
    {
        return $this->filterTextBefore;
    }

    public function setFilterTextBefore(?string $filterTextBefore): void
    {
        $this->filterTextBefore = $filterTextBefore;
    }

    public function getFilterTextAfter(): ?string
    {
        return $this->filterTextAfter;
    }

    public function setFilterTextAfter(?string $filterTextAfter): void
    {
        $this->filterTextAfter = $filterTextAfter;
    }

    /**
     * @return string|null
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * @param string|null $unit
     */
    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    /**
     * @return bool
     */
    public function isHide(): bool
    {
        return $this->hide ?? false;
    }

    /**
     * @param bool $hide
     */
    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }

    public function getDisplayOn(): string
    {
        return $this->displayOn;
    }

    public function setDisplayOn(string $displayOn): void
    {
        $this->displayOn = $displayOn;
    }


    public function getCustomFieldId(): string
    {
        return $this->customFieldId;
    }

    public function setCustomFieldId(string $customFieldId): void
    {
        $this->customFieldId = $customFieldId;
    }

    public function getCustomField(): ?CustomFieldEntity
    {
        return $this->customField;
    }

    public function setCustomField(?CustomFieldEntity $customField): void
    {
        $this->customField = $customField;
    }

    public function getLimitCategoryFilterScope(): ?string
    {
        return $this->limitCategoryFilterScope;
    }

    public function setLimitCategoryFilterScope(?string $limitCategoryFilterScope): void
    {
        $this->limitCategoryFilterScope = $limitCategoryFilterScope;
    }

    public function getAcrisPropertyGroupId(): ?string
    {
        return $this->acrisPropertyGroupId;
    }

    public function setAcrisPropertyGroupId(?string $acrisPropertyGroupId): void
    {
        $this->acrisPropertyGroupId = $acrisPropertyGroupId;
    }
}
