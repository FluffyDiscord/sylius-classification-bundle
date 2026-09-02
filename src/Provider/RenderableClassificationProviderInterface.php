<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Provider;

interface RenderableClassificationProviderInterface
{
    /**
     * @return list<RenderableClassification>
     */
    public function getClassifications(): array;
}
