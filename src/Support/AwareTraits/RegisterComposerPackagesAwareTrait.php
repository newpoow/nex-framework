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
 * @copyright (c) 2020 Nex Framework { https://github.com/newpoow/nex-framework }
 */
namespace Nex\Support\AwareTraits;

use Nex\Configuration\Parsers\JsonParser;
use RuntimeException;

/**
 * Provides knowledge to register packages loaded by the composer.
 * @package Nex
 */
trait RegisterComposerPackagesAwareTrait
{
    /**
     * Add multiple packages to the application.
     * @param mixed ...$packages
     */
    abstract public function addPackages(...$packages);

    /**
     * Path where the libraries loaded by the composer are to be found.
     * @return string
     */
    abstract public function getComposerVendorPath(): string;

    /**
     * Path where the composer's file is located.
     * @return string
     */
    public function getComposerFilePath(): string
    {
        return dirname($this->getComposerVendorPath()).DIRECTORY_SEPARATOR.'composer.json';
    }

    /**
     * Register packages loaded by the composer.
     */
    protected function registerComposerPackages()
    {
        $vendor = $this->getComposerVendorPath();
        if (!is_dir($vendor)) {
            throw new RuntimeException("Missing vendor files, try running 'composer install'.");
        }

        $installed = (new JsonParser())->parse($vendor.'/composer/installed.json');
        if (isset($installed['packages'])) {
            $installed = $installed['packages'];
        }

        $packages = array();
        foreach ($installed as $pkg) {
            if (isset($pkg['extra']['nex-framework'])) {
                $packages = array_merge($packages, $pkg['extra']['nex-framework']['packages'] ?? []);
            }
        }

        $this->addPackages(array_diff($packages, $this->packagesToIgnore()));
    }

    /**
     * Get all of the package names that should be ignored.
     * @return array
     */
    protected function packagesToIgnore(): array
    {
        $composer = (new JsonParser())->parse($this->getComposerFilePath());
        return $composer['extra']['nex-framework']['ignore'] ?? [];
    }
}