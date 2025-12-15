<?php

/**
 * Process NPC Message Action.
 *
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Classes\Soap
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Classes\Soap;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ometra\HelaAlize\Classes\Support\AttachmentGuard;
use Ometra\HelaAlize\Classes\Support\SoapAuthGuard;
use Ometra\HelaAlize\Classes\Support\SoapParser;
use Ometra\HelaAlize\Enums\MessageType;
use Ometra\HelaAlize\Events\InboundMessageReceived;
use Ometra\HelaAlize\Models\NpcMessage;

class ProcessNpcMsgAction
{
    /**
     * Executes the SOAP message processing workflow.
     *
     * @param Request $request The HTTP request containing SOAP XML
     * @return Response Text/XML response with success acknowledgment
     */
    public function execute(Request $request): Response
    {
        // Extract raw SOAP content
        $rawContent = $request->getContent();

        // Parse SOAP message
        $parser = new SoapParser();
        $parsedMessage = $parser->parse($rawContent);

        // Validate SOAP credentials
        $authGuard = new SoapAuthGuard();
        $authGuard->validate(
            userId: $parsedMessage->userId,
            passwordBase64: $parsedMessage->passwordBase64
        );

        // Validate attachments if present
        if (!empty($parsedMessage->attachments)) {
            $attachmentGuard = new AttachmentGuard();
            $attachmentGuard->validate($parsedMessage->attachments);
        }

        // Store message in database (Using NpcMessage model)
        // Mapping parsedMessage fields to NpcMessage attributes
        $npcMessage = NpcMessage::create([
            'port_id' => $parsedMessage->portabilityId,
            'message_id' => $parsedMessage->messageId,
            'direction' => 'IN',
            'type_code' => MessageType::tryFrom((int)$parsedMessage->typeCode) ?? $parsedMessage->typeCode,
            // 'sender' => infer from context or parsed? parsedMessage usually has sender
            'raw_xml' => $rawContent,
            'received_at' => Carbon::now(),
            'ack_status' => 'SUCCESS',
            'ack_text' => 'éxito',
            'ack_at' => Carbon::now(),
        ]);

        // Dispatch generic inbound event
        InboundMessageReceived::dispatch($npcMessage);

        // Dispatch specific business events based on type if needed
        // For now, relies on listeners to InboundMessageReceived or we can add switch here.
        // The plan implies "Dispatch appropriate events".
        // Start simple with generic, listeners can filter.

        // Return success response
        return response(
            content: 'éxito',
            status: 200,
            headers: ['Content-Type' => 'text/xml']
        );
    }
}
