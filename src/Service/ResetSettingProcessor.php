<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\WritableSettingsProvider;
use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Yiisoft\User\CurrentUser;

/**
 * @internal
 */
final readonly class ResetSettingProcessor
{
    /**
     * @param array<string, SettingDefinition> $definitions
     */
    public function __construct(
        private WritableSettingsProvider $settingsProvider,
        private ResponseFactoryInterface $responseFactory,
        private SettingsUrls $urls,
        private array $definitions,
        private ?CurrentUser $currentUser = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
    ) {}

    public function process(string $key): ResponseInterface
    {
        if (!isset($this->definitions[$key])) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        if ($this->definitions[$key]->readonly) {
            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        $this->settingsProvider->remove(key: $key);

        $this->eventDispatcher?->dispatch(
            SettingChanged::reset(
                key: $key,
                isSecret: $this->definitions[$key]->isSecret(),
                actor: $this->currentUser?->getId(),
            ),
        );

        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader('Location', $this->urls->list());
    }
}
