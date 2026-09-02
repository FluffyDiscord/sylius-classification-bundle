<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Provider;

use FluffyDiscord\SyliusClassificationBundle\Product\ClassificationLabelResolverInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;

class RenderableClassificationProvider implements RenderableClassificationProviderInterface, ResetInterface
{
    /**
     * @var list<RenderableClassification>|null
     */
    private ?array $classifications = null;

    public function __construct(
        private readonly ClassificationRepositoryInterface $classificationRepository,
        private readonly ClassificationLabelResolverInterface $labelResolver,
    ) {}

    /**
     * @return list<RenderableClassification>
     */
    public function getClassifications(): array
    {
        if ($this->classifications !== null) {
            return $this->classifications;
        }

        $renderable = [];
        foreach ($this->classificationRepository->findWithValuesAndTranslations() as $classification) {
            $code = $classification->getCode();
            if ($code === null || $code === '') {
                continue;
            }

            $renderable[] = new RenderableClassification(
                $code,
                $this->labelResolver->getLabel($classification),
                $classification->isMultiple(),
                $classification->getPosition(),
            );
        }

        $this->classifications = $renderable;

        return $renderable;
    }

    public function reset(): void
    {
        $this->classifications = null;
    }
}
