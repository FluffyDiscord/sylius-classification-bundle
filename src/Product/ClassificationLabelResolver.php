<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Product;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationTranslationInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueTranslationInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class ClassificationLabelResolver implements ClassificationLabelResolverInterface
{
    public function __construct(
        private RequestStack $requestStack,
        #[Autowire(param: 'kernel.default_locale')]
        private string $defaultLocale,
    ) {}

    public function getLabel(ClassificationInterface|ClassificationValueInterface $subject): string
    {
        $names = $this->getNamesByLocale($subject);

        $requestLocale = $this->requestStack->getCurrentRequest()?->getLocale();
        $preferredLocales = array_filter([$requestLocale, $this->defaultLocale]);

        foreach ($preferredLocales as $locale) {
            $name = $names[$locale] ?? null;
            if ($name !== null) {
                return $name;
            }
        }

        $firstName = reset($names);
        if (is_string($firstName)) {
            return $firstName;
        }

        return (string) $subject->getCode();
    }

    /**
     * @return array<string, string>
     */
    private function getNamesByLocale(ClassificationInterface|ClassificationValueInterface $subject): array
    {
        $names = [];
        foreach ($subject->getTranslations() as $translation) {
            if (! $translation instanceof ClassificationTranslationInterface && ! $translation instanceof ClassificationValueTranslationInterface) {
                continue;
            }

            $locale = $translation->getLocale();
            $name = $translation->getName();
            if ($locale === null || $name === null || trim($name) === '') {
                continue;
            }

            $names[$locale] = $name;
        }

        return $names;
    }
}
