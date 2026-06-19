<?php

declare(strict_types=1);

use Rasuvaeff\Yii3Settings\SettingDefinition;
use Rasuvaeff\Yii3Settings\SettingState;
use Rasuvaeff\Yii3SettingsUi\Form\SettingForm;
use Yiisoft\Html\Html;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var SettingForm $form
 * @var string $key
 * @var SettingDefinition $definition
 * @var SettingState $state
 * @var string $label
 * @var string|null $help
 * @var list<string>|null $choices
 * @var bool $readonly
 * @var string $updateUrl
 * @var string $resetUrl
 * @var string $listUrl
 * @var string|null $error
 * @var Csrf|null $csrf
 */

$this->setTitle('Edit: ' . $label);

$type = $definition->type->value;
$isSecret = $definition->isSecret();

/** @var mixed $fieldValue */
$fieldValue = $error !== null ? $form->value : $state->effectiveValue;
$inputAttrs = $readonly ? ['class' => 'form-control', 'disabled' => true] : ['class' => 'form-control'];
?>
<div class="container-fluid">
    <ul class="list-unstyled mb-4">
        <li>Key: <code><?= Html::encode($key) ?></code></li>
        <li>
            Type: <?= Html::encode($type) ?>
            <?php if ($isSecret): ?> <span class="badge text-bg-warning">secret</span><?php endif ?>
        </li>
        <li>Source: <?= Html::encode($state->source) ?></li>
    </ul>

    <?php if ($error !== null): ?>
        <div class="alert alert-danger"><?= Html::encode($error) ?></div>
    <?php endif ?>

    <form method="post" action="<?= Html::encode($updateUrl) ?>">
        <?php if (($csrf ?? null) !== null): ?>
            <?= $csrf->hiddenInput() ?>
        <?php endif ?>

        <?php if ($isSecret): ?>
            <div class="mb-3">
                <label class="form-label">New value</label>
                <?= Html::passwordInput('Setting[value]', '', array_merge(['autocomplete' => 'off'], $inputAttrs)) ?>
                <div class="form-text text-muted">Leave blank to keep the current value.</div>
            </div>
        <?php elseif ($type === 'bool'): ?>
            <div class="form-check mb-3">
                <?= Html::checkbox(
                    'Setting[value]',
                    '1',
                    $readonly
                        ? ['class' => 'form-check-input', 'checked' => (bool) $fieldValue, 'disabled' => true]
                        : ['class' => 'form-check-input', 'checked' => (bool) $fieldValue],
                ) ?>
                <label class="form-check-label">Enabled</label>
            </div>
        <?php elseif ($type === 'array'): ?>
            <div class="mb-3">
                <label class="form-label">Value (JSON)</label>
                <?php
                $json = is_string($fieldValue) ? $fieldValue : json_encode($fieldValue ?? [], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                ?>
                <?= Html::textarea('Setting[value]', $json, array_merge(['rows' => 6], $inputAttrs)) ?>
            </div>
        <?php elseif ($choices !== null): ?>
            <div class="mb-3">
                <label class="form-label">Value</label>
                <?= Html::select('Setting[value]', (string) $fieldValue)
                    ->optionsData($choices)
                    ->attributes($readonly ? ['class' => 'form-select', 'disabled' => true] : ['class' => 'form-select']) ?>
            </div>
        <?php else: ?>
            <div class="mb-3">
                <label class="form-label">Value</label>
                <?= Html::textInput('Setting[value]', (string) $fieldValue, $inputAttrs) ?>
            </div>
        <?php endif ?>

        <?php if ($help !== null): ?>
            <div class="form-text text-muted mb-3"><?= Html::encode($help) ?></div>
        <?php endif ?>

        <div class="d-flex gap-2 align-items-center">
            <?php if (!$readonly): ?>
                <button type="submit" class="btn btn-primary">Save</button>
                <?php if ($state->hasStoredOverride): ?>
                    <button type="submit"
                        class="btn btn-outline-secondary"
                        formaction="<?= Html::encode($resetUrl) ?>"
                        onclick="return confirm('Reset to default?')">Reset to default</button>
                <?php endif ?>
            <?php else: ?>
                <span class="text-muted">Read-only</span>
            <?php endif ?>
            <a href="<?= Html::encode($listUrl) ?>" class="btn btn-link">Cancel</a>
        </div>
    </form>
</div>
