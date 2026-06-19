<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Tests\Yii\Edit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Rasuvaeff\Yii3SettingsUi\Http\Status;
use Rasuvaeff\Yii3SettingsUi\Tests\Action\ActionTestCase;
use Rasuvaeff\Yii3SettingsUi\Tests\Double\FakeTemplateRenderer;
use Rasuvaeff\Yii3SettingsUi\Yii\Edit\Action as YiiEditAction;

#[CoversClass(YiiEditAction::class)]
final class ActionTest extends ActionTestCase
{
    #[Test]
    public function invokesResponderWithoutRequestArgument(): void
    {
        $renderer = new FakeTemplateRenderer($this->http);
        $action = new YiiEditAction(
            responder: $this->editResponder($renderer),
        );

        $response = $action->__invoke('mail.from');

        $this->assertSame(Status::OK, $response->getStatusCode());
        $this->assertSame('edit', $renderer->view);
        $this->assertSame('mail.from', $renderer->parameters['key']);
    }
}
