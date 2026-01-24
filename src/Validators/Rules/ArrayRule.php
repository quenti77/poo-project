<?php

namespace Tuto\Validators\Rules;

use Tuto\Collections\Collection;
use Tuto\Validators\AbstractRule;
use Tuto\Validators\RuleInterface;

class ArrayRule extends AbstractRule
{
    /** @var bool $isList */
    private bool $isList;

    /** @var array<int|string, mixed> $validatedItems */
    private array $validatedItems = [];

    /**
     * @param Collection<string, Collection<int, RuleInterface>|RuleInterface[]>|array<string, Collection<int, RuleInterface>|RuleInterface[]> $rules
     * @param bool $isList
     */
    public function __construct(Collection|array $rules, bool $isList = true)
    {
        parent::__construct();

        $this->isList = $isList;
        $this->rules = collect();
        $this->addRules($rules);
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public function getValidatedValue(): array|null
    {
        return !empty($this->validatedItems) ? $this->validatedItems : null;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $value = $this->data->get($this->fieldName);

        if (!is_array($value)) {
            $this->pushError(
                $this->fieldName,
                'array',
                trans('framework.rules.array', context: ['field' => $this->fieldName]),
            );
            return false;
        }

        return $this->isList
            ? $this->validateList($value)
            : $this->validateAssociative($value);
    }

    /**
     * @param array<int, mixed> $items
     * @return bool
     */
    private function validateList(array $items): bool
    {
        $success = true;
        $this->validatedItems = [];

        foreach ($items as $index => $item) {
            $itemData = is_array($item) ? collect($item) : collect([$this->fieldName => $item]);
            $itemSuccess = true;

            foreach ($this->rules as $subFieldName => $rules) {
                $fullFieldName = "{$this->fieldName}.{$index}.{$subFieldName}";
                $fieldSuccess = $this->validateField($subFieldName, $fullFieldName, $rules, $itemData);

                if (!$fieldSuccess) {
                    $itemSuccess = false;
                    $success = false;
                }
            }

            if ($itemSuccess) {
                $this->validatedItems[$index] = $item;
            }
        }

        return $success;
    }

    /**
     * @param array<string, mixed> $item
     * @return bool
     */
    private function validateAssociative(array $item): bool
    {
        $success = true;
        $this->validatedItems = [];
        $itemData = collect($item);

        foreach ($this->rules as $subFieldName => $rules) {
            $fullFieldName = "{$this->fieldName}.{$subFieldName}";
            $fieldSuccess = $this->validateField($subFieldName, $fullFieldName, $rules, $itemData);

            if (!$fieldSuccess) {
                $success = false;
            } elseif (isset($item[$subFieldName])) {
                $this->validatedItems[$subFieldName] = $item[$subFieldName];
            }
        }

        return $success;
    }

    /**
     * @param string $subFieldName
     * @param string $fullFieldName
     * @param Collection<int, RuleInterface> $rules
     * @param Collection<string, mixed> $itemData
     * @return bool
     */
    private function validateField(string $subFieldName, string $fullFieldName, Collection $rules, Collection $itemData): bool
    {
        $tempErrors = collect();
        $fieldSuccess = true;

        foreach ($rules as $rule) {
            $rule->withFieldName($subFieldName);
            $rule->withData($itemData);
            $rule->withErrors($tempErrors);

            if ($rule->validate() === false) {
                $fieldSuccess = false;

                if ($rule->stopOnFailure()) {
                    break;
                }
            }
        }

        if ($tempErrors->offsetExists($subFieldName)) {
            $this->errors[$fullFieldName] = $tempErrors[$subFieldName];
        }

        return $fieldSuccess;
    }
}
