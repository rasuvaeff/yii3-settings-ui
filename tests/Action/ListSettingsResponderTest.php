<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\ListSettingsResponder;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

use function Rasuvaeff\Understudy\verify;

#[Test]
#[Covers(ListSettingsResponder::class)]
final class ListSettingsResponderTest extends ActionTestCase
{
    public function rendersFlatPresenterList(): void
    {
        $renderer = $this->renderer();

        $response = $this->listResponder($renderer)->respond();

        Assert::same($response->getStatusCode(), Status::OK);

        verify(fn() => $renderer->render('list'));
        $parameters = $this->renderedParameters();
        Assert::array($parameters)->hasKeys('settings');
        Assert::array($parameters)->hasKeys('gridHtml');
        Assert::true($parameters['gridHtml'] !== '' && $parameters['gridHtml'] !== []);

        /** @var list<SettingPresenter> $settings */
        $settings = $parameters['settings'];
        $groups = array_map(static fn(SettingPresenter $s): string => $s->group, $settings);
        Assert::contains($groups, 'mail');
        Assert::contains($groups, 'billing');
    }

    public function secretValueIsMaskedAndPlaintextAbsentFromViewModel(): void
    {
        $renderer = $this->renderer();

        $this->listResponder($renderer)->respond();

        $parameters = $this->renderedParameters();

        /** @var list<SettingPresenter> $settings */
        $settings = $parameters['settings'];
        $serialized = json_encode(
            array_map(static fn(SettingPresenter $s): string => $s->displayValue, $settings),
            JSON_THROW_ON_ERROR,
        );

        Assert::string($serialized)->contains('(set)');
        Assert::string($serialized)->notContains('sk_live');

        /** @var string $gridHtml */
        $gridHtml = $parameters['gridHtml'];
        Assert::string($gridHtml)->contains('(set)');
        Assert::string($gridHtml)->notContains('sk_live');
    }

    public function sortsSettingsByGroupThenKey(): void
    {
        $renderer = $this->renderer();

        $this->listResponder($renderer)->respond();

        /** @var list<SettingPresenter> $settings */
        $settings = $this->renderedParameters()['settings'];
        $keys = array_map(static fn(SettingPresenter $s): string => $s->group . "\t" . $s->key, $settings);

        $expected = $keys;
        sort($expected);

        Assert::same($keys, $expected);
    }
}
