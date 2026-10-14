<?php
/**
 * کلاس Validator — اعتبارسنجی ورودی‌ها با پیام‌های فارسی
 *
 * قواعد پشتیبانی‌شده:
 *  required | email | numeric | integer | min:n | max:n | between:a,b
 *  in:a,b,c | regex:pattern | confirmed | unique:table,column[,ignoreId]
 *  exists:table,column | phone (موبایل ایران) | melli (کد ملی) | date | jalali_date | slug | array
 */

declare(strict_types=1);

namespace Core;

final class Validator
{
    /** @var array<string, string> خطاهای اعتبارسنجی */
    private array $errors = [];

    /** @var array<string, mixed> دادهٔ اعتبارسنجی‌شده */
    private array $validated = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules  مثال: ['name' => 'required|min:3', 'phone' => 'required|phone']
     * @param array<string, string> $labels برچسب فارسی فیلدها
     */
    public function __construct(
        private array $data,
        private array $rules,
        private array $labels = [],
    ) {
        $this->run();
    }

    public static function make(array $data, array $rules, array $labels = []): self
    {
        return new self($data, $rules, $labels);
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return $this->errors === [] ? '' : reset($this->errors);
    }

    /** @return array<string, mixed> فقط فیلدهای دارای قاعده */
    public function validated(): array
    {
        return $this->validated;
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? $field;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $isRequired = in_array('required', $rules, true);

            // فیلد اختیاری و خالی → رد کردن بقیهٔ قواعد
            if (!$isRequired && ($value === null || $value === '')) {
                if (array_key_exists($field, $this->data)) {
                    $this->validated[$field] = $value;
                }
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $error = $this->checkRule($name, (string) $param, $field, $value);
                if ($error !== null) {
                    $this->errors[$field] ??= $error;
                    continue 2; // فیلد دارای خطا → عبور از قواعد بعدی همان فیلد
                }
            }
            $this->validated[$field] = $value;
        }
    }

    private function checkRule(string $rule, string $param, string $field, mixed $value): ?string
    {
        $label = $this->label($field);
        $strValue = is_scalar($value) ? (string) $value : '';

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || $value === []) {
                    return "«{$label}» الزامی است.";
                }
                return null;

            case 'email':
                return filter_var($strValue, FILTER_VALIDATE_EMAIL) ? null : "«{$label}» باید یک رایانامهٔ معتبر باشد.";

            case 'numeric':
                return is_numeric(en_digits($strValue)) ? null : "«{$label}» باید عددی باشد.";

            case 'integer':
                return preg_match('/^-?\d+$/', en_digits($strValue)) ? null : "«{$label}» باید عدد صحیح باشد.";

            case 'min':
                $num = en_digits($strValue);
                if (is_numeric($num) && $this->isNumericField($field)) {
                    return ((float) $num >= (float) $param) ? null : "«{$label}» نباید کمتر از " . Jalali::digits($param) . " باشد.";
                }
                return (mb_strlen($strValue) >= (int) $param) ? null : "«{$label}» باید حداقل " . Jalali::digits($param) . " نویسه باشد.";

            case 'max':
                $num = en_digits($strValue);
                if (is_numeric($num) && $this->isNumericField($field)) {
                    return ((float) $num <= (float) $param) ? null : "«{$label}» نباید بیشتر از " . Jalali::digits($param) . " باشد.";
                }
                return (mb_strlen($strValue) <= (int) $param) ? null : "«{$label}» باید حداکثر " . Jalali::digits($param) . " نویسه باشد.";

            case 'between':
                [$min, $max] = array_pad(explode(',', $param), 2, '0');
                $num = (float) en_digits($strValue);
                return ($num >= (float) $min && $num <= (float) $max)
                    ? null
                    : "«{$label}» باید بین " . Jalali::digits($min) . " و " . Jalali::digits($max) . " باشد.";

            case 'in':
                $allowed = explode(',', $param);
                return in_array($strValue, $allowed, true) ? null : "مقدار «{$label}» معتبر نیست.";

            case 'regex':
                return preg_match($param, $strValue) ? null : "قالب «{$label}» نامعتبر است.";

            case 'confirmed':
                $confirm = $this->data[$field . '_confirmation'] ?? null;
                return ($strValue === (string) $confirm) ? null : "تکرار «{$label}» مطابقت ندارد.";

            case 'unique':
                [$table, $column, $ignoreId] = array_pad(explode(',', $param), 3, null);
                $sql = "SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = ?";
                $bindings = [$value];
                if ($ignoreId !== null && $ignoreId !== '') {
                    $sql .= ' AND id != ?';
                    $bindings[] = (int) $ignoreId;
                }
                $row = Database::selectOne($sql, $bindings);
                return ((int) ($row['c'] ?? 0) === 0) ? null : "«{$label}» قبلاً ثبت شده است.";

            case 'exists':
                [$table, $column] = array_pad(explode(',', $param), 2, 'id');
                $row = Database::selectOne("SELECT COUNT(*) AS c FROM `{$table}` WHERE `{$column}` = ?", [$value]);
                return ((int) ($row['c'] ?? 0) > 0) ? null : "«{$label}» انتخاب‌شده وجود ندارد.";

            case 'phone':
                $phone = normalize_phone($strValue);
                return preg_match('/^09\d{9}$/', $phone) ? null : "«{$label}» باید یک شمارهٔ موبایل معتبر ایرانی باشد (مثلاً ۰۹۱۵۱۲۳۴۵۶۷).";

            case 'melli':
                return $this->isValidNationalId($strValue) ? null : "«{$label}» معتبر نیست.";

            case 'date':
                return (strtotime($strValue) !== false) ? null : "«{$label}» باید تاریخ معتبر باشد.";

            case 'jalali_date':
                // ورودی سه فیلدی سال/ماه/روز یا رشتهٔ 1403/05/12
                if (preg_match('/^(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})$/', en_digits($strValue), $m)) {
                    $ok = (int) $m[1] >= 1300 && (int) $m[1] <= 1500
                        && (int) $m[2] >= 1 && (int) $m[2] <= 12
                        && (int) $m[3] >= 1 && (int) $m[3] <= Jalali::monthLength((int) $m[1], (int) $m[2]);
                    return $ok ? null : "«{$label}» باید یک تاریخ شمسی معتبر باشد.";
                }
                return "«{$label}» باید یک تاریخ شمسی معتبر باشد.";

            case 'slug':
                return preg_match('/^[\p{L}\p{N}\-_]+$/u', $strValue) ? null : "«{$label}» فقط می‌تواند شامل حروف، رقم، خط تیره و زیرخط باشد.";

            case 'array':
                return is_array($value) ? null : "«{$label}» نامعتبر است.";

            default:
                return null; // قواعد ناشناخته نادیده گرفته می‌شوند
        }
    }

    /** تشخیص اینکه فیلد عددی است (برای تفسیر min/max به‌عنوان مقدار نه طول) */
    private function isNumericField(string $field): bool
    {
        $rules = $this->rules[$field] ?? '';
        return str_contains($rules, 'numeric') || str_contains($rules, 'integer');
    }

    /** الگوریتم استاندارد اعتبارسنجی کد ملی ایران */
    private function isValidNationalId(string $input): bool
    {
        $input = en_digits($input);
        if (!preg_match('/^\d{8,10}$/', $input)) {
            return false;
        }
        $input = str_pad($input, 10, '0', STR_PAD_LEFT);
        if (preg_match('/^(\d)\1{9}$/', $input)) {
            return false; // ارقام یکسان نامعتبر است
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $input[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $check = (int) $input[9];
        return ($remainder < 2 && $check === $remainder) || ($remainder >= 2 && $check === 11 - $remainder);
    }
}
