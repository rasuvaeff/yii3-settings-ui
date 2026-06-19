<?php

declare(strict_types=1);

namespace Rasuvaeff\Yii3SettingsUi\Exception;

use InvalidArgumentException;

/**
 * Thrown when submitted input does not satisfy the setting's declared type.
 *
 * Messages describe the type problem only and never echo the submitted value,
 * so secret input cannot leak into responses or logs.
 *
 * @api
 */
final class InvalidSettingValueException extends InvalidArgumentException {}
