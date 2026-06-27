<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\View;

use Nyholm\Psr7\Factory\Psr17Factory;
use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3Settings\SettingType;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Rasuvaeff\Yii3SettingsUi\Renderer\ViewTemplateRenderer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;
use Yiisoft\Aliases\Aliases;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

#[Test]
#[Covers(ViewTemplateRenderer::class)]
final class ViewRenderingTest
{
    private ViewTemplateRenderer $renderer;

    #[BeforeTest]
    public function setUp(): void
    {
        $this->renderer = $this->renderer();
    }

    public function listTemplateRendersProvidedGridHtml(): void
    {
        $html = $this->render('list', ['settings' => [], 'gridHtml' => '<table id="settings-grid"></table>']);

        Assert::string($html)->contains('<table id="settings-grid"></table>');
        Assert::string($html)->contains('Settings');
    }

    public function editSecretRendersEmptyPasswordField(): void
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

        $html = $this->render('edit', $this->editParams('billing.stripe_key', $def, $state));

        Assert::string($html)->notContains('sk_live_LEAKED');
        Assert::string($html)->contains('type="password"');
        Assert::string($html)->contains('Leave blank');
    }

    public function editArrayRendersJsonTextarea(): void
    {
        $def = new SettingDefinition(key: 'app.features', type: SettingType::Array, default: ['search' => true]);
        $state = new SettingState(
            key: 'app.features',
            effectiveValue: ['search' => true],
            hasStoredOverride: false,
            source: 'default',
            isSecret: false,
            isWritable: true,
        );

        $html = $this->render('edit', $this->editParams('app.features', $def, $state));

        Assert::string($html)->contains('<textarea');
        Assert::string($html)->contains('"search"');
        // Structural HTML chars in textarea content must be escaped to prevent tag breakout.
        Assert::string($html)->notContains('</textarea><');
    }

    public function editArrayTextareaEncodesSpecialCharsExactlyOnce(): void
    {
        $def = new SettingDefinition(key: 'app.payload', type: SettingType::Array, default: []);
        $state = new SettingState(
            key: 'app.payload',
            effectiveValue: ['html' => '<b> & </b>'],
            hasStoredOverride: true,
            source: 'db',
            isSecret: false,
            isWritable: true,
        );

        $html = $this->render('edit', $this->editParams('app.payload', $def, $state));

        // `<`/`&` encoded once (so the browser shows the real JSON), not doubled.
        Assert::string($html)->contains('&lt;b&gt;');
        Assert::string($html)->notContains('&amp;lt;');
        Assert::string($html)->notContains('&amp;amp;');
    }

    public function editRendersWhenNoCsrfParameterIsInjected(): void
    {
        $def = new SettingDefinition(key: 'app.title', type: SettingType::String);
        $state = new SettingState(
            key: 'app.title',
            effectiveValue: 'x',
            hasStoredOverride: false,
            source: 'default',
            isSecret: false,
            isWritable: true,
        );

        $params = $this->editParams('app.title', $def, $state);
        unset($params['csrf']);

        $html = $this->render('edit', $params);

        Assert::string($html)->contains('<form');
    }

    public function editShowsValidationError(): void
    {
        $def = new SettingDefinition(key: 'orders.max_items', type: SettingType::Int, default: 100);
        $state = new SettingState(
            key: 'orders.max_items',
            effectiveValue: 100,
            hasStoredOverride: false,
            source: 'default',
            isSecret: false,
            isWritable: true,
        );

        $params = $this->editParams('orders.max_items', $def, $state);
        $params['error'] = 'Setting "orders.max_items" must be an integer';
        $params['form'] = new SettingForm(present: true, value: 'abc');

        $html = $this->render('edit', $params);

        Assert::string($html)->contains('must be an integer');
        Assert::string($html)->contains('value="abc"');
    }

    public function configuredListAndEditViewsOverrideDefaults(): void
    {
        $basePath = sys_get_temp_dir() . '/yii3-settings-ui-' . bin2hex(random_bytes(4));
        $overridesPath = $basePath . '/overrides';
        mkdir($overridesPath, 0o777, true);

        file_put_contents($basePath . '/_layout-standalone.php', '<?php declare(strict_types=1); ?><html><body><?= $content ?></body></html>');
        file_put_contents($overridesPath . '/list.php', '<?php declare(strict_types=1); ?>LIST OVERRIDE');
        file_put_contents($overridesPath . '/edit.php', '<?php declare(strict_types=1); ?>EDIT OVERRIDE');

        $renderer = $this->renderer(
            viewPath: $basePath,
            layout: $basePath . '/_layout-standalone.php',
            views: [
                'list' => 'overrides/list.php',
                'edit' => 'overrides/edit.php',
            ],
        );

        $listHtml = (string) $renderer->render('list', ['settings' => [], 'gridHtml' => ''])->getBody();
        $editHtml = (string) $renderer->render('edit', $this->editParams('app.title', new SettingDefinition(key: 'app.title', type: SettingType::String), new SettingState(key: 'app.title', effectiveValue: 'x', hasStoredOverride: false, source: 'default', isSecret: false, isWritable: true)))->getBody();

        Assert::string($listHtml)->contains('LIST OVERRIDE');
        Assert::string($editHtml)->contains('EDIT OVERRIDE');
    }

    /**
     * @return array<string, mixed>
     */
    private function editParams(string $key, SettingDefinition $def, SettingState $state): array
    {
        return [
            'form' => new SettingForm(present: false),
            'key' => $key,
            'definition' => $def,
            'state' => $state,
            'label' => $key,
            'help' => null,
            'choices' => null,
            'readonly' => false,
            'updateUrl' => '/admin/settings/' . $key,
            'resetUrl' => '/admin/settings/' . $key . '/reset',
            'listUrl' => '/admin/settings',
            'error' => null,
            'csrf' => null,
        ];
    }

    /**
     * @param array<string, string> $views
     */
    private function renderer(?string $viewPath = null, ?string $layout = null, array $views = []): ViewTemplateRenderer
    {
        $psr17 = new Psr17Factory();
        $webRenderer = new WebViewRenderer(
            responseFactory: $psr17,
            streamFactory: $psr17,
            aliases: new Aliases(),
            view: new WebView(),
        );

        return new ViewTemplateRenderer(
            renderer: $webRenderer,
            viewPath: $viewPath,
            layout: $layout,
            views: $views,
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function render(string $view, array $params): string
    {
        return (string) $this->renderer->render($view, $params)->getBody();
    }
}
