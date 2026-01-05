<?php

namespace Tuto\Event;

use DateMalformedStringException;
use JsonException;
use Random\RandomException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Tuto\Base\ClassNotFoundException;
use Tuto\Collections\Collection;
use Tuto\Container\DependencyInjectionContainer;
use Tuto\Event\Contract\EventDispatcherInterface;
use Tuto\Event\Contract\EventInterface;
use Tuto\Event\Contract\EventSubscriberInterface;
use Tuto\Event\Contract\ListenerInterface;
use Tuto\Event\Contract\ShouldQueueInterface;
use Tuto\Queue\JobsRepository;

class EventDispatcher implements EventDispatcherInterface
{
    /** @var Collection<string, Collection<int, Collection<int, callable|string>>> */
    private Collection $listeners;

    /**
     * @param JobsRepository $jobsRepository
     */
    public function __construct(
        private JobsRepository $jobsRepository,
    ) {
        $this->listeners = collect();
    }

    /**
     * @param string $eventName
     * @param callable|string $listener
     * @param int $priority
     * @return void
     */
    public function listen(string $eventName, callable|string $listener, int $priority = 0): void
    {
        if (!$this->listeners->offsetExists($eventName)) {
            $this->listeners[$eventName] = collect();
        }

        $eventListeners = $this->listeners[$eventName];
        if (!$eventListeners->offsetExists($priority)) {
            $eventListeners[$priority] = collect();
        }

        $priorityListeners = $eventListeners[$priority];
        $priorityListeners->push($listener);
    }

    /**
     * @param EventSubscriberInterface $subscriber
     * @return void
     */
    public function subscribe(EventSubscriberInterface $subscriber): void
    {
        $subscriber->subscribe($this);
    }

    /**
     * @param EventInterface $event
     * @return EventInterface
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws RandomException
     */
    public function dispatch(EventInterface $event): EventInterface
    {
        $listeners = $this->getListeners($event->getName());
        foreach ($listeners as $listener) {
            if ($event->isPropagationStopped()) {
                break;
            }

            $callable = $this->resolveListener($listener);
            if (is_string($listener) && $this->shouldQueue($listener)) {
                $this->queueListener($listener, $event);
                continue;
            }

            $callable($event);
        }

        return $event;
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool
    {
        return $this->listeners->offsetExists($eventName)
            && !$this->listeners[$eventName]->isEmpty();
    }

    /**
     * @param string $eventName
     * @return Collection<int, callable|string>
     */
    public function getListeners(string $eventName): Collection
    {
        if (!$this->hasListeners($eventName)) {
            return collect();
        }

        $eventListeners = $this->listeners[$eventName];

        $priorities = $eventListeners->keys()->all();
        rsort($priorities);

        $sorted = collect();
        foreach ($priorities as $priority) {
            $sorted->merge($eventListeners[$priority]);
        }
        return $sorted;
    }

    /**
     * @param callable|string $listener
     * @return callable
     */
    private function resolveListener(callable|string $listener): callable
    {
        if (is_callable($listener)) {
            return $listener;
        }

        $instance = container($listener);
        if (!($instance instanceof ListenerInterface)) {
            throw new RuntimeException(sprintf("'%s' must be an instance of '%s'", $listener, ListenerInterface::class));
        }

        return static function($event) use ($instance): void {
            $instance->handle($event);
        };
    }

    /**
     * @param string $listener
     * @return bool
     */
    private function shouldQueue(string $listener): bool
    {
        try {
            return new ReflectionClass($listener)->implementsInterface(ShouldQueueInterface::class);
        } catch (ClassNotFoundException|ReflectionException) {
            return false;
        }
    }

    /**
     * @param string $listener
     * @param EventInterface $event
     * @return void
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws RandomException
     */
    private function queueListener(string $listener, EventInterface $event): void
    {
        $job = new QueuedListenerJob($listener, $event);

        $listenerInstance = container($listener);
        $queue = $listenerInstance instanceof ShouldQueueInterface
            ? $listenerInstance->getQueue()
            : 'default';

        $this->jobsRepository->push($job, $queue);
    }
}