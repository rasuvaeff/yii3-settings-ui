<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\User\CurrentUser;

#[Test]
#[Covers(UpdateSettingProcessor::class)]
final class UpdateSettingProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    private FakeTemplateRenderer $renderer;

    #[BeforeTest]
    public function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider();
        $this->events = new RecordingEventDispatcher();
        $this->renderer = new FakeTemplateRenderer($this->http);
    }

    public function returns404ForUnknownKey(): void
    {
        $response = $this->processor()->process(
            'nope',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'x']]),
        );

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

    public function readonlySettingRejectsWrite(): void
    {
        $response = $this->processor()->process(
            'app.locked',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'hacked']]),
        );

        Assert::same($response->getStatusCode(), Status::FORBIDDEN);
        Assert::same($this->provider->setCalls, []);
    }

    public function blankSecretKeepsCurrentValue(): void
    {
        $response = $this->processor()->process(
            'billing.stripe_key',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '']]),
        );

        Assert::same($response->getStatusCode(), Status::FOUND);
        Assert::array($this->provider->setCalls)->doesNotHaveKeys('billing.stripe_key');
        Assert::same($this->events->events, []);
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

        Assert::count($this->events->events, 1);
        $event = $this->events->events[0] ?? null;
        Assert::instanceOf($event, SettingChanged::class);
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
        Assert::same($this->renderer->view, 'edit');
        Assert::notNull($this->renderer->parameters['error']);
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

        $event = $this->events->events[0] ?? null;
        Assert::instanceOf($event, SettingChanged::class);
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
