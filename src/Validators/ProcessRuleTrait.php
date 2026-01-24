<?php

namespace Tuto\Validators;

use Tuto\Collections\Collection;

trait ProcessRuleTrait
{
    /** @var Collection<string, Collection<int, RuleInterface>> $rules */
    protected Collection $rules;

    /** @var Collection<string, mixed> $data */
    protected Collection $data;

    /** @var Collection<string, Collection<int, string>> $errors */
    protected Collection $errors;

    /**
     * @param Collection<string, Collection<int, RuleInterface>|RuleInterface[]>|array<string, Collection<int, RuleInterface>|RuleInterface[]> $fieldRules
     * @return $this
     */
    public function addRules(Collection|array $fieldRules): static
    {
        if (is_array($fieldRules)) {
            $fieldRules = collect($fieldRules);
        }

        foreach ($fieldRules as $fieldName => $rules) {
            $this->addRule($fieldName, $rules);
        }

        return $this;
    }

    /**
     * @param string $fieldName
     * @param Collection<int, RuleInterface>|RuleInterface[] $rules
     * @return $this
     */
    public function addRule(string $fieldName, Collection|array $rules): static
    {
        if (is_array($rules)) {
            $rules = collect($rules);
        }
        if (!$this->rules->offsetExists($fieldName)) {
            $this->rules[$fieldName] = collect();
        }
        $this->rules[$fieldName]->push(...$rules->all());
        return $this;
    }

    /**
     * @param Collection<string, mixed>|array<string, mixed> $data
     * @return $this
     */
    public function mergeData(Collection|array $data): static
    {
        $this->data = $this->data->merge($data);
        return $this;
    }

    /**
     * @return Collection<string, Collection<int, string>>
     */
    public function errors(): Collection
    {
        return $this->errors;
    }

    /**
     * @param string $fieldName
     * @param Collection<int, RuleInterface> $rules
     * @return void
     */
    protected function processRules(string $fieldName, Collection $rules): void
    {
        $success = true;
        $validatedValue = null;

        foreach ($rules as $rule) {
            $rule->withFieldName($fieldName);
            $rule->withData($this->data);
            $rule->withErrors($this->errors);

            $validated = $rule->validate();
            if ($success === true && $validated === false) {
                $success = false;
            }

            $ruleValue = $rule->getValidatedValue();
            if ($ruleValue !== null) {
                $validatedValue = $ruleValue;
            }

            if ($validated === false && $rule->stopOnFailure()) {
                return;
            }
        }

        if ($success === true) {
            $this->validated[$fieldName] = $this->data[$fieldName] ?? null;
        } elseif ($validatedValue !== null) {
            $this->validated[$fieldName] = $validatedValue;
        }
    }
}
