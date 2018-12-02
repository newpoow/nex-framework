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

use Nex\Standard\Configuration\ConfiguratorInterface;

/**
 * Standardization of a package to extend the configurations.
 * @package Nex\Configuration
 */
interface ConfigurablePackageInterface extends PackageInterface
{
    /**
     * Define the settings used by the package.
     * @param ConfiguratorInterface $configurator
     */
    public function defineSettings(ConfiguratorInterface $configurator);
}