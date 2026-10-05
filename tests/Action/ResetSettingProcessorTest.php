<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Psr\EventDispatcher\EventDispatcherInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Captor;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\ResetSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\User\CurrentUser;

use function Rasuvaeff\Understudy\when;

#[Test]
#[Covers(ResetSettingProcessor::class)]
final class ResetSettingProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private EventDispatcherInterface $events;

    private Captor $dispatchedEvents;

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider();
        $this->dispatchedEvents = Arg::captor(SettingChanged::class);
        $this->events = Understudy::for(EventDispatcherInterface::class);
        when(fn() => $this->events->dispatch($this->dispatchedEvents->capture()));
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

        Assert::count($this->dispatchedEvents->all(), 1);
        $event = $this->dispatchedEvents->last();
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
