<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Yii\Edit;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Yiisoft\Router\CurrentRoute;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private EditSettingResponder $responder,
        private CurrentRoute $currentRoute,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responder->respond(key: (string) $this->currentRoute->getArgument('key'));
    }
}
