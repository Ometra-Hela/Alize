<?php

/**
 * Invalid SOAP credentials exception.
 *
 * Raised when an inbound SOAP message contains credentials that do not match the configured NUMLEX values.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Exceptions
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Exceptions;

use RuntimeException;

class InvalidSoapCredentialsException extends RuntimeException
{
}
