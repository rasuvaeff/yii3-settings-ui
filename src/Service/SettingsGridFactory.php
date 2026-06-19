<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Service;

use Psr\Container\ContainerInterface;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * Renders the settings list as a Bootstrap-styled GridView.
 *
 * Constructed with the application's DI container (which resolves the GridView
 * column renderers) and called from {@see ListSettingsResponder}. Rendering the
 * grid here — instead of via `GridView::widget()` inside the view template —
 * keeps the host application from having to bootstrap `WidgetFactory`.
 *
 * @internal
 */
final readonly class SettingsGridFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @param list<SettingPresenter> $settings
     */
    public function render(array $settings): string
    {
        return (new GridView($this->container))
            ->dataReader(new IterableDataReader($settings))
            ->containerClass('mt-2')
            ->tableClass('table', 'table-striped', 'table-hover', 'align-middle')
            ->columns(...$this->columns())
            ->render();
    }

    /**
     * @return list<DataColumn>
     */
    private function columns(): array
    {
        return [
            new DataColumn(property: 'group', header: 'Group'),
            new DataColumn(
                header: 'Key',
                content: static fn(SettingPresenter $s): string => '<strong>' . Html::encode($s->label) . '</strong>'
                    . ($s->help !== null ? '<br><small class="text-muted">' . Html::encode($s->help) . '</small>' : ''),
                encodeContent: false,
            ),
            new DataColumn(
                header: 'Value',
                bodyAttributes: static fn(SettingPresenter $s): array => $s->isSecret ? ['class' => 'text-muted'] : [],
                content: static fn(SettingPresenter $s): string => $s->displayValue,
            ),
            new DataColumn(
                header: 'Type',
                content: static fn(SettingPresenter $s): string => '<span class="badge text-bg-secondary">' . Html::encode($s->type) . '</span>',
                encodeContent: false,
            ),
            new DataColumn(
                header: 'Source',
                content: static fn(SettingPresenter $s): string => '<span class="badge text-bg-secondary">' . Html::encode($s->source) . '</span>',
                encodeContent: false,
            ),
            new DataColumn(
                header: '',
                content: static fn(SettingPresenter $s): string => $s->isWritable && !$s->readonly
                    ? Html::a('Edit', $s->editUrl, ['class' => 'btn btn-outline-primary btn-sm'])->render()
                    : '',
                encodeContent: false,
            ),
        ];
    }
}
