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
use Nex\Standard\Injection\InjectorInterface;

/**
 * Provides knowledge to initialize packages.
 * @package Nex
 */
trait BootPackagesAwareTrait
{
    /**
     * Get the packages registered in the application.
     * @param Closure|null $filter
     * @return array
     */
    abstract public function getPackages(?Closure $filter = null): array;

    /**
     * Initialize the packages registered in the application.
     * @param InjectorInterface $injector
     */
    protected function bootPackages(InjectorInterface $injector)
    {
        foreach ($this->getPackages(function ($package) {
            return method_exists($package, 'boot');
        }) as $package) {
            $injector->execute([$package, 'boot']);
        }
    }
}