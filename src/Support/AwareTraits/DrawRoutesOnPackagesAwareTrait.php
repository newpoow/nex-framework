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

use Closure;
use Nex\Standard\Http\RouterInterface;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Standard\RoutablePackageInterface;

/**
 * Provides knowledge to define the routes in the packages.
 * @package Nex\Http
 */
trait DrawRoutesOnPackagesAwareTrait
{
    /**
     * Get the packages registered in the application.
     * @param Closure|null $filter
     * @return array
     */
    abstract public function getPackages(?Closure $filter = null): array;

    /**
     * Define the package access routes.
     * @param InjectorInterface $injector
     */
    protected function drawRoutesOnPackages(InjectorInterface $injector)
    {
        foreach ($this->getPackages(function ($package) {
            return $package instanceof RoutablePackageInterface;
        }) as $package) {
            /** @var $package RoutablePackageInterface */
            $package->drawRoutes($injector->get(RouterInterface::class));
        }
    }
}