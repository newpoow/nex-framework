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
namespace Nex\Standard\Injection;

/**
 * Standardization of a parameter resolver.
 * @package Nex\Injection
 */
interface ResolverInterface
{
    /**
     * Get the parameters to be used by a function/method.
     * @param \ReflectionFunctionAbstract $reflected
     * @param array $primitives
     * @return array
     */
    public function resolveParameters(\ReflectionFunctionAbstract $reflected, array $primitives = []): array;
}