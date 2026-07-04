<?php

namespace App\Support\Logging;

use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Enumerable;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Throwable;
use UnitEnum;

class SensitiveLogContext
{
    private const REDACTED = '[redacted]';

    private const PERSONAL_DATA_REDACTED = '[personal-data-redacted]';

    private const FILE_CONTENT_REDACTED = '[file-content-redacted]';

    private const RAW_PAYLOAD_REDACTED = '[raw-payload-redacted]';

    private const MAX_DEPTH = 6;

    private const MAX_ARRAY_KEYS = 20;

    /**
     * @return mixed
     */
    public static function sanitize(mixed $value, int $depth = 0)
    {
        if ($depth > self::MAX_DEPTH) {
            return '[max-depth-exceeded]';
        }

        if (is_array($value)) {
            return self::sanitizeArray($value, $depth);
        }

        if (is_string($value)) {
            return self::sanitizeMessage($value);
        }

        if ($value instanceof Enumerable) {
            return self::sanitize($value->all(), $depth + 1);
        }

        if ($value instanceof Throwable) {
            return self::exceptionSummary($value);
        }

        if ($value instanceof UploadedFile || $value instanceof SymfonyUploadedFile) {
            return self::uploadedFileSummary($value);
        }

        if ($value instanceof DateTimeInterface || $value instanceof BackedEnum || $value instanceof UnitEnum) {
            return $value;
        }

        if (is_object($value)) {
            return self::objectSummary($value);
        }

        if (is_resource($value)) {
            return '[resource-redacted]';
        }

        return $value;
    }

    public static function sanitizeMessage(string $message): string
    {
        $message = preg_replace('/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/=-]+/i', '$1 '.self::REDACTED, $message) ?? $message;
        $message = preg_replace('/\b(Cookie|Set-Cookie)\s*:\s*[^\r\n]+/i', '$1: '.self::REDACTED, $message) ?? $message;
        $message = preg_replace(
            '/\b(password|passphrase|secret|token|api[_-]?key|client[_-]?secret|access[_-]?token|refresh[_-]?token|signature|csrf[_-]?token|xsrf[_-]?token)\s*[:=]\s*("[^"]*"|\'[^\']*\'|[^,\s;]+)/i',
            '$1='.self::REDACTED,
            $message
        ) ?? $message;
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email:redacted]', $message) ?? $message;
        $message = preg_replace('/\b(?:\d[ -]*?){13,19}\b/', '[payment-card:redacted]', $message) ?? $message;

        return $message;
    }

    /**
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private static function sanitizeArray(array $value, int $depth): array
    {
        $sanitized = [];

        foreach ($value as $key => $item) {
            $sanitized[$key] = self::sanitizeKeyedValue($key, $item, $depth);
        }

        return $sanitized;
    }

    private static function sanitizeKeyedValue(int|string $key, mixed $value, int $depth): mixed
    {
        $normalizedKey = self::normalizeKey($key);

        if (self::isRawPayloadKey($normalizedKey)) {
            return self::payloadSummary($value);
        }

        if (self::isFileContentKey($normalizedKey)) {
            return self::FILE_CONTENT_REDACTED;
        }

        if (self::isSensitiveKey($normalizedKey)) {
            return self::REDACTED;
        }

        if (self::isPersonalDataKey($normalizedKey)) {
            return self::PERSONAL_DATA_REDACTED;
        }

        if (self::isPaymentDataKey($normalizedKey)) {
            return self::REDACTED;
        }

        return self::sanitize($value, $depth + 1);
    }

    private static function normalizeKey(int|string $key): string
    {
        $key = (string) preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', (string) $key);
        $key = (string) preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $key);

        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $key));
    }

    private static function isSensitiveKey(string $key): bool
    {
        return preg_match(
            '/(?:^|_)(?:password|passwd|passphrase|secret|token|authorization|cookie|set_cookie|api_key|apikey|private_key|client_secret|refresh_token|access_token|remember_token|csrf|xsrf|signature|credential|credentials|session|jwt|bearer|key)(?:$|_)/',
            $key
        ) === 1;
    }

    private static function isPersonalDataKey(string $key): bool
    {
        return in_array($key, [
            'email',
            'phone',
            'mobile',
            'address',
            'street_address',
            'billing_address',
            'shipping_address',
            'name',
            'first_name',
            'last_name',
            'full_name',
            'date_of_birth',
            'birth_date',
            'dob',
        ], true);
    }

    private static function isPaymentDataKey(string $key): bool
    {
        return preg_match(
            '/(?:^|_)(?:card|card_number|cvv|cvc|pan|iban|routing_number|account_number|payment_method|payment_token|billing_details|bank_account)(?:$|_)/',
            $key
        ) === 1;
    }

    private static function isRawPayloadKey(string $key): bool
    {
        return in_array($key, [
            'payload',
            'raw_payload',
            'request_payload',
            'request_body',
            'body',
            'raw_body',
            'content',
            'contents',
            'input',
            'payment_data',
            'transaction_payload',
        ], true);
    }

    private static function isFileContentKey(string $key): bool
    {
        return str_contains($key, 'file_content')
            || str_contains($key, 'file_contents')
            || str_contains($key, 'attachment_content')
            || str_contains($key, 'document_content')
            || str_contains($key, 'base64');
    }

    /**
     * @return array{redacted: true, type: string, keys?: array<int, string>}|string
     */
    private static function payloadSummary(mixed $value): array|string
    {
        if (is_array($value)) {
            return [
                'redacted' => true,
                'type' => 'array',
                'keys' => array_map('strval', array_slice(array_keys($value), 0, self::MAX_ARRAY_KEYS)),
            ];
        }

        if ($value instanceof UploadedFile || $value instanceof SymfonyUploadedFile) {
            return self::uploadedFileSummary($value);
        }

        if ($value instanceof SplFileInfo) {
            return [
                'redacted' => true,
                'type' => 'file',
                'basename' => $value->getBasename(),
                'size' => $value->isFile() ? $value->getSize() : null,
            ];
        }

        if (is_string($value)) {
            return self::RAW_PAYLOAD_REDACTED;
        }

        return [
            'redacted' => true,
            'type' => get_debug_type($value),
        ];
    }

    /**
     * @return array{redacted: true, type: string, original_name: string, mime_type: string|null, size: int|null, error: int}
     */
    private static function uploadedFileSummary(UploadedFile|SymfonyUploadedFile $file): array
    {
        return [
            'redacted' => true,
            'type' => 'uploaded_file',
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'error' => $file->getError(),
        ];
    }

    /**
     * @return array{class: string, id?: int|string}
     */
    private static function objectSummary(object $value): array
    {
        $summary = [
            'class' => $value::class,
        ];

        $id = null;

        if ($value instanceof Model) {
            $id = $value->getKey();
        } elseif (isset($value->id) && (is_int($value->id) || is_string($value->id))) {
            $id = $value->id;
        }

        if (is_int($id) || is_string($id)) {
            $summary['id'] = $id;
        }

        return $summary;
    }

    /**
     * @return array{class: class-string<Throwable>, code: int|string, file: string, line: int}
     */
    private static function exceptionSummary(Throwable $throwable): array
    {
        return [
            'class' => $throwable::class,
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ];
    }
}
