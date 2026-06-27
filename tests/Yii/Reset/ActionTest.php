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
        );

        $response = $action->__invoke('mail.from');

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::contains($provider->removeCalls, 'mail.from');
    }
}
