<?php

namespace Tuto\Validators\Rules;

use Tuto\Collections\Collection;
use Tuto\Validators\AbstractRule;

class ArrayRule extends AbstractRule
{
    public function __construct(Collection|array $rules)
    {
        parent::__construct();

        $this->addRules($rules);
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        foreach ($this->rules as $fieldName => $rules) {
            $this->processRules($fieldName, $rules);
        }
    }
}
