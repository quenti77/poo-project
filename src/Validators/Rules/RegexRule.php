<?php

namespace Tuto\Validators\Rules;

use Tuto\Validators\AbstractRule;

class RegexRule extends AbstractRule
{
    /**
     * @param string $regex
     * @param string|null $humanRegex
     */
    public function __construct(
        private readonly string $regex,
        private readonly string|null $humanRegex = null,
    ) {
        parent::__construct();
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $field = $this->fieldName;
        $value = $this->data->get($field, '');

        if (!preg_match("/{$this->regex}/", $value)) {
            $regex = $this->humanRegex ?? $this->regex;
            $this->pushError(
                $field,
                'regex',
                trans('framework.rules.regex', context: ['field' => $field, 'regex' => $regex]),
            );
            return false;
        }
        return true;
    }
}
