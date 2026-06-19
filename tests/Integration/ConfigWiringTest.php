<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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

/**
 * Exercises the package `config/di.php`, which is covered by neither cs (it only
 * reformats it), psalm (`config/` is in `ignoreFiles`), nor the unit suite. Pins
 * the exact set of registered service keys so a removed or renamed binding fails
 * the build, and checks that the class-string aliases map to their own class.
 */
#[CoversNothing]
final class ConfigWiringTest extends TestCase
{
    #[Test]
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

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function aliasesActionsAndValidatorToTheirOwnClass(): void
    {
        $di = $this->di();

        $this->assertSame(SettingValueValidator::class, $di[SettingValueValidator::class]);
        $this->assertSame(ListAction::class, $di[ListAction::class]);
        $this->assertSame(EditAction::class, $di[EditAction::class]);
        $this->assertSame(UpdateAction::class, $di[UpdateAction::class]);
        $this->assertSame(ResetAction::class, $di[ResetAction::class]);
    }

    /**
     * @return array<string, mixed>
     */
    private function di(): array
    {
        /** @var mixed $params */
        $params = require dirname(__DIR__, 2) . '/config/params.php';
        $this->assertIsArray($params);

        /** @var array<string, mixed> $definitions */
        $definitions = require dirname(__DIR__, 2) . '/config/di.php';

        return $definitions;
    }
}
