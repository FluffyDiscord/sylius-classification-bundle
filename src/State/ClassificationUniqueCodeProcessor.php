<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sylius\Resource\Model\CodeAwareInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @implements ProcessorInterface<CodeAwareInterface, CodeAwareInterface>
 */
readonly class ClassificationUniqueCodeProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<CodeAwareInterface, CodeAwareInterface> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private TranslatorInterface $translator,
        #[Autowire(param: 'sylius_classification.model.classification.class')]
        private string $classificationClass,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        try {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        } catch (UniqueConstraintViolationException $exception) {
            if (! $data instanceof CodeAwareInterface) {
                throw $exception;
            }

            $codeIndexName = $this->getCodeIndexName($data);
            $driverMessage = $exception->getPrevious()?->getMessage() ?? $exception->getMessage();
            $isCodeCollision = str_contains($driverMessage, $codeIndexName);
            if (! $isCodeCollision) {
                throw $exception;
            }

            throw new UnprocessableEntityHttpException($this->translateCollisionMessage($data), $exception);
        }
    }

    private function getCodeIndexName(CodeAwareInterface $data): string
    {
        return $data instanceof $this->classificationClass
            ? 'uniq_sylius_classification_code'
            : 'uniq_sylius_classification_value_code';
    }

    private function translateCollisionMessage(CodeAwareInterface $data): string
    {
        $messageKey = $data instanceof $this->classificationClass
            ? 'sylius_classification.classification.code.unique'
            : 'sylius_classification.classification_value.code.unique';

        return $this->translator->trans($messageKey, [], 'validators');
    }
}
