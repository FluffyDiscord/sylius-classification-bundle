<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Sylius\Resource\Model\CodeAwareInterface;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;

interface ClassificationInterface extends ResourceInterface, CodeAwareInterface, TranslatableInterface
{
    public function getId(): ?int;

    public function isMultiple(): bool;

    public function setMultiple(bool $multiple): void;

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

    /**
     * @return Collection<int, ClassificationValueInterface>
     */
    public function getValues(): Collection;

    public function getName(): ?string;

    public function setName(?string $name): void;
}
