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

/**
 * Standardization of an extension package with dependencies.
 * @package Nex
 */
interface DependsOnPackageInterface extends PackageInterface
{
    /**
     * Get package dependencies.
     * @return array
     */
    public function getDependencies(): array;
}