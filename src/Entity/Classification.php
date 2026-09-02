<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\TranslatableTrait;
use Sylius\Resource\Model\TranslationInterface;

class Classification implements ClassificationInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    protected ?int $id = null;

    protected ?string $code = null;

    protected bool $multiple = false;

    protected int $position = 0;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $config = null;

    /**
     * @var Collection<int, ClassificationValueInterface>
     */
    protected Collection $values;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
        $this->values = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function setMultiple(bool $multiple): void
    {
        $this->multiple = $multiple;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    /**
     * @return Collection<int, ClassificationValueInterface>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    public function getName(): ?string
    {
        return $this->getClassificationTranslation()->getName();
    }

    public function setName(?string $name): void
    {
        $this->getClassificationTranslation()->setName($name);
    }

    protected function createTranslation(): TranslationInterface
    {
        return new ClassificationTranslation();
    }

    private function getClassificationTranslation(): ClassificationTranslationInterface
    {
        /** @var ClassificationTranslationInterface $translation */
        $translation = $this->getTranslation();

        return $translation;
    }
}
