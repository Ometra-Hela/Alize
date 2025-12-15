<?php

/**
 * SOAP authentication guard for NUMLEX credentials validation.
 *
 * Validates userId and passwordBase64 against configuration values.
 * Throws InvalidSoapCredentialsException on authentication failure.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\\Classes\Support
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Classes\Support;

use Ometra\HelaAlize\Exceptions\InvalidSoapCredentialsException;

class SoapAuthGuard
{
    /**
     * Validates SOAP credentials against configuration.
     *
     * @param string $userId         User ID from SOAP message
     * @param string $passwordBase64 Password (base64) from SOAP message
     * @return void
     * @throws InvalidSoapCredentialsException When credentials don't match
     */
    public function validate(
        string $userId,
        string $passwordBase64
    ): void {
        /** @var array{user_id?: string, password_b64?: string} $config */
        $config = (array) config('alize.soap', []);

        $expectedUserId = $config['user_id'] ?? '';
        $expectedPassword = $config['password_b64'] ?? '';

        if ($userId !== $expectedUserId) {
            throw new InvalidSoapCredentialsException('Invalid user ID');
        }

        if ($passwordBase64 !== $expectedPassword) {
            throw new InvalidSoapCredentialsException('Invalid password');
        }
    }
}
