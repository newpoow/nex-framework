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
 * @copyright (c) 2018 Laravel { https://github.com/laravel/framework }
 */
namespace Nex\Support;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Implements base functionality for static "facade" classes.
 * @package Nex\Support
 */
abstract class Facade
{
    /** @var ContainerInterface */
    protected static $container;

    /**
     * Get the registered name of the component.
     * @return string
     */
    abstract protected static function getFacadeAccessor(): string;

    /**
     * Get the root object behind the facade.
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Set the container instance.
     * @param ContainerInterface $container
     */
    public static function setFacadeContainer(ContainerInterface $container)
    {
        static::$container = $container;
    }

    /**
     * Resolve the facade root instance from the container.
     * @param string $name
     * @return mixed
     */
    protected static function resolveFacadeInstance(string $name)
    {
        return static::$container->get($name);
    }

    /**
     * Handle dynamic, static calls to the object.
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $instance = static::getFacadeRoot();
        if (!$instance) {
            throw new RuntimeException('A facade root has not been set.');
        }
        return $instance->{$name}(...$arguments);
    }
}