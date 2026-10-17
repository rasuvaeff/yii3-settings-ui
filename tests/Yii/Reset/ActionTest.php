<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\Reset;

use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Yii\Reset\Action as YiiResetAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(YiiResetAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesProcessorWithKey(): void
    {
        $provider = new RecordingWritableProvider();
        $action = new YiiResetAction(
            processor: $this->resetProcessor(provider: $provider),
            currentRoute: $this->currentRoute(['key' => 'mail.from']),
        );

        $response = $action->__invoke();

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::contains($provider->removeCalls, 'mail.from');
    }

    public function missingRouteArgumentDegradesToNotFound(): void
    {
        $provider = new RecordingWritableProvider();
        $action = new YiiResetAction(
            processor: $this->resetProcessor(provider: $provider),
            currentRoute: $this->currentRoute([]),
        );

        $response = $action->__invoke();

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
        Assert::same($provider->removeCalls, []);
    }
}
