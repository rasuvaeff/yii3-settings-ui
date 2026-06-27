<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\User\CurrentUser;

#[Test]
#[Covers(ResetSettingProcessor::class)]
final class ResetSettingProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider();
        $this->events = new RecordingEventDispatcher();
    }

    public function returns404ForUnknownKey(): void
    {
        $response = $this->processor()->process('nope');

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

    public function readonlySettingRejectsReset(): void
    {
        $response = $this->processor()->process('app.locked');

        Assert::same($response->getStatusCode(), Status::FORBIDDEN);
        Assert::same($this->provider->removeCalls, []);
    }

    public function removesOverrideAndRedirects(): void
    {
        $response = $this->processor()->process('mail.from');

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($response->getHeaderLine('Location'), '/admin/settings');
        Assert::same($this->provider->removeCalls, ['mail.from']);
    }

    public function dispatchesResetEvent(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process('billing.stripe_key');

        Assert::count($this->events->events, 1);
        $event = $this->events->events[0] ?? null;
        Assert::instanceOf($event, SettingChanged::class);
        Assert::same($event->operation, SettingChanged::OPERATION_RESET);
        Assert::true($event->isSecret);
        Assert::null($event->value);
        Assert::same($event->actor, 'user-1');
    }

    public function toleratesNullDispatcherAndCurrentUser(): void
    {
        $processor = new ResetSettingProcessor(
            settingsProvider: $this->provider,
            responseFactory: $this->http,
            urls: $this->urls(),
            definitions: $this->definitions(),
        );

        $response = $processor->process('mail.from');

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->removeCalls, ['mail.from']);
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
