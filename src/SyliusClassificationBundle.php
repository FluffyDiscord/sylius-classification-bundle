<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SyliusClassificationBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
