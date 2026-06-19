<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\List;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Yii\List\Action as YiiListAction;

#[CoversClass(YiiListAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokesResponderAndRendersList(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiListAction(
            responder: $this->listResponder($renderer),
        );

        $response = $action->__invoke();

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('list', $renderer->view);
    }
}
