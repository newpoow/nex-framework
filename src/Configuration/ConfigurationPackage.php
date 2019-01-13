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
namespace Nex\Configuration;

use Nex\Standard\Configuration\ConfiguratorInterface;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Standard\PackageInterface;

/**
 * Package to provide configuration management.
 * @package Nex\Configuration
 */
class ConfigurationPackage implements PackageInterface
{
    /**
     * Register services to the dependency injector.
     * @param InjectorInterface $injector
     */
    public function registerServices(InjectorInterface $injector)
    {
        $injector->alias('configurator', ConfiguratorInterface::class);
        $injector->singleton(ConfiguratorInterface::class, function (InjectorInterface $injector) {
            $configurator = new Configurator();
            $configurator
                ->addParser(new Parsers\JsonParser(), ['json'])
                ->addParser(new Parsers\PhpParser($injector), ['php']);

            return $configurator;
        });
    }
}