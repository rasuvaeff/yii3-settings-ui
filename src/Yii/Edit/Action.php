<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Yii\Edit;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private EditSettingResponder $responder,
    ) {}

    public function __invoke(
        #[RouteArgument('key')]
        string $key,
    ): ResponseInterface {
        return $this->responder->respond(key: $key);
    }
}
