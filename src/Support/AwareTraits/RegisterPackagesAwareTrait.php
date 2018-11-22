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
namespace Nex\Support\AwareTraits;

use Nex\Standard\Injection\InjectorInterface;
use Nex\Standard\PackageInterface;

/**
 * Provides knowledge to register packages.
 * @package Nex
 */
trait RegisterPackagesAwareTrait
{
    /** @var PackageInterface[] */
    private $packages = array();

    /**
     * Get the dependencies injector.
     * @return InjectorInterface
     */
    abstract public function getInjector(): InjectorInterface;

    /**
     * Add a package to the application.
     * @param PackageInterface|string $package
     * @param bool $replace
     * @return PackageInterface
     */
    public function addPackage($package, bool $replace = false): PackageInterface
    {
        $package = is_object($package) ? $package : $this->getInjector()->get($package);
        if (!$package instanceof PackageInterface) {
            throw new \InvalidArgumentException(sprintf(
                "The given package is not a instance of '%s'", PackageInterface::class
            ));
        }

        $namePackage = get_class($package);
        if (!array_key_exists($namePackage, $this->packages) || $replace) {
            $this->packages[$namePackage] = $package;
            $package->registerServices($this->getInjector());
        }
        return $this->packages[$namePackage];
    }

    /**
     * Get the packages registered in the application.
     * @param \Closure|null $filter
     * @return array
     */
    public function getPackages(?\Closure $filter = null): array
    {
        if (is_null($filter)) {
            return $this->packages;
        }
        return array_filter($this->packages, $filter, ARRAY_FILTER_USE_BOTH);
    }
}