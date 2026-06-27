<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Service;

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Service\SettingsGridFactory;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\TestContainer;
use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Test]
#[Covers(SettingsGridFactory::class)]
final class SettingsGridFactoryTest
{
    private SettingsGridFactory $factory;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->factory = new SettingsGridFactory(new TestContainer());
    }

    public function rendersTableWithBootstrapClassesAndColumns(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello', label: 'Max items')]);

        Assert::string($html)->contains('<table');
        Assert::string($html)->contains('table-striped');
        Assert::string($html)->contains('table-hover');
        Assert::string($html)->contains('Group');
        Assert::string($html)->contains('Key');
        Assert::string($html)->contains('Value');
        Assert::string($html)->contains('Type');
        Assert::string($html)->contains('Source');
    }

    public function wrapsLabelInStrongAndRendersHelpWhenPresent(): void
    {
        $html = $this->factory->render([$this->presenter(value: '250', label: 'Max items', help: 'Cart limit')]);

        Assert::string($html)->contains('<strong>Max items</strong>');
        Assert::string($html)->contains('<br>');
        Assert::string($html)->contains('<small class="text-muted">Cart limit</small>');
    }

    public function omitsHelpBlockWhenHelpAbsent(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello', label: 'Title')]);

        Assert::string($html)->contains('<strong>Title</strong>');
        Assert::string($html)->notContains('<small class="text-muted">');
    }

    public function rendersTypeAndSourceAsBadges(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello')]);

        Assert::string($html)->contains('<span class="badge text-bg-secondary">string</span>');
        Assert::string($html)->contains('<span class="badge text-bg-secondary">db</span>');
    }

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

        Assert::string($html)->notContains('sk_live_LEAKED');
        Assert::string($html)->contains('(set)');
        Assert::string($html)->contains('text-muted');
    }

    public function doesNotMuteNonSecretValueCell(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello')]);

        Assert::string($html)->contains('>Hello<');
    }

    public function escapesUntrustedValues(): void
    {
        $html = $this->factory->render([$this->presenter(value: '<script>alert(1)</script>')]);

        Assert::string($html)->notContains('<script>alert(1)</script>');
        Assert::string($html)->contains('&lt;script&gt;');
    }

    public function rendersEditLinkForWritableNonReadonlySetting(): void
    {
        $html = $this->factory->render([$this->presenter(value: 'Hello', editUrl: '/admin/settings/app.title/edit')]);

        Assert::string($html)->contains('href="/admin/settings/app.title/edit"');
        Assert::string($html)->contains('btn-outline-primary');
    }

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

        Assert::string($html)->notContains('btn-outline-primary');
    }

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

        Assert::string($html)->notContains('btn-outline-primary');
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
