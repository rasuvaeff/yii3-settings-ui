<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsGridFactory;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;

#[CoversClass(SettingsGridFactory::class)]
final class SettingsGridFactoryTest extends TestCase
{
    private SettingsGridFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new SettingsGridFactory(new TestContainer());
    }

    #[Test]
    public function rendersTableWithBootstrapClassesAndColumns(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello', label: 'Max items')]);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('table-striped', $html);
        $this->assertStringContainsString('table-hover', $html);
        $this->assertStringContainsString('Group', $html);
        $this->assertStringContainsString('Key', $html);
        $this->assertStringContainsString('Value', $html);
        $this->assertStringContainsString('Type', $html);
        $this->assertStringContainsString('Source', $html);
    }

    #[Test]
    public function wrapsLabelInStrongAndRendersHelpWhenPresent(): void
    {
        $html = $this->factory->render([$this->presenter(value: '250', label: 'Max items', help: 'Cart limit')]);

        $this->assertStringContainsString('<strong>Max items</strong>', $html);
        $this->assertStringContainsString('<br>', $html);
        $this->assertStringContainsString('<small class="text-muted">Cart limit</small>', $html);
    }

    #[Test]
    public function omitsHelpBlockWhenHelpAbsent(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello', label: 'Title')]);

        $this->assertStringContainsString('<strong>Title</strong>', $html);
        $this->assertStringNotContainsString('<small class="text-muted">', $html);
    }

    #[Test]
    public function rendersTypeAndSourceAsBadges(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello')]);

        $this->assertStringContainsString('<span class="badge text-bg-secondary">string</span>', $html);
        $this->assertStringContainsString('<span class="badge text-bg-secondary">db</span>', $html);
    }

    #[Test]
    public function masksSecretAndNeverLeaksPlaintext(): void
    {
        $def = new SettingDefinition(key: 'billing.stripe_key', type: SettingType::String, secret: true);
        $state = new SettingState(
            key: 'billing.stripe_key',
            effectiveValue: 'sk_live_LEAKED',
            hasStoredOverride: true,
            source: 'db',
            isSecret: true,
            isWritable: true,
        );

        $html = $this->factory->render([new SettingPresenter($def, $state, '/edit')]);

        $this->assertStringNotContainsString('sk_live_LEAKED', $html);
        $this->assertStringContainsString('(set)', $html);
        $this->assertStringContainsString('text-muted', $html);
    }

    #[Test]
    public function doesNotMuteNonSecretValueCell(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello')]);

        $this->assertStringContainsString('>Hello<', $html);
    }

    #[Test]
    public function escapesUntrustedValues(): void
    {
        $html = $this->factory->render([$this->presenter(value: '<script>alert(1)</script>')]);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    #[Test]
    public function rendersEditLinkForWritableNonReadonlySetting(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello', editUrl: '/admin/settings/app.title/edit')]);

        $this->assertStringContainsString('href="/admin/settings/app.title/edit"', $html);
        $this->assertStringContainsString('btn-outline-primary', $html);
    }

    #[Test]
    public function omitsEditLinkForReadonlySetting(): void
    {
        $def = new SettingDefinition(key: 'app.locked', type: SettingType::String, default: 'fixed', readonly: true);
        $state = new SettingState(
            key: 'app.locked',
            effectiveValue: 'fixed',
            hasStoredOverride: false,
            source: 'default',
            isSecret: false,
            isWritable: true,
        );

        $html = $this->factory->render([new SettingPresenter($def, $state, '/edit')]);

        $this->assertStringNotContainsString('btn-outline-primary', $html);
    }

    #[Test]
    public function omitsEditLinkForNonWritableSetting(): void
    {
        $def = new SettingDefinition(key: 'app.title', type: SettingType::String);
        $state = new SettingState(
            key: 'app.title',
            effectiveValue: 'Hello',
            hasStoredOverride: false,
            source: 'default',
            isSecret: false,
            isWritable: false,
        );

        $html = $this->factory->render([new SettingPresenter($def, $state, '/edit')]);

        $this->assertStringNotContainsString('btn-outline-primary', $html);
    }

    private function presenter(
        mixed $value,
        string $label = 'Default label',
        ?string $help = null,
        string $editUrl = '/edit',
    ): SettingPresenter {
        $def = new SettingDefinition(key: 'app.title', type: SettingType::String, label: $label, help: $help);
        $state = new SettingState(
            key: 'app.title',
            effectiveValue: $value,
            hasStoredOverride: true,
            source: 'db',
            isSecret: false,
            isWritable: true,
        );

        return new SettingPresenter($def, $state, $editUrl);
    }
}
