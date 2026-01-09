<?php

namespace Tuto\Event;

use Tuto\Event\Contract\EventInterface;
use Tuto\Event\Contract\ListenerInterface;
use Tuto\Event\Contract\ShouldQueueInterface;
use Tuto\Queue\Jobs\AbstractJob;

class QueuedListenerJob extends AbstractJob
{
    /**
     * @param string $listenerClass
     * @param EventInterface $event
     */
    public function __construct(
        private readonly string $listenerClass,
        private readonly EventInterface $event,
    ) {
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        $listener = $this->getListener();
        if ($listener === null) {
            return;
        }

        $listener->handle($this->event);
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        $listener = $this->getListener();

        return $listener instanceof ShouldQueueInterface
            ? $listener->getMaxAttempts()
            : parent::getMaxAttempts();
    }

    private function getListener(): ListenerInterface|null
    {
        $listener = container($this->listenerClass);
        if (!($listener instanceof ListenerInterface)) {
            logger()->warning("'{$this->listenerClass}' must be implements '" . ListenerInterface::class . "'");
            return null;
        }
        return $listener;
    }
}
