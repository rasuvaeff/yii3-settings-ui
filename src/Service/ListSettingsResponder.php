<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Service;

use Psr\Http\Message\ResponseInterface;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingsInspector;
use Rasuvaeff\Yii3SettingsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;

/**
 * @internal
 */
final readonly class ListSettingsResponder
{
    /**
     * @param array<string, SettingDefinition> $definitions
     */
    public function __construct(
        private TemplateRendererInterface $renderer,
        private SettingsInspector $settingsInspector,
        private SettingsUrls $urls,
        private SettingsGridFactory $gridFactory,
        private array $definitions,
    ) {}

    public function respond(): ResponseInterface
    {
        $presenters = $this->buildPresenters();

        return $this->renderer->render('list', [
            'settings' => $presenters,
            'gridHtml' => $this->gridFactory->render($presenters),
        ]);
    }

    /**
     * @return list<SettingPresenter>
     */
    private function buildPresenters(): array
    {
        $presenters = [];

        foreach ($this->definitions as $key => $definition) {
            $state = $this->settingsInspector->describe(key: $key);
            $presenters[] = new SettingPresenter(
                definition: $definition,
                state: $state,
                editUrl: $this->urls->edit($key),
            );
        }

        usort(
            $presenters,
            static function (SettingPresenter $a, SettingPresenter $b): int {
                $byGroup = strcmp($a->group, $b->group);

                return $byGroup !== 0 ? $byGroup : strcmp($a->key, $b->key);
            },
        );

        return $presenters;
    }
}
