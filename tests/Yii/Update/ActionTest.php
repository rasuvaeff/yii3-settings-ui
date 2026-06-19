<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\Update;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Yii\Update\Action as YiiUpdateAction;

#[CoversClass(YiiUpdateAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokesProcessorWithKeyAndRequest(): void
    {
        $provider = new RecordingWritableProvider();
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiUpdateAction(
            processor: $this->updateProcessor(provider: $provider, renderer: $renderer),
        );

        $response = $action->__invoke(
            'orders.max_items',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '250']]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(250, $provider->setCalls['orders.max_items']);
    }
}
