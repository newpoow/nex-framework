<?php /** @noinspection PhpIncludeInspection */
declare(strict_types=1);
/**
 * This file is part of the "Nex Framework" software,
 * A simple and efficient web framework written with PHP.
 *
 * For complete copyright and license information,
 * see the LICENSE file that was distributed with this source code.
 *
 * @license MIT
 * @author Ney Pinheiro
 * @copyright (c) 2020 Nex Framework { https://github.com/newpoow/nex-framework }
 */
namespace Nex\Support\AwareTraits;

use Closure;
use Nex\Filesystem\Finder;

/**
 * Provides knowledge to define the routes in the application.
 * @package Nex\Http
 */
trait DrawRoutesOnApplicationAwareTrait
{
    /**
     * Define the application access routes.
     * @param Closure $fn
     * @return mixed
     */
    abstract public function drawRoutes(Closure $fn);

    /**
     * Path where routes files should be found.
     * @return string
     */
    abstract public function getRoutesPath(): string;

    /**
     * Define the application access routes.
     */
    protected function drawRoutesOnApplication()
    {
        $routesPath = $this->getRoutesPath();
        if (empty($routesPath)) return;

        $this->drawRoutes(function () use ($routesPath) {
            if (is_file($routesPath)) {
                require_once $routesPath;
            } else {
                foreach (Finder::create()->files()->name('*.php')->in($routesPath) as $file) {
                    require_once $file->getRealPath();
                }
            }
        });
    }
}