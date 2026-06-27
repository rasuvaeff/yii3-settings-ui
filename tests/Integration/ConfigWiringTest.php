<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Integration;

use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsGridFactory;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsUrls;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Rasuvaeff\Yii3SettingsUi\Yii\Edit\Action as EditAction;
use Rasuvaeff\Yii3SettingsUi\Yii\List\Action as ListAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Reset\Action as ResetAction;
use Rasuvaeff\Yii3SettingsUi\Yii\Update\Action as UpdateAction;
use Testo\Assert;
use Testo\Codecov\CoversNothing;
use Testo\Test;

/**
 * Exercises the package `config/di.php`, which is covered by neither cs (it only
 * reformats it), psalm (`config/` is in `ignoreFiles`), nor the unit suite. Pins
 * the exact set of registered service keys so a removed or renamed binding fails
 * the build, and checks that the class-string aliases map to their own class.
 */
#[Test]
#[CoversNothing]
final class ConfigWiringTest
{
    public function registersExactlyTheExpectedServiceKeys(): void
    {
        $expected = [
            TemplateRendererInterface::class,
            SettingValueValidator::class,
            SettingsUrls::class,
            EditPageRenderer::class,
            SettingsGridFactory::class,
            ListSettingsResponder::class,
            EditSettingResponder::class,
            UpdateSettingProcessor::class,
            ResetSettingProcessor::class,
            ListAction::class,
            EditAction::class,
            UpdateAction::class,
            ResetAction::class,
        ];
        sort($expected);

        $actual = array_keys($this->di());
        sort($actual);

        Assert::same($actual, $expected);
    }

    public function aliasesActionsAndValidatorToTheirOwnClass(): void
    {
        $di = $this->di();

        Assert::same($di[SettingValueValidator::class], SettingValueValidator::class);
        Assert::same($di[ListAction::class], ListAction::class);
        Assert::same($di[EditAction::class], EditAction::class);
        Assert::same($di[UpdateAction::class], UpdateAction::class);
        Assert::same($di[ResetAction::class], ResetAction::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function di(): array
    {
        /** @var mixed $params */
        $params = require dirname(__DIR__, 2) . '/config/params.php';
        Assert::true(is_array($params));

        /** @var array<string, mixed> $definitions */
        $definitions = require dirname(__DIR__, 2) . '/config/di.php';

        return $definitions;
    }
}
