<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;

#[CoversClass(EditPageRenderer::class)]
#[CoversClass(EditSettingResponder::class)]
final class EditSettingResponderTest extends ActionTestCase
{
    #[Test]
    public function returns404ForUnknownKey(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer)->respond('does.not.exist');

        $this->assertSame(Status::NOT_FOUND, $response->getStatusCode());
    }

    #[Test]
    public function rendersEditForm(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer)->respond('mail.from');

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('edit', $renderer->view);
        $this->assertSame('mail.from', $renderer->parameters['key']);
        $this->assertNull($renderer->parameters['error']);
    }

    #[Test]
    public function secretFormCarriesNoValue(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->editResponder($renderer)->respond('billing.stripe_key');

        /** @var SettingForm $form */
        $form = $renderer->parameters['form'];
        $this->assertNull($form->value);
    }
}
