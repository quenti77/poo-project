<?php

namespace Tuto\Validators\Rules;

use Tuto\Validators\AbstractRule;

class SometimesRule extends AbstractRule
{

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $field = $this->fieldName;
        $value = $this->data->get($field);

        return $value !== null;
    }

    /**
     * @return bool
     */
    public function stopOnFailure(): bool
    {
        return true;
    }
}
