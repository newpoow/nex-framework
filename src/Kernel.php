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

use Closure;
use InvalidArgumentException;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Standard\PackageInterface;
use Nex\Support\Facade;

/**
 * Core of the application with package management.
 * @package Nex
 */
abstract class Kernel
{
    /** @var InjectorInterface */
    private $injector;
    /** @var PackageInterface[] */
    private $packages = array();

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Application constructor.
     * @param InjectorInterface $injector
     */
    public function __construct(InjectorInterface $injector)
    {
        $this->injector = $injector;
        $this->bindAndRun($this->injector, function () {
            /** @var $injector InjectorInterface */
            $injector = $this;
            $injector->instance(InjectorInterface::class, $injector);

            Facade::setFacadeContainer($injector);
        });
    }

    /**
     * Add a package to the application.
     * @param PackageInterface $package
     * @param bool $replace
     * @return static
     */
    public function addPackage(PackageInterface $package, bool $replace = false): self
    {
        $namePackage = get_class($package);
        if (!array_key_exists($namePackage, $this->packages) || $replace) {
            $this->packages[$namePackage] = $package;

            if (method_exists($package, 'getDependencies')) {
                $this->addPackages($package->getDependencies());
            }
            $package->registerServices($this->injector);
        }
        return $this;
    }

    /**
     * Add multiple packages to the application.
     * @param mixed ...$packages
     * @return static
     */
    public function addPackages(...$packages): self
    {
        $packages = isset($packages[0]) && is_array($packages[0]) ? $packages[0] : $packages;
        foreach ($packages as $package => $options) {
            if (!is_string($package)) {
                $package = is_object($options) ? $options : $this->injector->get($options);
            } else {
                $package = $this->injector->make($package, $options);
            }

            if (!$package instanceof PackageInterface) {
                $type = is_object($package) ? get_class($package) : gettype($package);

                throw new InvalidArgumentException(sprintf(
                    "The given package '%s' is not a instance of %s.", $type, PackageInterface::class
                ));
            }
            $this->addPackage($package);
        }
        return $this;
    }

    /**
     * Configure the application.
     * @param Closure $fn
     * @return mixed
     */
    public function configure(Closure $fn)
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

    /**
     * Get the packages registered in the application.
     * @param Closure|null $filter
     * @return array
     */
    public function getPackages(?Closure $filter = null): array
    {
        if (is_null($filter)) {
            return $this->packages;
        }
        return array_filter($this->packages, $filter, ARRAY_FILTER_USE_BOTH);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Performs a function by including an object within its scope.
     * @param mixed $scope
     * @param Closure $fn
     * @param array $parameters
     * @return mixed
     */
    protected function bindAndRun($scope, Closure $fn, array $parameters = [])
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
        foreach ($this->getAwareTraits() as $aware) {
            $method = lcfirst($this->getInitMethodFromTrait($aware));
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