<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity;

use Sylius\Resource\Model\AbstractTranslation;

class ClassificationValueTranslation extends AbstractTranslation implements ClassificationValueTranslationInterface
{
    protected ?int $id = null;

    protected ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
