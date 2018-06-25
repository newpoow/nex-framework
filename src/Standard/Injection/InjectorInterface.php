<?php declare(strict_types=1);
/**
 * This file is part of the "Nex Framework" software,
 * A simple and efficient web framework written with PHP.
 *
 * For complete copyright and license information,
 * see the LICENSE file that was distributed with this source code.
 *
 * @license MIT
 * @author Ney Pinheiro
 * @copyright (c) 2019 Nex Framework { https://github.com/newpoow/nex-framework }
 */
namespace Nex\Standard\Injection;

use Psr\Container\ContainerInterface;

/**
 * Standardization of a dependency injector.
 * @package Nex\Injection
 */
interface InjectorInterface extends ContainerInterface
{
    /**
     * Define an alias for a particular type.
     * @param string $alias
     * @param string $type
     * @return InjectorInterface
     */
    public function alias(string $alias, string $type): InjectorInterface;

    /**
     * Set a resolution for a particular type.
     * @param string $id
     * @param mixed $concrete
     * @param bool $shared
     * @return InjectorInterface
     */
    public function bind(string $id, $concrete = null, bool $shared = false): InjectorInterface;

    /**
     * Execute a function/method and inject its dependencies.
     * @param callable $fn
     * @param array $parameters
     * @return mixed
     */
    public function execute(callable $fn, array $parameters = []);

    /**
     * Extend an existing definition of an already defined type.
     * @param string $id
     * @param \Closure $fn
     * @return InjectorInterface
     */
    public function extend(string $id, \Closure $fn): InjectorInterface;

    /**
     * Get a type from your alias, if any.
     * @param string $alias
     * @return string
     */
    public function getTypeFromAlias(string $alias): string;

    /**
     * Define an existing instance as a resolved type.
     * @param string $id
     * @param mixed $instance
     * @return InjectorInterface
     */
    public function instance(string $id, $instance): InjectorInterface;

    /**
     * Determine if a given type is shared.
     * @param string $id
     * @return bool
     */
    public function isShared(string $id): bool;

    /**
     * Determine if a given string is an alias.
     * @param string $id
     * @return bool
     */
    public function isAlias(string $id): bool;

    /**
     * Solve a provided type.
     * @param string $id
     * @param array $parameters
     * @return mixed
     */
    public function make(string $id, array $parameters = []);

    /**
     * Set a resolution for a given type as a single shared solution.
     * @param string $id
     * @param mixed $concrete
     * @return InjectorInterface
     */
    public function singleton(string $id, $concrete = null): InjectorInterface;
}