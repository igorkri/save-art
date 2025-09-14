<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class MaxFileSizeRule implements ValidationRule
{
    protected int $maxSizeInKb;

    public function __construct(int $maxSizeInKb = 73728) // 72 MB за замовчуванням
    {
        $this->maxSizeInKb = $maxSizeInKb;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $fileSizeInKb = $value->getSize() / 1024; // Конвертуємо байти в кілобайти

        if ($fileSizeInKb > $this->maxSizeInKb) {
            $fail("Розмір файлу не повинен перевищувати {$this->maxSizeInKb} кілобайт.");
        }
    }
}
