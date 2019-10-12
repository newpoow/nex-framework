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
namespace Nex\Support\Facade;

use Closure;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Support\Facade;

/**
 * Facade for the dependency injector.
 *
 * @method static InjectorInterface alias(string $alias, string $type)
 * @method static InjectorInterface bind(string $id, $concrete = null, bool $shared = false)
 * @method static mixed execute(callable $fn, array $parameters = [])
 * @method static InjectorInterface extend(string $id, Closure $fn)
 * @method static mixed get($id)
 * @method static string getTypeFromAlias(string $alias)
 * @method static bool has($id)
 * @method static InjectorInterface instance(string $id, $instance)
 * @method static bool isShared(string $id)
 * @method static bool isAlias(string $id)
 * @method static mixed make(string $id, array $parameters = [])
 * @method static InjectorInterface singleton(string $id, $concrete = null)
 *
 * @package Nex\Injection
 */
class Injector extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return InjectorInterface::class;
    }
}