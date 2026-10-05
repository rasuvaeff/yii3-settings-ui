<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Action;

use Rasuvaeff\Understudy\Understudy;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Renderer\EditPageRenderer;
use Rasuvaeff\Yii3SettingsUi\Service\EditSettingResponder;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

use function Rasuvaeff\Understudy\verify;

#[Test]
#[Covers(EditPageRenderer::class)]
#[Covers(EditSettingResponder::class)]
final class EditSettingResponderTest extends ActionTestCase
{
    public function returns404ForUnknownKey(): void
    {
        $renderer = $this->renderer();

        $response = $this->editResponder($renderer)->respond('does.not.exist');

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);

        Understudy::unused($renderer);
    }

    public function rendersEditForm(): void
    {
        $renderer = $this->renderer();

        $response = $this->editResponder($renderer)->respond('mail.from');

        Assert::same($response->getStatusCode(), Status::OK);

        verify(fn() => $renderer->render('edit'));
        $parameters = $this->renderedParameters();
        Assert::same($parameters['key'], 'mail.from');
        Assert::null($parameters['error']);
    }

    public function secretFormCarriesNoValue(): void
    {
        $renderer = $this->renderer();

        $this->editResponder($renderer)->respond('billing.stripe_key');

        /** @var SettingForm $form */
        $form = $this->renderedParameters()['form'];
        Assert::null($form->value);
    }
}
