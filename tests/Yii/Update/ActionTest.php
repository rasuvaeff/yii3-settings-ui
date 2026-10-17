<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\Update;

use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Yii\Update\Action as YiiUpdateAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(YiiUpdateAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesProcessorWithKeyAndRequest(): void
    {
        $provider = new RecordingWritableProvider();
        $renderer = $this->renderer();
        $action = new YiiUpdateAction(
            processor: $this->updateProcessor(provider: $provider, renderer: $renderer),
            currentRoute: $this->currentRoute(['key' => 'orders.max_items']),
        );

        $response = $action->__invoke(
            $this->request('POST', parsedBody: ['Setting' => ['value' => '250']]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($provider->setCalls['orders.max_items'], 250);
    }

    public function missingRouteArgumentDegradesToNotFound(): void
    {
        $provider = new RecordingWritableProvider();
        $action = new YiiUpdateAction(
            processor: $this->updateProcessor(provider: $provider, renderer: $this->renderer()),
            currentRoute: $this->currentRoute([]),
        );

        $response = $action->__invoke(
            $this->request('POST', parsedBody: ['Setting' => ['value' => '250']]),
        );

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
        Assert::same($provider->setCalls, []);
    }
}
