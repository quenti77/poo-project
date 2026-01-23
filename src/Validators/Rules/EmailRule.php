<?php

namespace Tuto\Validators\Rules;

use Tuto\Validators\AbstractRule;

class EmailRule extends AbstractRule
{

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $field = $this->fieldName;
        $value = $this->data->get($field);

        if ($value === null) {
            $this->pushEmailError();
            return false;
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->pushEmailError();
            return false;
        }
        return true;
    }

    /**
     * @return void
     */
    private function pushEmailError(): void
    {
        $this->pushError(
            $this->fieldName,
            'email',
            trans('framework.rules.email', context: ['field' => $this->fieldName]),
        );
    }
}
