<?php

namespace Tuto\Validators;

use Tuto\Collections\Collection;

interface RuleInterface
{
    /**
     * @param string $fieldName
     * @return void
     */
    public function withFieldName(string $fieldName): void;

    /**
     * @param Collection<string, mixed> $data
     * @return void
     */
    public function withData(Collection $data): void;

    /**
     * @param Collection<string, Collection<string, string>> $errors
     * @return void
     */
    public function withErrors(Collection $errors): void;

    /**
     * @return bool
     */
    public function stopOnFailure(): bool;

    /**
     * @return bool
     */
    public function validate(): bool;

    /**
     * @return mixed
     */
    public function getValidatedValue(): mixed;
}
