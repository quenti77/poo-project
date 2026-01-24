<?php

namespace Tuto\Validators;

use Tuto\Collections\Collection;

class Validator
{
    use ProcessRuleTrait;

    /** @var Collection<string, mixed> $validated */
    private Collection $validated;

    /** @var bool $processed */
    private bool $processed = false;

    public function __construct()
    {
        $this->rules = collect();
        $this->data = collect();
        $this->validated = collect();
        $this->errors = collect();
    }

    /**
     * @param Collection<string, Collection<int, RuleInterface>|RuleInterface[]>|array<string, Collection<int, RuleInterface>|RuleInterface[]> $fieldRules
     * @param Collection<string, mixed>|array<string, mixed> $data
     * @return static
     */
    public static function make(Collection|array $fieldRules = [], Collection|array $data = []): static
    {
        $instance = new static();
        $instance->addRules($fieldRules);
        $instance->mergeData($data);

        return $instance;
    }

    /**
     * @return void
     */
    public function validate(): void
    {
        foreach ($this->rules as $fieldName => $rules) {
            $this->processRules($fieldName, $rules);
        }
        $this->processed = true;
    }

    /**
     * @return Collection<string, mixed>
     */
    public function validated(): Collection
    {
        if ($this->processed === false) {
            $this->validate();
        }

        return $this->validated;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->errors->isEmpty() === false;
    }
}
