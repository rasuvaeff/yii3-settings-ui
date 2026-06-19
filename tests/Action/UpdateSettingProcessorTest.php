<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Event\SettingChanged;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Service\UpdateSettingProcessor;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingEventDispatcher;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\RecordingWritableProvider;
use Rasuvaeff\Yii3SettingsUi\Validation\SettingValueValidator;
use Yiisoft\User\CurrentUser;

#[CoversClass(UpdateSettingProcessor::class)]
final class UpdateSettingProcessorTest extends ActionTestCase
{
    private RecordingWritableProvider $provider;

    private RecordingEventDispatcher $events;

    private FakeTemplateRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new RecordingWritableProvider();
        $this->events = new RecordingEventDispatcher();
        $this->renderer = new FakeTemplateRenderer($this->http);
    }

    #[Test]
    public function returns404ForUnknownKey(): void
    {
        $response = $this->processor()->process(
            'nope',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'x']]),
        );

        $this->assertSame(Status::NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function readonlySettingRejectsWrite(): void
    {
        $response = $this->processor()->process(
            'app.locked',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'hacked']]),
        );

        $this->assertSame(Status::FORBIDDEN, $response->getStatusCode());
        $this->assertSame([], $this->provider->setCalls);
    }

    #[Test]
    public function blankSecretKeepsCurrentValue(): void
    {
        $response = $this->processor()->process(
            'billing.stripe_key',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '']]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertArrayNotHasKey('billing.stripe_key', $this->provider->setCalls);
        $this->assertSame([], $this->events->events);
    }

    #[Test]
    public function nonBlankSecretIsStored(): void
    {
        $response = $this->processor()->process(
            'billing.stripe_key',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'sk_new']]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame('sk_new', $this->provider->setCalls['billing.stripe_key']);
    }

    #[Test]
    public function secretValueIsNotCarriedInEvent(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process(
            'billing.stripe_key',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'sk_new']]),
        );

        $this->assertCount(1, $this->events->events);
        $event = $this->events->events[0] ?? null;
        $this->assertInstanceOf(SettingChanged::class, $event);
        $this->assertSame('billing.stripe_key', $event->key);
        $this->assertTrue($event->isSecret);
        $this->assertNull($event->value);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
    public function validIntegerIsStored(): void
    {
        $response = $this->processor()->process(
            'orders.max_items',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '250']]),
        );

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(250, $this->provider->setCalls['orders.max_items']);
    }

    #[Test]
    public function invalidIntegerReRendersWithError(): void
    {
        $response = $this->processor()->process(
            'orders.max_items',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'abc']]),
        );

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('edit', $this->renderer->view);
        $this->assertNotNull($this->renderer->parameters['error']);
        $this->assertSame([], $this->provider->setCalls);
    }

    #[Test]
    public function validJsonArrayIsDecodedAndStored(): void
    {
        $this->processor()->process(
            'app.features',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '{"search":true,"beta":false}']]),
        );

        $this->assertSame(['search' => true, 'beta' => false], $this->provider->setCalls['app.features']);
    }

    #[Test]
    public function invalidJsonArrayReRendersWithError(): void
    {
        $response = $this->processor()->process(
            'app.features',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'not json']]),
        );

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame([], $this->provider->setCalls);
    }

    #[Test]
    public function checkedBooleanIsStoredTrue(): void
    {
        $this->processor()->process(
            'mail.enabled',
            $this->request('POST', parsedBody: ['Setting' => ['value' => '1']]),
        );

        $this->assertTrue($this->provider->setCalls['mail.enabled']);
    }

    #[Test]
    public function uncheckedBooleanIsStoredFalse(): void
    {
        $this->processor()->process(
            'mail.enabled',
            $this->request('POST', parsedBody: []),
        );

        $this->assertArrayHasKey('mail.enabled', $this->provider->setCalls);
        $this->assertFalse($this->provider->setCalls['mail.enabled']);
    }

    #[Test]
    public function nonSecretEventCarriesValue(): void
    {
        $this->processor(currentUser: $this->currentUser('user-1'))->process(
            'mail.from',
            $this->request('POST', parsedBody: ['Setting' => ['value' => 'new@example.com']]),
        );

        $event = $this->events->events[0] ?? null;
        $this->assertInstanceOf(SettingChanged::class, $event);
        $this->assertFalse($event->isSecret);
        $this->assertSame('new@example.com', $event->value);
        $this->assertSame(SettingChanged::OPERATION_UPDATED, $event->operation);
        $this->assertSame('user-1', $event->actor);
    }

    #[Test]
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

        $this->assertSame(Status::FOUND, $response->getStatusCode());
        $this->assertSame(250, $this->provider->setCalls['orders.max_items']);
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
