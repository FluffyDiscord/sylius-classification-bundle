<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity;

use Sylius\Resource\Model\TranslatableTrait;
use Sylius\Resource\Model\TranslationInterface;

class ClassificationValue implements ClassificationValueInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    protected ?int $id = null;

    protected ?ClassificationInterface $classification = null;

    protected ?string $code = null;

    protected int $position = 0;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $config = null;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClassification(): ?ClassificationInterface
    {
        return $this->classification;
    }

    public function setClassification(?ClassificationInterface $classification): void
    {
        $this->classification = $classification;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
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

    public function getName(): ?string
    {
        return $this->getClassificationValueTranslation()->getName();
    }

    public function setName(?string $name): void
    {
        $this->getClassificationValueTranslation()->setName($name);
    }

    protected function createTranslation(): TranslationInterface
    {
        return new ClassificationValueTranslation();
    }

    private function getClassificationValueTranslation(): ClassificationValueTranslationInterface
    {
        /** @var ClassificationValueTranslationInterface $translation */
        $translation = $this->getTranslation();

        return $translation;
    }
}
