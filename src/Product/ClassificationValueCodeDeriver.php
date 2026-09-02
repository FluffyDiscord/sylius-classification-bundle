<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Product;

use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\ClassificationValueTranslationInterface;
use FluffyDiscord\SyliusClassificationBundle\Repository\ClassificationValueRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;

readonly class ClassificationValueCodeDeriver implements ClassificationValueCodeDeriverInterface
{
    public function __construct(
        private ClassificationValueRepositoryInterface $classificationValueRepository,
        #[Autowire(param: 'kernel.default_locale')]
        private string $defaultLocale,
    ) {}

    public function derive(ClassificationValueInterface $classificationValue): void
    {
        $existingCode = $classificationValue->getCode();
        if ($existingCode !== null && $existingCode !== '') {
            return;
        }

        $classificationCode = $classificationValue->getClassification()?->getCode();
        if ($classificationCode === null || $classificationCode === '') {
            return;
        }

        $name = $this->resolveName($classificationValue);
        if ($name === null) {
            return;
        }

        $slug = (new AsciiSlugger($this->defaultLocale))->slug($name)->lower()->toString();
        if ($slug === '') {
            return;
        }

        $freeCode = $this->resolveFreeCode($classificationCode . '_' . $slug);
        if ($freeCode === null) {
            return;
        }

        $classificationValue->setCode($freeCode);
    }

    private function getCodeMaxLength(): int
    {
        return 96;
    }

    private function getMaxCollisionSuffix(): int
    {
        return 99;
    }

    private function resolveFreeCode(string $base): ?string
    {
        $candidate = mb_substr($base, 0, $this->getCodeMaxLength());
        if ($this->isCodeFree($candidate)) {
            return $candidate;
        }

        for ($suffix = 2; $suffix <= $this->getMaxCollisionSuffix(); ++$suffix) {
            $suffixPart = '_' . $suffix;
            $trimmedBase = mb_substr($base, 0, $this->getCodeMaxLength() - mb_strlen($suffixPart));
            $candidate = $trimmedBase . $suffixPart;

            if ($this->isCodeFree($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isCodeFree(string $code): bool
    {
        return $this->classificationValueRepository->findOneByCode($code) === null;
    }

    private function resolveName(ClassificationValueInterface $classificationValue): ?string
    {
        $fallback = null;
        foreach ($classificationValue->getTranslations() as $translation) {
            if (! $translation instanceof ClassificationValueTranslationInterface) {
                continue;
            }

            $name = $translation->getName();
            if ($name === null || trim($name) === '') {
                continue;
            }

            if ($translation->getLocale() === $this->defaultLocale) {
                return $name;
            }

            $fallback ??= $name;
        }

        return $fallback;
    }
}
