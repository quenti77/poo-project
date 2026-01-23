<?php

namespace Tuto\Validators\Rules;

use Tuto\Validators\AbstractRule;

class RequiredRule extends AbstractRule
{

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $field = $this->fieldName;
        $value = $this->data->get($field);

        if ($value === null) {
            $this->pushError($field, 'required', trans('framework.rules.required', context: ['field' => $field]));
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function stopOnFailure(): bool
    {
        return true;
    }
}
