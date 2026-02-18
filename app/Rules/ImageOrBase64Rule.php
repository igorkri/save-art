<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Правило валідації для зображення (файл або Base64)
 */
class ImageOrBase64Rule implements ValidationRule
{
    protected int $maxSizeInKb;

    protected array $allowedMimes;

    public function __construct(int $maxSizeInKb = 15360, array $allowedMimes = ['jpeg', 'jpg', 'png', 'gif', 'webp'])
    {
        $this->maxSizeInKb = $maxSizeInKb;
        $this->allowedMimes = $allowedMimes;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // Пропускаємо nullable
        }

        // Якщо це файл
        if ($value instanceof UploadedFile) {
            $this->validateUploadedFile($value, $fail);

            return;
        }

        // Якщо це строка (Base64 або URL)
        if (is_string($value)) {
            $this->validateStringValue($value, $fail);

            return;
        }

        $fail('Поле :attribute має бути зображенням (файл або Base64).');
    }

    /**
     * Валідація завантаженого файлу
     */
    protected function validateUploadedFile(UploadedFile $file, Closure $fail): void
    {
        // Перевірка MIME типу
        $mimeType = $file->getMimeType();
        $isValidMime = false;

        foreach ($this->allowedMimes as $allowedMime) {
            if (str_contains($mimeType, $allowedMime) || str_contains($mimeType, 'image')) {
                $isValidMime = true;
                break;
            }
        }

        if (! $isValidMime) {
            $fail('Поле :attribute має бути зображенням.');

            return;
        }

        // Перевірка розміру
        $fileSizeInKb = $file->getSize() / 1024;

        if ($fileSizeInKb > $this->maxSizeInKb) {
            $maxSizeInMb = round($this->maxSizeInKb / 1024, 0);
            $fail("Максимальний розмір зображення — {$maxSizeInMb} МБ");
        }
    }

    /**
     * Валідація строкового значення (Base64 або URL)
     */
    protected function validateStringValue(string $value, Closure $fail): void
    {
        // Якщо це URL (вже завантажене зображення) - пропускаємо
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return;
        }

        // Якщо це відносний шлях до storage - пропускаємо
        if (str_starts_with($value, 'projects/') || str_starts_with($value, 'storage/')) {
            return;
        }

        // Якщо це Base64
        if (str_starts_with($value, 'data:image/')) {
            $this->validateBase64Image($value, $fail);

            return;
        }

        // Спробуємо декодувати як чистий Base64
        if ($this->isValidBase64($value)) {
            $this->validatePureBase64($value, $fail);

            return;
        }

        $fail('Поле :attribute має бути зображенням (файл, Base64 або URL).');
    }

    /**
     * Валідація Base64 зображення з data URI
     */
    protected function validateBase64Image(string $value, Closure $fail): void
    {
        // Перевіряємо MIME тип з data URI
        if (preg_match('/^data:image\/(\w+);base64,/', $value, $matches)) {
            $mimeType = strtolower($matches[1]);

            if (! in_array($mimeType, $this->allowedMimes)) {
                $allowed = implode(', ', $this->allowedMimes);
                $fail("Поле :attribute має бути зображенням формату: {$allowed}.");

                return;
            }

            // Отримуємо Base64 дані
            $base64Data = substr($value, strpos($value, ',') + 1);
            $decodedData = base64_decode($base64Data, true);

            if ($decodedData === false) {
                $fail('Поле :attribute містить некоректні Base64 дані.');

                return;
            }

            // Перевірка розміру
            $sizeInKb = strlen($decodedData) / 1024;

            if ($sizeInKb > $this->maxSizeInKb) {
                $maxSizeInMb = round($this->maxSizeInKb / 1024, 0);
                $fail("Максимальний розмір зображення — {$maxSizeInMb} МБ");
            }
        } else {
            $fail('Поле :attribute має некоректний формат Base64 зображення.');
        }
    }

    /**
     * Перевірка чи є строка валідним Base64
     */
    protected function isValidBase64(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        return base64_encode($decoded) === $value;
    }

    /**
     * Валідація чистого Base64 (без data URI)
     */
    protected function validatePureBase64(string $value, Closure $fail): void
    {
        $decodedData = base64_decode($value, true);

        if ($decodedData === false) {
            $fail('Поле :attribute містить некоректні Base64 дані.');

            return;
        }

        // Перевіряємо що це зображення через magic bytes
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($decodedData);

        if (! str_starts_with($mimeType, 'image/')) {
            $fail('Поле :attribute має бути зображенням.');

            return;
        }

        // Перевірка розміру
        $sizeInKb = strlen($decodedData) / 1024;

        if ($sizeInKb > $this->maxSizeInKb) {
            $maxSizeInMb = round($this->maxSizeInKb / 1024, 0);
            $fail("Максимальний розмір зображення — {$maxSizeInMb} МБ");
        }
    }
}
