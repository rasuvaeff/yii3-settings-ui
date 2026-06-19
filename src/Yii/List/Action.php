<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Yii\List;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;

/**
 * @api
 */
final readonly class Action
{
    public function __construct(
        private ListSettingsResponder $responder,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responder->respond();
    }
}
