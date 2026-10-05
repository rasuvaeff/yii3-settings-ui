<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\List;

use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Yii\List\Action as YiiListAction;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

use function Rasuvaeff\Understudy\verify;

#[Test]
#[Covers(YiiListAction::class)]
final class ActionTest extends ActionTestCase
{
    public function invokesResponderAndRendersList(): void
    {
        $renderer = $this->renderer();
        $action = new YiiListAction(
            responder: $this->listResponder($renderer),
        );

        $response = $action->__invoke();

        Assert::same($response->getStatusCode(), Status::OK);

        verify(fn() => $renderer->render('list'));
    }
}
