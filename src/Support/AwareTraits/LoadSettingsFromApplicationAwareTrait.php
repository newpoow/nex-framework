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
use Nex\Filesystem\Finder;
use Nex\Standard\Configuration\ConfiguratorInterface;

/**
 * Provides knowledge to load configurations into application.
 * @package Nex\Configuration
 */
trait LoadSettingsFromApplicationAwareTrait
{
    /**
     * Configure the application.
     * @param Closure $fn
     * @return mixed
     */
    abstract public function configure(Closure $fn);

    /**
     * Path where configuration files should be found.
     * @return string
     */
    abstract public function getConfigurationPath(): string;

    /**
     * Load the settings defined in the application.
     */
    protected function loadSettingsFromApplication()
    {
        $configurationPath = $this->getConfigurationPath();
        if (empty($configurationPath)) return;

        $this->configure(function (ConfiguratorInterface $configurator) use ($configurationPath) {
            foreach (Finder::create()->files()->in($configurationPath) as $file) {
                $path = str_replace(DIRECTORY_SEPARATOR, '.', trim(
                    str_replace($configurationPath, '', $file->getPath()), DIRECTORY_SEPARATOR
                ));

                $configurator->load([$path => $file->getRealPath()]);
            }
        });
    }
}