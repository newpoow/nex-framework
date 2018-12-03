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

use Nex\Standard\ConfigurablePackageInterface;
use Nex\Standard\Configuration\ConfiguratorInterface;

/**
 * Provides knowledge to load configurations into packages.
 * @package Nex\Configuration
 */
trait LoadSettingsFromPackagesAwareTrait
{
    use RegisterPackagesAwareTrait;

    /**
     * Load the settings defined in the packages.
     */
    protected function loadSettingsFromPackages()
    {
        foreach ($this->getPackages(function ($package) {
            return $package instanceof ConfigurablePackageInterface;
        }) as $package) {
            /** @var $package ConfigurablePackageInterface */
            $package->defineSettings($this->getInjector()->get(ConfiguratorInterface::class));
        }
    }
}