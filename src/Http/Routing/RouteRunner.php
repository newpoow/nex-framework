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
namespace Nex\Http\Routing;

use Nex\Http\Dispatcher;
use Nex\Http\Exceptions\RouterException;
use Nex\Standard\Injection\InjectorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Executor of access routes and its intermediate actions.
 * @package Nex\Http
 */
class RouteRunner
{
    /** @var array */
    protected $aliases = array();
    /** @var InjectorInterface */
    protected $injector;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Route executor.
     * @param InjectorInterface $injector
     */
    public function __construct(InjectorInterface $injector)
    {
        $this->injector = $injector;
    }

    /**
     * Set a name for a group of intermediate actions.
     * @param string $alias
     * @param array $middlewares
     * @param bool $replace
     * @return static
     */
    public function aliasedMiddleware(string $alias, array $middlewares, bool $replace = false): self
    {
        if (!array_key_exists($alias, $this->aliases)) {
            $this->aliases[$alias] = array();
        }

        $this->aliases[$alias] = $replace ? $middlewares : array_merge($this->aliases[$alias], $middlewares);
        return $this;
    }

    /**
     * Performs the action defined on the route.
     * @param Route $route
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function run(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $dispatcher = new Dispatcher(function (ServerRequestInterface $request) use ($route) {
            $this->injector->instance(ServerRequestInterface::class, $request);

            return $route->getHandler()->handle($request);
        });

        $dispatcher->addMiddlewares($this->gatherRouteMiddleware($route));
        return $dispatcher->handle($request);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Prepare the intermediate actions to be executed.
     * @param Route $route
     * @return array
     */
    protected function gatherRouteMiddleware(Route $route): array
    {
        $middlewares = array();
        foreach ($route->getMiddlewares() as $middleware) {
            if (is_string($middleware)) {
                if (array_key_exists($middleware, $this->aliases)) {
                    $middleware = $this->aliases[$middleware];
                } elseif (!class_exists($middleware)) {
                    throw new RouterException(sprintf(
                        "No middleware groups were found with the name: '%s'.", $middleware
                    ), 500);
                }
            } elseif (is_callable($middleware)) {
                $middleware = array($middleware);
            }
            $middlewares = array_merge($middlewares, is_array($middleware) ? $middleware : array($middleware));
        }
        return array_values(array_unique($middlewares, SORT_REGULAR));
    }
}