<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Yii\Update;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Yiisoft\Router\CurrentRoute;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private UpdateSettingProcessor $processor,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->processor->process(
            key: (string) $this->currentRoute->getArgument('key'),
            request: $request,
        );
    }
}
