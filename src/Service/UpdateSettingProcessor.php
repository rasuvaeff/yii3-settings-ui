<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3Settings\WritableSettingsProvider;
use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Exception\InvalidSettingValueException;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Yiisoft\User\CurrentUser;

/**
 * @internal
 */
final readonly class UpdateSettingProcessor
{
    /**
     * @param array<string, SettingDefinition> $definitions
     */
    public function __construct(
        private WritableSettingsProvider $settingsProvider,
        private ResponseFactoryInterface $responseFactory,
        private SettingValueValidator $validator,
        private EditPageRenderer $editPage,
        private SettingsUrls $urls,
        private array $definitions,
        private ?CurrentUser $currentUser = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function process(string $key, ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->definitions[$key])) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        if ($this->isReadonly($key)) {
            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        $definition = $this->definitions[$key];
        $form = SettingForm::fromParsedBody($request->getParsedBody());

        if ($definition->isSecret() && $form->isBlank()) {
            return $this->redirect();
        }

        if ($definition->type !== SettingType::Bool && !$form->present) {
            return $this->redirect();
        }

        try {
            $value = $this->validator->validate(
                definition: $definition,
                raw: $definition->type === SettingType::Bool && !$form->present ? false : $form->value,
            );
        } catch (InvalidSettingValueException $e) {
            $redisplay = $definition->isSecret() ? new SettingForm(present: false) : $form;

            return $this->editPage->render($key, $redisplay, $e->getMessage());
        }

        $this->settingsProvider->set(key: $key, value: $value);

        $this->eventDispatcher?->dispatch(
            SettingChanged::updated(
                key: $key,
                isSecret: $definition->isSecret(),
                value: $value,
                actor: $this->currentUser?->getId(),
            ),
        );

        return $this->redirect();
    }

    private function isReadonly(string $key): bool
    {
        return $this->definitions[$key]->readonly;
    }

    private function redirect(): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urls->list());
    }
}
