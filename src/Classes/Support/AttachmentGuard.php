<?php

/**
 * Attachment Guard.
 *
 * Validates SOAP attachments for security and compliance.
 * Enforces file size limits, MIME type restrictions, and filename sanitization.
 * PHP 8.1+
 *
 * @package Ometra\HelaAlize\Classes\Support
 * @author  HELA Development Team
 * @license MIT
 */

namespace Ometra\HelaAlize\Classes\Support;

use Equidna\Toolkit\Exceptions\BadRequestException;

class AttachmentGuard
{
    /**
     * Validates a list of attachments.
     *
     * @param  array<array{filename: string, content: string, mime_type?: string}> $attachments Attachments to validate
     * @return void
     * @throws BadRequestException When validation fails
     */
    public function validate(array $attachments): void
    {
        if (empty($attachments)) {
            return;
        }

        $maxSize = \config('alize.attachments.max_size_mb', 10) * 1024 * 1024;
        $allowedMimes = \config('alize.attachments.allowed_types', [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/zip',
        ]);
        $maxAttachments = \config('alize.attachments.max_count', 10);

        if (count($attachments) > $maxAttachments) {
            throw new BadRequestException(
                "Too many attachments. Maximum {$maxAttachments} allowed."
            );
        }

        foreach ($attachments as $index => $attachment) {
            $this->validateSingleAttachment($attachment, $index, $maxSize, $allowedMimes);
        }
    }

    /**
     * Validates a single attachment.
     *
     * @param  array{filename: string, content: string, mime_type?: string} $attachment Attachment data
     * @param  int                                                           $index      Attachment index
     * @param  int                                                           $maxSize    Maximum size in bytes
     * @param  array<string>                                                 $allowedMimes Allowed MIME types
     * @return void
     * @throws BadRequestException When validation fails
     */
    private function validateSingleAttachment(
        array $attachment,
        int $index,
        int $maxSize,
        array $allowedMimes
    ): void {
        // Validate required fields
        if (empty($attachment['filename']) || empty($attachment['content'])) {
            throw new BadRequestException(
                "Attachment #{$index}: Missing required fields (filename, content)"
            );
        }

        // Validate filename
        $filename = $attachment['filename'];
        if (!preg_match('/^[a-zA-Z0-9._-]+\.[a-zA-Z]{2,10}$/', $filename)) {
            throw new BadRequestException(
                "Attachment #{$index}: Invalid filename '{$filename}'. Only alphanumeric, dots, dashes, and underscores allowed."
            );
        }

        // Decode and validate content
        $decodedContent = base64_decode($attachment['content'], true);
        if ($decodedContent === false) {
            throw new BadRequestException(
                "Attachment #{$index}: Content is not valid base64"
            );
        }

        // Validate size
        $size = strlen($decodedContent);
        if ($size > $maxSize) {
            $maxMb = $maxSize / (1024 * 1024);
            $actualMb = round($size / (1024 * 1024), 2);

            throw new BadRequestException(
                "Attachment #{$index}: Size {$actualMb}MB exceeds maximum {$maxMb}MB"
            );
        }

        if ($size === 0) {
            throw new BadRequestException(
                "Attachment #{$index}: File is empty"
            );
        }

        // Validate MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($decodedContent);

        if (!in_array($detectedMime, $allowedMimes, true)) {
            $allowed = implode(', ', $allowedMimes);

            throw new BadRequestException(
                "Attachment #{$index}: MIME type '{$detectedMime}' not allowed. Allowed: {$allowed}"
            );
        }

        // Additional security: check for executable signatures
        $this->checkMaliciousContent($decodedContent, $index);
    }

    /**
     * Checks for potentially malicious content signatures.
     *
     * @param  string $content File content
     * @param  int    $index   Attachment index
     * @return void
     * @throws BadRequestException When malicious content detected
     */
    private function checkMaliciousContent(string $content, int $index): void
    {
        // Check for executable signatures
        $signatures = [
            'MZ' => 'Windows executable',
            '\x7fELF' => 'Linux executable',
            '#!/' => 'Script file',
            '<?php' => 'PHP script',
        ];

        foreach ($signatures as $signature => $type) {
            if (str_starts_with($content, $signature)) {
                throw new BadRequestException(
                    "Attachment #{$index}: {$type} detected and rejected for security reasons"
                );
            }
        }
    }
}
