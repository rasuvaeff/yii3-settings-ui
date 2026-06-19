<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Yii\Reset;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private ResetSettingProcessor $processor,
    ) {}

    public function __invoke(
        #[RouteArgument('key')]
        string $key,
    ): ResponseInterface {
        return $this->processor->process(key: $key);
    }
}
