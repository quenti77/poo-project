<?php

namespace Tuto\Console\Components\Input;

use Closure;
use Tuto\Console\Components\AbstractComponent;
use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Output;

class QuestionComponent extends AbstractComponent
{
    /** @var Closure(string): bool|null $validator */
    protected Closure|null $validator = null;

    /**
     * @param Output $output
     */
    public function __construct(
        Output $output,
        protected string $question,
        protected string|null $defaultValue = null,
        protected string $append = ': ',
    ) {
        parent::__construct($output);
    }

    /**
     * @param string $question
     * @return $this
     */
    public function withQuestion(string $question): static
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @param string $defaultValue
     * @return $this
     */
    public function withDefaultValue(string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * @param string $append
     * @return $this
     */
    public function withAppend(string $append): static
    {
        $this->append = $append;
        return $this;
    }

    /**
     * @param callable(string): bool $validator
     * @return $this
     */
    public function withValidator(callable $validator): static
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @return string
     */
    public function run(): string
    {
        while (true) {
            $this->writePrompt();

            $input = $this->output->terminal->readLine();
            if ($input === '' && $this->defaultValue !== null) {
                return $this->defaultValue;
            }
            if ($input === '') {
                $this->output->error("The response can not be empty!");
                continue;
            }

            if ($this->validator === null) {
                return $input;
            }

            $result = ($this->validator)($input);
            if ($result === true) {
                return $input;
            }

            $result = is_string($result) ? $result : 'Invalid value!';
            $this->output->error($result);
        }
    }

    /**
     * @return void
     */
    private function writePrompt(): void
    {
        $this->output->info($this->question);

        if ($this->defaultValue !== null) {
            $this->output->comment(" [{$this->defaultValue}]");
        }

        $this->output->simpleText(Ansi::RESET, $this->append);
    }
}