<?php

namespace Tuto\Validators\Rules;

use Tuto\Validators\AbstractRule;

class MaxRule extends AbstractRule
{
    /**
     * @param int $max
     */
    public function __construct(private readonly int $max)
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

        if ($this->max < $size) {
            $this->pushError(
                $field,
                'min',
                trans('framework.rules.max', context: ['field' => $field, 'max' => $this->max]),
            );
            return false;
        }
        return true;
    }
}
