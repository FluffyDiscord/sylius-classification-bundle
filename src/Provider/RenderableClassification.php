<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Provider;

readonly class RenderableClassification
{
    public function __construct(
        public string $code,
        public string $label,
        public bool $multiple,
        public int $position,
    ) {}
}
