<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\Reset;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Yii\Reset\Action as YiiResetAction;

#[CoversClass(YiiResetAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokesProcessorWithKey(): void
    {
        $provider = new RecordingWritableProvider();
        $action = new YiiResetAction(
            processor: $this->resetProcessor(provider: $provider),
        );

        $response = $action->__invoke('mail.from');

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertContains('mail.from', $provider->removeCalls);
    }
}
