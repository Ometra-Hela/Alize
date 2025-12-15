<?php

/**
 * Process NPC Message Request validation.
 *
 * Validates incoming SOAP requests for security and data integrity.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Http\Requests
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Http\Requests;

use Equidna\Toolkit\Http\Requests\EquidnaFormRequest;

class ProcessNpcMsgRequest extends EquidnaFormRequest
{
    /**
     * Prepares data for validation.
     *
     * Extracts raw SOAP content for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $rawContent = $this->getContent();

        // Basic XML validation
        if (empty($rawContent)) {
            abort(400, 'Empty SOAP request');
        }

        // Check if content appears to be XML
        if (strpos($rawContent, '<?xml') === false && strpos($rawContent, '<soap:') === false) {
            abort(400, 'Invalid SOAP format');
        }

        // Check content length (5MB max)
        if (strlen($rawContent) > 5 * 1024 * 1024) {
            abort(413, 'SOAP message too large');
        }
    }
}
