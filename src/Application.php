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
namespace Nex;

use Nex\Injection\Injector;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Support\Facade;
use Psr\Container\ContainerInterface;

/**
 * Core of the application.
 * @package Nex
 */
abstract class Application
{
    /** @var InjectorInterface */
    private $injector;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Application constructor.
     * @param null|ContainerInterface $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->injector = new Injector($container);
        $this->bindAndRun($this->injector, function () {
            /** @var InjectorInterface $injector */
            $injector = $this;
            $injector->instance(InjectorInterface::class, $injector);

            Facade::setFacadeContainer($injector);
        });
    }

    /**
     * Configure the application.
     * @param \Closure $fn
     * @return mixed
     */
    public function configure(\Closure $fn)
    {
        return $this->bindAndRun($this->injector, $fn);
    }

    /**
     * Get the dependencies injector.
     * @return InjectorInterface
     */
    public function getInjector(): InjectorInterface
    {
        return $this->injector;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Performs a function by including an object within its scope.
     * @param \object|string $scope
     * @param \Closure $fn
     * @param array $parameters
     * @return mixed
     */
    protected function bindAndRun($scope, \Closure $fn, array $parameters = [])
    {
        if (is_object($scope) || is_string($scope)) {
            $fn = $fn->bindTo(is_object($scope) ? $scope : $this->injector->get($scope));
        }
        return $this->injector->execute($fn, $parameters);
    }

    /**
     * Initialize the settings defined in the application.
     */
    protected function initialize()
    {
        foreach ($this->getAwareTraits() as $trait) {
            $method = $this->getInitMethodFromTrait($trait);
            if (is_callable([$this, $method])) {
                call_user_func([$this, $method], $this->injector);
            }
        }
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PRIVATE METHODS                 ##
    ##----------------------------------------------##
    /**
     * Get the knowledge included in the app extends
     * @return array
     */
    private function getAwareTraits(): array
    {
        $traits = array();
        $current = $this;
        do {
            $traits = array_merge(array_diff(
                class_uses($current, true), $traits
            ), $traits);
        } while ($current = get_parent_class($current));
        return $traits;
    }

    /**
     * Get the trait initialization method name.
     * @param string $trait
     * @return string
     */
    private function getInitMethodFromTrait(string $trait): string
    {
        if (($position = strrpos($trait, '\\')) !== false) {
            $trait = substr($trait, $position + 1);
        }

        if (($length = strrpos($trait, 'AwareTrait')) === false) {
            return $trait;
        }
        return substr($trait, 0, $length);
    }
}