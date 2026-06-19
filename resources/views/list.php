<?php

declare(strict_types=1);

use Rasuvaeff\Yii3SettingsUi\View\SettingPresenter;

/**
 * @var \Yiisoft\View\WebView $this
 * @var list<SettingPresenter> $settings
 * @var string $gridHtml
 */

$this->setTitle('Settings');
?>
<div class="container-fluid">
    <?= $gridHtml ?>
</div>
