<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(EditPageRenderer::class)]
#[Covers(EditSettingResponder::class)]
final class EditSettingResponderTest extends ActionTestCase
{
    public function returns404ForUnknownKey(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer)->respond('does.not.exist');

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }

    public function rendersEditForm(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $response = $this->editResponder($renderer)->respond('mail.from');

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($renderer->view, 'edit');
        Assert::same($renderer->parameters['key'], 'mail.from');
        Assert::null($renderer->parameters['error']);
    }

    public function secretFormCarriesNoValue(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);

        $this->editResponder($renderer)->respond('billing.stripe_key');

        /** @var SettingForm $form */
        $form = $renderer->parameters['form'];
        Assert::null($form->value);
    }
}
