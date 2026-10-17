<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Double;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\TemplateRendererInterface;

/**
 * Renders nothing to HTML: writes the view name and the serialized template
 * parameters into the response body, so dispatch-level tests can assert that
 * a route argument actually reached the template.
 */
final class ParameterEchoRenderer implements TemplateRendererInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    /**
     * @param array<string, mixed> $parameters
     */
    #[\Override]
    public function render(string $view, array $parameters = []): ResponseInterface
    {
        $body = $this->responseFactory->createResponse(Status::OK)->getBody();
        $body->write($view . ' ' . (json_encode($parameters) ?: ''));

        return $this->responseFactory->createResponse(Status::OK)->withBody($body);
    }
}
