<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix202306\Symfony\Component\HttpKernel\EventListener;

use ECSPrefix202306\Psr\Log\LoggerInterface;
use ECSPrefix202306\Symfony\Component\Console\ConsoleEvents;
use ECSPrefix202306\Symfony\Component\Console\Event\ConsoleEvent;
use ECSPrefix202306\Symfony\Component\Console\Output\ConsoleOutputInterface;
use ECSPrefix202306\Symfony\Component\ErrorHandler\ErrorHandler;
use ECSPrefix202306\Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ECSPrefix202306\Symfony\Component\HttpKernel\Event\KernelEvent;
use ECSPrefix202306\Symfony\Component\HttpKernel\KernelEvents;
/**
 * Configures errors and exceptions handlers.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @final
 *
 * @internal
 */
class DebugHandlersListener implements EventSubscriberInterface
{
    /**
     * @var string|object|null
     */
    private $earlyHandler;
    /**
     * @var \Closure|null
     */
    private $exceptionHandler;
    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    private $logger;
    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    private $deprecationLogger;
    /**
     * @var mixed[]|int|null
     */
    private $levels;
    /**
     * @var int|null
     */
    private $throwAt;
    /**
     * @var bool
     */
    private $scream;
    /**
     * @var bool
     */
    private $scope;
    /**
     * @var bool
     */
    private $firstCall = \true;
    /**
     * @var bool
     */
    private $hasTerminatedWithException = \false;
    /**
     * @param callable|null  $exceptionHandler A handler that must support \Throwable instances that will be called on Exception
     * @param array|int|null $levels           An array map of E_* to LogLevel::* or an integer bit field of E_* constants
     * @param int|null       $throwAt          Thrown errors in a bit field of E_* constants, or null to keep the current value
     * @param bool           $scream           Enables/disables screaming mode, where even silenced errors are logged
     * @param bool           $scope            Enables/disables scoping mode
     */
    public function __construct(callable $exceptionHandler = null, LoggerInterface $logger = null, $levels = \E_ALL, ?int $throwAt = \E_ALL, bool $scream = \true, bool $scope = \true, LoggerInterface $deprecationLogger = null)
    {
        $handler = \set_exception_handler('is_int');
        $this->earlyHandler = \is_array($handler) ? $handler[0] : null;
        \restore_exception_handler();
        $this->exceptionHandler = null === $exceptionHandler ? null : \Closure::fromCallable($exceptionHandler);
        $this->logger = $logger;
        $this->levels = $levels ?? \E_ALL;
        $this->throwAt = \is_int($throwAt) ? $throwAt : (null === $throwAt ? null : ($throwAt ? \E_ALL : null));
        $this->scream = $scream;
        $this->scope = $scope;
        $this->deprecationLogger = $deprecationLogger;
    }
    /**
     * Configures the error handler.
     */
    public function configure(object $event = null)
    {
        if ($event instanceof ConsoleEvent && !\in_array(\PHP_SAPI, ['cli', 'phpdbg'], \true)) {
            return;
        }
        if (!$event instanceof KernelEvent ? !$this->firstCall : !$event->isMainRequest()) {
            return;
        }
        $this->firstCall = $this->hasTerminatedWithException = \false;
        $handler = \set_exception_handler('is_int');
        $handler = \is_array($handler) ? $handler[0] : null;
        \restore_exception_handler();
        if (!$handler instanceof ErrorHandler) {
            $handler = $this->earlyHandler;
        }
        if ($handler instanceof ErrorHandler) {
            if ($this->logger || $this->deprecationLogger) {
                $this->setDefaultLoggers($handler);
                if (\is_array($this->levels)) {
                    $levels = 0;
                    foreach ($this->levels as $type => $log) {
                        $levels |= $type;
                    }
                } else {
                    $levels = $this->levels;
                }
                if ($this->scream) {
                    $handler->screamAt($levels);
                }
                if ($this->scope) {
                    $handler->scopeAt($levels & ~\E_USER_DEPRECATED & ~\E_DEPRECATED);
                } else {
                    $handler->scopeAt(0, \true);
                }
                $this->logger = $this->deprecationLogger = $this->levels = null;
            }
            if (null !== $this->throwAt) {
                $handler->throwAt($this->throwAt, \true);
            }
        }
        if (!$this->exceptionHandler) {
            if ($event instanceof KernelEvent) {
                if (\method_exists($kernel = $event->getKernel(), 'terminateWithException')) {
                    $request = $event->getRequest();
                    $hasRun =& $this->hasTerminatedWithException;
                    $this->exceptionHandler = static function (\Throwable $e) use($kernel, $request, &$hasRun) {
                        if ($hasRun) {
                            throw $e;
                        }
                        $hasRun = \true;
                        $kernel->terminateWithException($e, $request);
                    };
                }
            } elseif ($event instanceof ConsoleEvent && ($app = $event->getCommand()->getApplication())) {
                $output = $event->getOutput();
                if ($output instanceof ConsoleOutputInterface) {
                    $output = $output->getErrorOutput();
                }
                $this->exceptionHandler = static function (\Throwable $e) use($app, $output) {
                    $app->renderThrowable($e, $output);
                };
            }
        }
        if ($this->exceptionHandler) {
            if ($handler instanceof ErrorHandler) {
                $handler->setExceptionHandler($this->exceptionHandler);
            }
            $this->exceptionHandler = null;
        }
    }
    private function setDefaultLoggers(ErrorHandler $handler) : void
    {
        if (\is_array($this->levels)) {
            $levelsDeprecatedOnly = [];
            $levelsWithoutDeprecated = [];
            foreach ($this->levels as $type => $log) {
                if (\E_DEPRECATED == $type || \E_USER_DEPRECATED == $type) {
                    $levelsDeprecatedOnly[$type] = $log;
                } else {
                    $levelsWithoutDeprecated[$type] = $log;
                }
            }
        } else {
            $levelsDeprecatedOnly = $this->levels & (\E_DEPRECATED | \E_USER_DEPRECATED);
            $levelsWithoutDeprecated = $this->levels & ~\E_DEPRECATED & ~\E_USER_DEPRECATED;
        }
        $defaultLoggerLevels = $this->levels;
        if ($this->deprecationLogger && $levelsDeprecatedOnly) {
            $handler->setDefaultLogger($this->deprecationLogger, $levelsDeprecatedOnly);
            $defaultLoggerLevels = $levelsWithoutDeprecated;
        }
        if ($this->logger && $defaultLoggerLevels) {
            $handler->setDefaultLogger($this->logger, $defaultLoggerLevels);
        }
    }
    public static function getSubscribedEvents() : array
    {
        $events = [KernelEvents::REQUEST => ['configure', 2048]];
        if (\defined('Symfony\\Component\\Console\\ConsoleEvents::COMMAND')) {
            $events[ConsoleEvents::COMMAND] = ['configure', 2048];
        }
        return $events;
    }
}
