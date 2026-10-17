<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\Edit;

use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Yii\Edit\Action as YiiEditAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

use function Rasuvaeff\Understudy\verify;

#[Test]
#[Covers(YiiEditAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesResponderWithoutRequestArgument(): void
    {
        $renderer = $this->renderer();
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer),
            currentRoute: $this->currentRoute(['key' => 'mail.from']),
        );

        $response = $action->__invoke();

        Assert::same($response->getStatusCode(), Status::OK);

        verify(fn() => $renderer->render('edit'));
        Assert::same($this->renderedParameters()['key'], 'mail.from');
    }

    public function missingRouteArgumentDegradesToNotFound(): void
    {
        $action = new YiiEditAction(
            responder: $this->editResponder($this->renderer()),
            currentRoute: $this->currentRoute([]),
        );

        $response = $action->__invoke();

        Assert::same($response->getStatusCode(), Status::NOT_FOUND);
    }
}
