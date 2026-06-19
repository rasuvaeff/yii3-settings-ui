<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Service;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;

/**
 * @internal
 */
final readonly class EditSettingResponder
{
    /**
     * @param array<string, SettingDefinition> $definitions
     */
    public function __construct(
        private EditPageRenderer $editPage,
        private ResponseFactoryInterface $responseFactory,
        private array $definitions,
    ) {}

    public function respond(string $key): ResponseInterface
    {
        if (!isset($this->definitions[$key])) {
            return $this->responseFactory->createResponse(Status::NOT_FOUND);
        }

        return $this->editPage->render(
            $key,
            new SettingForm(present: false),
        );
    }
}
