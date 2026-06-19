<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Yiisoft\User\CurrentUser;

#[CoversClass(ResetSettingProcessor::class)]
final class ResetSettingProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider();
        $this->events = new RecordingEventDispatcher();
    }

    #[Test]
    public function returns404ForUnknownKey(): void
    {
        $response = $this->processor()->process('nope');

        $this->assertSame(Status::NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function readonlySettingRejectsReset(): void
    {
        $response = $this->processor()->process('app.locked');

        $this->assertSame(Status::FORBIDDEN, $response->getStatusCode());
        $this->assertSame([], $this->provider->removeCalls);
    }

    #[Test]
    public function removesOverrideAndRedirects(): void
    {
        $response = $this->processor()->process('mail.from');

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame('/admin/settings', $response->getHeaderLine('Location'));
        $this->assertSame(['mail.from'], $this->provider->removeCalls);
    }

    #[Test]
    public function dispatchesResetEvent(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process('billing.stripe_key');

        $this->assertCount(1, $this->events->events);
        $event = $this->events->events[0] ?? null;
        $this->assertInstanceOf(SettingChanged::class, $event);
        $this->assertSame(SettingChanged::OPERATION_RESET, $event->operation);
        $this->assertTrue($event->isSecret);
        $this->assertNull($event->value);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function toleratesNullDispatcherAndCurrentUser(): void
    {
        $processor = new ResetSettingProcessor(
            settingsProvider: $this->provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            definitions: $this->definitions(),
        );

        $response = $processor->process('mail.from');

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(['mail.from'], $this->provider->removeCalls);
    }

    private function processor(?CurrentUser $currentUser = null): ResetSettingProcessor
    {
        return $this->resetProcessor(
            provider: $this->provider,
            currentUser: $currentUser,
            eventDispatcher: $this->events,
        );
    }
}
