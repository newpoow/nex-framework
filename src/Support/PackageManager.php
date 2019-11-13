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
namespace Nex\Support;

use Closure;
use InvalidArgumentException;
use Nex\Standard\DependsOnPackageInterface;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Standard\PackageInterface;

/**
 * Package manager for application extension.
 * @package Nex
 */
final class PackageManager
{
    /** @var InjectorInterface */
    protected $injector;
    /** @var PackageInterface[] */
    protected $packages = array();

    /**
     * PackageManager constructor.
     * @param InjectorInterface $injector
     */
    public function __construct(InjectorInterface $injector)
    {
        $this->injector = $injector;
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

            if ($package instanceof DependsOnPackageInterface) {
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
}