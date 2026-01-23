<?php

namespace Tuto\Validators\Rules;

use Tuto\Validators\AbstractRule;

class MinRule extends AbstractRule
{
    /**
     * @param int $min
     */
    public function __construct(private readonly int $min)
    {
        parent::__construct();
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $field = $this->fieldName;
        $value = $this->data->get($field, '');

        $isNumeric = is_numeric($value);
        $size = $isNumeric ? $this->convertToNumeric($value) : mb_strlen($value);

        if ($this->min > $size) {
            $this->pushError(
                $field,
                'min',
                trans('framework.rules.min', context: ['field' => $field, 'min' => $this->min]),
            );
            return false;
        }
        return true;
    }
}
