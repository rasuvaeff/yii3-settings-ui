<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Psr\EventDispatcher\EventDispatcherInterface;
use Rasuvaeff\Understudy\Arg;
use Rasuvaeff\Understudy\Captor;
use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\TemplateRendererInterface;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\User\CurrentUser;

use function Rasuvaeff\Understudy\verify;
use function Rasuvaeff\Understudy\when;

#[Test]
#[Covers(UpdateSettingProcessor::class)]
#[Covers(Status::class)]
final class UpdateSettingProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private EventDispatcherInterface $events;

    private Captor $dispatchedEvents;

    private TemplateRendererInterface $renderer;

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider();
        $this->renderer = $this->renderer();
        $this->dispatchedEvents = Arg::captor(SettingChanged::class);
        $this->events = Understudy::for(EventDispatcherInterface::class);
        when(fn() => $this->events->dispatch($this->dispatchedEvents->capture()));
    }

    public function returns404ForUnknownKey(): void
    {
        $response = $this->processor()->process(
            'nope',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'x']]),
        );

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
        Assert::same($response->getStatusCode(), 404);
    }

    public function readonlySettingRejectsWrite(): void
    {
        $response = $this->processor()->process(
            'app.locked',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'hacked']]),
        );

        Assert::same($response->getStatusCode(), Status::FORBIDDEN);
        Assert::same($response->getStatusCode(), 403);
        Assert::same($this->provider->setCalls, []);
    }

    public function blankSecretKeepsCurrentValue(): void
    {
        $response = $this->processor()->process(
            'billing.stripe_key',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '']]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($response->getStatusCode(), 302);
        Assert::array($this->provider->setCalls)->doesNotHaveKeys('billing.stripe_key');

        Understudy::unused($this->events);
    }

    public function nonBlankSecretIsStored(): void
    {
        $response = $this->processor()->process(
            'billing.stripe_key',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'sk_new']]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->setCalls['billing.stripe_key'], 'sk_new');
    }

    public function secretValueIsNotCarriedInEvent(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process(
            'billing.stripe_key',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'sk_new']]),
        );

        Assert::count($this->dispatchedEvents->all(), 1);
        $event = $this->dispatchedEvents->last();
        Assert::same($event->key, 'billing.stripe_key');
        Assert::true($event->isSecret);
        Assert::null($event->value);
        Assert::same($event->actor, 'user-1');
    }

    public function validIntegerIsStored(): void
    {
        $response = $this->processor()->process(
            'orders.max_items',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '250']]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->setCalls['orders.max_items'], 250);
    }

    public function invalidIntegerReRendersWithError(): void
    {
        $response = $this->processor()->process(
            'orders.max_items',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'abc']]),
        );

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($response->getStatusCode(), 200);

        verify(fn() => $this->renderer->render('edit'));
        Assert::notNull($this->renderedParameters()['error']);
        Assert::same($this->provider->setCalls, []);
    }

    public function validJsonArrayIsDecodedAndStored(): void
    {
        $this->processor()->process(
            'app.features',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '{"search":true,"beta":false}']]),
        );

        Assert::same($this->provider->setCalls['app.features'], ['search' => true, 'beta' => false]);
    }

    public function invalidJsonArrayReRendersWithError(): void
    {
        $response = $this->processor()->process(
            'app.features',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'not json']]),
        );

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($this->provider->setCalls, []);
    }

    public function checkedBooleanIsStoredTrue(): void
    {
        $this->processor()->process(
            'mail.enabled',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '1']]),
        );

        Assert::true($this->provider->setCalls['mail.enabled']);
    }

    public function uncheckedBooleanIsStoredFalse(): void
    {
        $this->processor()->process(
            'mail.enabled',
            $this->request('POST', parsedBody: []),
        );

        Assert::array($this->provider->setCalls)->hasKeys('mail.enabled');
        Assert::false($this->provider->setCalls['mail.enabled']);
    }

    public function nonSecretEventCarriesValue(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process(
            'mail.from',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'new@example.com']]),
        );

        $event = $this->dispatchedEvents->last();
        Assert::false($event->isSecret);
        Assert::same($event->value, 'new@example.com');
        Assert::same($event->operation, SettingChanged::OPERATION_UPDATED);
        Assert::same($event->actor, 'user-1');
    }

    public function toleratesNullDispatcherAndCurrentUser(): void
    {
        $processor = new UpdateSettingProcessor(
            settingsProvider: $this->provider,
            responseFactory: $this->http,
            validator: new SettingValueValidator(),
            editPage: $this->editPage($this->renderer),
            urls: $this->urls(),
            definitions: $this->definitions(),
        );

        $response = $processor->process(
            'orders.max_items',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '250']]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::same($this->provider->setCalls['orders.max_items'], 250);
    }

    private function processor(?CurrentUser $currentUser = null): UpdateSettingProcessor
    {
        return $this->updateProcessor(
            provider: $this->provider,
            renderer: $this->renderer,
            currentUser: $currentUser,
            eventDispatcher: $this->events,
        );
    }
}
