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
namespace Nex\Injection;

use Nex\Injection\Exceptions\ContainerException;
use Nex\Injection\Exceptions\EntryNotFoundException;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Standard\Injection\ResolverInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Dependency Injector.
 * @package Nex\Injection
 */
class Injector implements \ArrayAccess, InjectorInterface
{
    /** @var string[] */
    protected $aliases = array();
    /** @var null|ContainerInterface */
    protected $container;
    /** @var array */
    protected $definitions = array();
    /** @var array */
    protected $instances = array();
    /** @var ResolverInterface */
    protected $resolver;
    /** @var string[] */
    protected $resolving = array();

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Dependency Injector.
     * @param null|ContainerInterface $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
        if ($container && $container->has(ResolverInterface::class)) {
            $this->resolver = $container->get(ResolverInterface::class);
        } else {
            $this->resolver = new Resolver($this);
        }
    }

    /**
     * Define an alias for a particular type.
     * @param string $alias
     * @param string $type
     * @return InjectorInterface
     */
    public function alias(string $alias, string $type): InjectorInterface
    {
        if ($alias === $type) {
            throw new ContainerException(sprintf(
                "The alias '%s' is aliased to itself.", $alias
            ));
        }

        $this->aliases[$alias] = $type;
        return $this;
    }

    /**
     * Set a resolution for a particular type.
     * @param string $id
     * @param mixed $concrete
     * @param bool $shared
     * @return InjectorInterface
     */
    public function bind(string $id, $concrete = null, bool $shared = false): InjectorInterface
    {
        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }

        $concrete = is_null($concrete) ? $id : $concrete;
        if (!$concrete instanceof \Closure) {
            $concrete = function () use ($concrete) {
                if (is_string($concrete)) {
                    return $this->build($concrete);
                }
                return $concrete;
            };
        }

        $this->definitions[$id] = compact('concrete', 'shared');
        return $this;
    }

    /**
     * Execute a function/method and inject its dependencies.
     * @param callable $fn
     * @param array $parameters
     * @return mixed
     */
    public function execute(callable $fn, array $parameters = [])
    {
        try {
            if (is_array($fn)) {
                list($class, $method) = $fn;
                $reflected = new \ReflectionMethod($class, $method);
            } else if (is_object($fn)) {
                $reflected = new \ReflectionMethod($fn, '__invoke');
            } else {
                $reflected = new \ReflectionFunction($fn);
            }

            return call_user_func_array($fn, $this->resolver->resolveParameters(
                $reflected, $parameters
            ));
        } catch (\ReflectionException $exception) {
            throw new ContainerException(
                $exception->getMessage(), $exception->getCode(), $exception->getPrevious()
            );
        }
    }

    /**
     * Extend an existing definition of an already defined type.
     * @param string $id
     * @param \Closure $fn
     * @return InjectorInterface
     */
    public function extend(string $id, \Closure $fn): InjectorInterface
    {
        $id = $this->getTypeFromAlias($id);
        if (!(isset($this->instances[$id]) || isset($this->definitions[$id]))) {
            throw new EntryNotFoundException(sprintf(
                "The identifier '%s' is not yet defined in the dependency injector.", $id
            ));
        }

        if (isset($this->definitions[$id])) {
            $def = $this->definitions[$id];

            $this->bind($id, function () use ($fn, $def) {
                return call_user_func($fn, $this->execute($def['concrete']));
            }, $def['shared']);
        }

        if (isset($this->instances[$id])) {
            $this->instances[$id] = call_user_func($fn, $this->instances[$id]);
        }
        return $this;
    }

    /**
     * Get an element resolved by its identifier.
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        try {
            if (is_null($this->container)) {
                throw new EntryNotFoundException(sprintf(
                    "No entry was found for '%s' identifier in the container.", $id
                ));
            }
            return $this->container->get($id);
        } catch (NotFoundExceptionInterface $exception) {
            if ($this->isResolvable($id)) {
                return $this->make($id);
            }
            throw $exception;
        }
    }

    /**
     * Get a type from your alias, if any.
     * @param string $alias
     * @return string
     */
    public function getTypeFromAlias(string $alias): string
    {
        $aliases = array();
        while (isset($this->aliases[$alias])) {
            $aliases[] = $alias;
            if (in_array($alias = $this->aliases[$alias], $aliases)) {
                throw new ContainerException(sprintf(
                    "The alias '%s' contains a circular entry.", $alias
                ));
            }
        }
        return $alias;
    }

    /**
     * Checks if the given type can be resolved.
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return $this->container && $this->container->has($id) ?: $this->isResolvable($id);
    }

    /**
     * Define an existing instance as a resolved type.
     * @param string $id
     * @param mixed $instance
     * @return InjectorInterface
     */
    public function instance(string $id, $instance): InjectorInterface
    {
        if (isset($this->aliases[$id])) {
            unset($this->aliases[$id]);
        }

        $this->instances[$id] = $instance;
        return $this;
    }

    /**
     * Determine if a given type is shared.
     * @param string $id
     * @return bool
     */
    public function isShared(string $id): bool
    {
        return isset($this->instances[$id]) ||
            (isset($this->definitions[$id]['shared']) && $this->definitions[$id]['shared'] === true);
    }

    /**
     * Determine if a given string is an alias.
     * @param string $id
     * @return bool
     */
    public function isAlias(string $id): bool
    {
        return isset($this->aliases[$id]);
    }

    /**
     * Solve a provided type.
     * @param string $id
     * @param array $parameters
     * @return mixed
     */
    public function make(string $id, array $parameters = [])
    {
        $id = $this->getTypeFromAlias($id);
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $def = $this->definitions[$id];

            if ($def['shared'] === true) {
                return $this->instances[$id] = $this->execute($def['concrete'], $parameters);
            }
            return $this->execute($def['concrete'], $parameters);
        }

        return $this->build($id, $parameters);
    }

    /**
     * Set a resolution for a given type as a single shared solution.
     * @param string $id
     * @param mixed $concrete
     * @return InjectorInterface
     */
    public function singleton(string $id, $concrete = null): InjectorInterface
    {
        return $this->bind($id, $concrete, true);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Build a concrete instance of the type.
     * @param string $id
     * @param array $parameters
     * @return mixed
     */
    protected function build(string $id, array $parameters = [])
    {
        if (isset($this->resolving[$id])) {
            throw new ContainerException(sprintf(
                "Circular dependency detected while trying to resolve entry '%s'.", $id
            ));
        }
        $this->resolving[$id] = true;

        try {
            $reflected = new \ReflectionClass($id);
            if (!$reflected->isInstantiable()) {
                throw new ContainerException(sprintf(
                    "Target '%s' is not instantiable.", $id
                ));
            }

            $constructor = $reflected->getConstructor();
            if (is_null($constructor)) {
                return $reflected->newInstance();
            }

            return $reflected->newInstanceArgs(
                $this->resolver->resolveParameters($constructor, $parameters)
            );
        } catch (\ReflectionException $exception) {
            throw new ContainerException(
                $exception->getMessage(), $exception->getCode(), $exception->getPrevious()
            );
        } finally {
            unset($this->resolving[$id]);
        }
    }

    /**
     * Check that the injector can resolve an informed type.
     * @param string $id
     * @return bool
     */
    protected function isResolvable(string $id): bool
    {
        $id = $this->getTypeFromAlias($id);
        return isset($this->instances[$id]) || isset($this->definitions[$id]) || class_exists($id);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              ARRAYACCESS METHODS             ##
    ##----------------------------------------------##
    /**
     * Checks whether the given type can be constructed using array syntax.
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get an element resolved by its identifier, using array syntax.
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set a resolution for a particular type using array syntax.
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->bind($offset, $value, true);
    }

    /**
     * Remove a resolved element using array syntax.
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->instances[$offset]);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##               MAGIC METHODS                  ##
    ##----------------------------------------------##
    /**
     * Get an element resolved by its identifier, dynamically.
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * Set a resolution for a particular type, dynamically.
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }
}