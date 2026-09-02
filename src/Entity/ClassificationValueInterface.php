<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity;

use Sylius\Resource\Model\CodeAwareInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;

interface ClassificationValueInterface extends ResourceInterface, CodeAwareInterface, TranslatableInterface
{
    public function getId(): ?int;

    public function getClassification(): ?ClassificationInterface;

    public function setClassification(?ClassificationInterface $classification): void;

    public function getPosition(): int;

    public function setPosition(int $position): void;

    /**
     * @return array<string, mixed>|null
     */
    public function getConfig(): ?array;

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void;

    public function getName(): ?string;

    public function setName(?string $name): void;
}
