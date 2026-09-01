<?php declare(strict_types=1);

namespace Acris\Filter\Custom\Aggregate\FilterProductStream\FilterProductStreamTranslation;

use Acris\Filter\Custom\Aggregate\FilterProductStream\FilterProductStreamEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class FilterProductStreamTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $acrisFilterProductStreamId;

    /**
     * @var string|null
     */
    protected $proStreamTitle;

    /**
     * @var string|null
     */
    protected $proStreamSlug;

    /**
     * @var FilterProductStreamEntity|null
     */
    protected $acrisFilterProductStream;

    public function getAcrisFilterProductStreamId(): string
    {
        return $this->acrisFilterProductStreamId;
    }

    public function setAcrisFilterProductStreamId(string $acrisFilterProductStreamId): void
    {
        $this->acrisFilterProductStreamId = $acrisFilterProductStreamId;
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

    public function getAcrisFilterProductStream(): ?FilterProductStreamEntity
    {
        return $this->acrisFilterProductStream;
    }

    public function setAcrisFilterProductStream(?FilterProductStreamEntity $acrisFilterProductStream): void
    {
        $this->acrisFilterProductStream = $acrisFilterProductStream;
    }
}
