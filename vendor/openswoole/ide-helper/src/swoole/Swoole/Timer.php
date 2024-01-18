<?php

declare(strict_types=1);
/**
 * This file is part of OpenSwoole IDE Helper.
 * @link     https://openswoole.com
 * @contact  hello@openswoole.com
 * @license  https://github.com/openswoole/library/blob/master/LICENSE
 */
namespace Swoole;

class Timer
{
    /**
     * @param array $settings [required]
     */
    public static function set(array $settings): bool
    {
    }

    /**
     * @param int $ms [required]
     * @param callable $callback [required]
     * @param mixed ...$params
     */
    public static function after(int $ms, callable $callback, ...$params)
    {
    }

    /**
     * @param int $ms [required]
     * @param callable $callback [required]
     * @param mixed ...$params
     */
    public static function tick(int $ms, callable $callback, ...$params)
    {
    }

    public static function exists(): bool
    {
    }

    public static function info()
    {
    }

    public static function stats(): array
    {
    }

    /**
     * @return \Swoole\Timer\Iterator
     */
    public static function list(): Timer\Iterator
    {
    }

    public static function clear(): bool
    {
    }

    public static function clearAll(): bool
    {
    }
}
