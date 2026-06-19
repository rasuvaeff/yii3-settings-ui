<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;

#[CoversClass(ListSettingsResponder::class)]
final class ListSettingsResponderTest extends ActionTestCase
{
    #[Test]
    public function rendersFlatPresenterList(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->listResponder($renderer)->respond();

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('list', $renderer->view);
        $this->assertArrayHasKey('settings', $renderer->parameters);
        $this->assertArrayHasKey('gridHtml', $renderer->parameters);
        $this->assertNotEmpty($renderer->parameters['gridHtml']);

        /** @var list<SettingPresenter> $settings */
        $settings = $renderer->parameters['settings'];
        $groups = array_map(static fn(SettingPresenter $s): string => $s->group, $settings);
        $this->assertContains('mail', $groups);
        $this->assertContains('billing', $groups);
    }

    #[Test]
    public function secretValueIsMaskedAndPlaintextAbsentFromViewModel(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->listResponder($renderer)->respond();

        /** @var list<SettingPresenter> $settings */
        $settings = $renderer->parameters['settings'];
        $serialized = json_encode(
            array_map(static fn(SettingPresenter $s): string => $s->displayValue, $settings),
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringContainsString('(set)', $serialized);
        $this->assertStringNotContainsString('sk_live', $serialized);

        /** @var string $gridHtml */
        $gridHtml = $renderer->parameters['gridHtml'];
        $this->assertStringContainsString('(set)', $gridHtml);
        $this->assertStringNotContainsString('sk_live', $gridHtml);
    }

    #[Test]
    public function sortsSettingsByGroupThenKey(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->listResponder($renderer)->respond();

        /** @var list<SettingPresenter> $settings */
        $settings = $renderer->parameters['settings'];
        $keys = array_map(static fn(SettingPresenter $s): string => $s->group . "\t" . $s->key, $settings);

        $expected = $keys;
        sort($expected);

        $this->assertSame($expected, $keys);
    }
}
