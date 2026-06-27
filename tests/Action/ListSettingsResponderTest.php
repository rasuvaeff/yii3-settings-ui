<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ListSettingsResponder::class)]
final class ListSettingsResponderTest extends ActionTestCase
{
    public function rendersFlatPresenterList(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->listResponder($renderer)->respond();

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($renderer->view, 'list');
        Assert::array($renderer->parameters)->hasKeys('settings');
        Assert::array($renderer->parameters)->hasKeys('gridHtml');
        Assert::true($renderer->parameters['gridHtml'] !== '' && $renderer->parameters['gridHtml'] !== []);

        /** @var list<SettingPresenter> $settings */
        $settings = $renderer->parameters['settings'];
        $groups = array_map(static fn(SettingPresenter $s): string => $s->group, $settings);
        Assert::contains($groups, 'mail');
        Assert::contains($groups, 'billing');
    }

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

        Assert::string($serialized)->contains('(set)');
        Assert::string($serialized)->notContains('sk_live');

        /** @var string $gridHtml */
        $gridHtml = $renderer->parameters['gridHtml'];
        Assert::string($gridHtml)->contains('(set)');
        Assert::string($gridHtml)->notContains('sk_live');
    }

    public function sortsSettingsByGroupThenKey(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->listResponder($renderer)->respond();

        /** @var list<SettingPresenter> $settings */
        $settings = $renderer->parameters['settings'];
        $keys = array_map(static fn(SettingPresenter $s): string => $s->group . "\t" . $s->key, $settings);

        $expected = $keys;
        sort($expected);

        Assert::same($keys, $expected);
    }
}
