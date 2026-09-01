<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterTranslation;

use Acris\Filter\Custom\FilterEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class FilterTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $acrisFilterId;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $slug;

    /**
     * @var FilterEntity|null
     */
    protected $acrisFilter;

    public function getAcrisFilterId(): string
    {
        return $this->acrisFilterId;
    }

    public function setAcrisFilterId(string $acrisFilterId): void
    {
        $this->acrisFilterId = $acrisFilterId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getAcrisFilter(): ?FilterEntity
    {
        return $this->acrisFilter;
    }

    public function setAcrisFilter(?FilterEntity $acrisFilter): void
    {
        $this->acrisFilter = $acrisFilter;
    }
}
