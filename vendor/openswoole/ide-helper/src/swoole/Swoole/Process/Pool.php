<?php

declare(strict_types=1);
/**
 * This file is part of OpenSwoole IDE Helper.
 * @link     https://openswoole.com
 * @contact  hello@openswoole.com
 * @license  https://github.com/openswoole/library/blob/master/LICENSE
 */
namespace Swoole\Process;

class Pool
{
    public $master_pid;

    public $workers;

    /**
     * @param int $workerNum [required]
     * @param int $ipcType [optional] = \SWOOLE_IPC_NONE
     * @param int $msgqueue_key [optional] = 0
     * @param bool $enableCoroutine [optional] = false
     */
    public function __construct(int $workerNum, int $ipcType = \SWOOLE_IPC_NONE, int $msgqueue_key = 0, bool $enableCoroutine = false)
    {
    }

    public function __destruct()
    {
    }

    /**
     * @param array $settings [required]
     * @return ?bool
     */
    public function set(array $settings): ?bool
    {
    }

    /**
     * @param string $event [required]
     * @param callable $callback [required]
     */
    public function on(string $event, callable $callback): bool
    {
    }

    /**
     * @param int $workerId [optional] = -1
     */
    public function getProcess(int $workerId = -1)
    {
    }

    /**
     * @param string $host [required]
     * @param int $port [optional] = 0
     * @param int $backlog [optional] = 2048
     */
    public function listen(string $host, int $port = 0, int $backlog = 2048): bool
    {
    }

    /**
     * @param string $data [required]
     */
    public function write(string $data): bool
    {
    }

    public function detach(): bool
    {
    }

    /**
     * @return ?bool
     */
    public function start(): ?bool
    {
    }

    public function stop(): void
    {
    }

    public function shutdown(): bool
    {
    }
}
