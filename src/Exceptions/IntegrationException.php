<?php

/**
 * Integration Exception.
 *
 * Represents failures when communicating with external systems (e.g., SOAP or SFTP).
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Exceptions
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Exceptions;

use RuntimeException;
use Throwable;

class IntegrationException extends RuntimeException
{
    /**
     * Creates an integration exception.
     *
     * @param  string         $message  Exception message.
     * @param  Throwable|null $previous Previous exception for chaining.
     */
    public function __construct(string $message = 'Integration failure', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
