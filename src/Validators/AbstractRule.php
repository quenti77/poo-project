<?php

namespace Tuto\Validators;

use Tuto\Collections\Collection;

abstract class AbstractRule implements RuleInterface
{
    use ProcessRuleTrait;

    /** @var string $fieldName */
    protected string $fieldName = 'field';

    /** @var Collection<string, mixed> $validated */
    protected Collection $validated;

    public function __construct()
    {
        $this->data = collect();
        $this->validated = collect();
        $this->errors = collect();
    }

    /**
     * @param string $fieldName
     * @return void
     */
    public function withFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @param Collection<string, mixed> $data
     * @return void
     */
    public function withData(Collection $data): void
    {
        $this->data = $data;
    }

    /**
     * @param Collection<string, Collection<string, string>> $errors
     * @return void
     */
    public function withErrors(Collection $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * @return bool
     */
    public function stopOnFailure(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    abstract public function validate(): bool;

    /**
     * @param string $fieldName
     * @param string $errorName
     * @param string $error
     * @return void
     */
    protected function pushError(string $fieldName, string $ruleName, string $error): void
    {
        $this->errors[$fieldName] ??= collect();
        $this->errors[$fieldName][$ruleName] = $error;
    }

    /**
     * @param string $value
     * @return int|float
     */
    protected function convertToNumeric(string $value): int|float
    {
        return str_contains($value, '.') ? (float) $value : (int) $value;
    }
}
