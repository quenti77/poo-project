<?php

namespace Tuto\Validators\Rules;

use Closure;
use Tuto\Validators\AbstractRule;

class CallbackRule extends AbstractRule
{
    /**
     * @param Closure(mixed $value, string $field, Closure(string $rule, string $message): void $fail): bool $callback
     * @param bool $shouldStopOnFailure
     */
    public function __construct(
        private readonly Closure $callback,
        private readonly bool $shouldStopOnFailure = false,
    ) {
        parent::__construct();
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $field = $this->fieldName;
        $value = $this->data->get($field);

        $fail = function (string $rule, string $message) use ($field): void {
            $this->pushError($field, $rule, $message);
        };

        return ($this->callback)($value, $field, $fail);
    }

    /**
     * @return bool
     */
    public function stopOnFailure(): bool
    {
        return $this->shouldStopOnFailure;
    }
}
