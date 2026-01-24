<?php

namespace Tuto\Validators;

use Tuto\Collections\Collection;
use Tuto\Http\Requests\Request;

class RequestValidator extends Validator
{
    /** @var Request|null $request */
    protected Request|null $request = null;

    /**
     * @return Request|null
     */
    public function getRequest(): Request|null
    {
        return $this->request;
    }

    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): static
    {
        $data = collect()
            ->merge($request->query)
            ->merge($request->body)
            ->merge($request->files);

        $instance = static::make();
        $instance->withRequest($request);
        $instance->addRules($instance->rules());
        $instance->mergeData($data);

        return $instance;
    }

    /**
     * @param Request $request
     * @return void
     */
    protected function withRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @return Collection<string, Collection<int, RuleInterface>|RuleInterface[]>|array<string, Collection<int, RuleInterface>|RuleInterface[]>
     */
    protected function rules(): Collection|array
    {
        return [];
    }
}
