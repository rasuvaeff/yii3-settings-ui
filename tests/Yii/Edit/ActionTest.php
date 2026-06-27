<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\Edit;

use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Yii\Edit\Action as YiiEditAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(YiiEditAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesResponderWithoutRequestArgument(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer),
        );

        $response = $action->__invoke('mail.from');

        Assert::same($response->getStatusCode(), Status::OK);
        Assert::same($renderer->view, 'edit');
        Assert::same($renderer->parameters['key'], 'mail.from');
    }
}
