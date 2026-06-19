<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Renderer;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingsInspector;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;

/**
 * Renders the single-setting edit page. Shared by the GET edit action and the
 * POST update action (which re-renders it with a validation error).
 *
 * @internal
 */
final readonly class EditPageRenderer
{
    /**
     * @param array<string, SettingDefinition> $definitions
     */
    public function __construct(
        private TemplateRendererInterface $renderer,
        private SettingsInspector $inspector,
        private SettingsUrls $urls,
        private array $definitions,
    ) {}

    public function render(
        string $key,
        SettingForm $form,
        ?string $error = null,
    ): ResponseInterface {
        $definition = $this->definitions[$key];
        $state = $this->inspector->describe(key: $key);

        return $this->renderer->render('edit', [
            'form' => $form,
            'key' => $key,
            'definition' => $definition,
            'state' => $state,
            'label' => $definition->label ?? $key,
            'help' => $definition->help,
            'choices' => $definition->choices,
            'readonly' => $definition->readonly,
            'updateUrl' => $this->urls->update($key),
            'resetUrl' => $this->urls->reset($key),
            'listUrl' => $this->urls->list(),
            'error' => $error,
        ]);
    }
}
