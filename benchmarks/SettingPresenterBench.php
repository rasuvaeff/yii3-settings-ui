<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Benchmarks;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;
use Testo\Bench;

final class SettingPresenterBench
{
    #[Bench(
        callables: [
            'secret' => [self::class, 'constructSecret'],
        ],
        calls: 1_000,
        iterations: 10,
    )]
    public static function constructSimple(): SettingPresenter
    {
        return new SettingPresenter(
            definition: new SettingDefinition(key: 'app.name', type: SettingType::String, default: 'My App', label: 'Application name'),
            state: new SettingState(key: 'app.name', effectiveValue: 'My App', hasStoredOverride: false, source: 'default', isSecret: false, isWritable: true),
            editUrl: '/settings/app.name/edit',
        );
    }

    public static function constructSecret(): SettingPresenter
    {
        return new SettingPresenter(
            definition: new SettingDefinition(key: 'api.key', type: SettingType::String, default: '', secret: true, label: 'API Key', group: 'api', help: 'External API key'),
            state: new SettingState(key: 'api.key', effectiveValue: 's3cr3t', hasStoredOverride: true, source: 'db', isSecret: true, isWritable: true),
            editUrl: '/settings/api.key/edit',
        );
    }
}
