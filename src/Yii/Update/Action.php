<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Yii\Update;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private UpdateSettingProcessor $processor,
    ) {}

    public function __invoke(
        #[RouteArgument('key')]
        string $key,
        ServerRequestInterface $request,
    ): ResponseInterface {
        return $this->processor->process(key: $key, request: $request);
    }
}
