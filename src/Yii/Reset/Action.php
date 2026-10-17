<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Yii\Reset;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Yiisoft\Router\CurrentRoute;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private ResetSettingProcessor $processor,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->processor->process(key: (string) $this->currentRoute->getArgument('key'));
    }
}
