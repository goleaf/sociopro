<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

final readonly class PostMediaFile implements ValidationRule
{
    private const CREATE_MAX_KILOBYTES = 500000;

    private const UPDATE_MAX_KILOBYTES = 20480;

    /**
     * @var list<string>
     */
    private const ALLOWED_EXTENSIONS = [
        'jpeg',
        'png',
        'jpg',
        'gif',
        'svg',
        'mp4',
        'mov',
        'wmv',
        'avi',
        'webm',
    ];

    private function __construct(private int $maxKilobytes) {}

    public static function forCreate(): self
    {
        return new self(self::CREATE_MAX_KILOBYTES);
    }

    public static function forUpdate(): self
    {
        return new self(self::UPDATE_MAX_KILOBYTES);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validator = Validator::make(
            ['file' => $value],
            ['file' => ['bail', $this->mimesRule(), $this->maxRule()]]
        );

        if ($validator->fails()) {
            $fail('validation.post_media_file')->translate([
                'types' => $this->extensionList(),
                'max' => (string) $this->maxKilobytes,
            ]);
        }
    }

    public function maxKilobytes(): int
    {
        return $this->maxKilobytes;
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    private function mimesRule(): string
    {
        return 'mimes:'.implode(',', self::ALLOWED_EXTENSIONS);
    }

    private function maxRule(): string
    {
        return 'max:'.$this->maxKilobytes;
    }

    private function extensionList(): string
    {
        return implode(', ', self::ALLOWED_EXTENSIONS);
    }
}
