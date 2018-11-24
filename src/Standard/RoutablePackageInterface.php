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
namespace Nex\Standard;

use Nex\Standard\Http\RouterInterface;

/**
 * Standardization of a package to extend the access routes to the application.
 * @package Nex\Http
 */
interface RoutablePackageInterface extends PackageInterface
{
    /**
     * Define the access routes used by the package.
     * @param RouterInterface $router
     */
    public function drawRoutes(RouterInterface $router);
}