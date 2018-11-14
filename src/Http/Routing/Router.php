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
 * @copyright (c) 2018 Trevor N. Suarez { https://github.com/klein/klein.php }
 * @copyright (c) 2018 Rareloop { https://github.com/Rareloop/router }
 * @copyright (c) 2018 Sunrise { https://github.com/sunrise-php/http-router }
 * @copyright (c) 2018 Laravel { https://github.com/laravel/framework }
 */
namespace Nex\Http\Routing;

use Nex\Http\Exceptions\MethodNotAllowedHttpException;
use Nex\Http\Exceptions\NotFoundHttpException;
use Nex\Http\Exceptions\RouterHttpException;
use Nex\Standard\Http\RouteCompilerInterface;
use Nex\Standard\Http\RouterInterface;
use Nex\Standard\Injection\InjectorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Router for application access.
 * @package Nex\Http
 */
class Router implements RouterInterface
{
    use Routable;

    /** @var RouteCompilerInterface */
    protected $compiler;
    /** @var InjectorInterface */
    protected $injector;
    /** @var RouteGroup[] */
    protected $groups = array();
    /** @var array */
    protected $middlewares = array();
    /** @var string[] */
    protected $patterns = array(
        '**' => '.+?', ## all
        '*' => '[^/\.]++', ## / beetwen /
        'i' => '[0-9]++', ## int
        'c' => '[A-Za-z]++', ## char
        'a' => '[0-9A-Za-z]++', ## alpha
        'h' => '[0-9A-Fa-f]++', ## hex
        's' => '[0-9A-Za-z-_]++', ## slug
        'y' => '[12][0-9]{3}', ## year
        'm' => '[1-9]|0[1-9]|1[012]', ## month
        'd' => '[1-9]|0[1-9]|[12][0-9]|3[01]' ## day
    );
    /** @var Route[] */
    protected $routes = array();

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * The router.
     * @param InjectorInterface $injector
     */
    public function __construct(InjectorInterface $injector)
    {
        $this->injector = $injector;
        $this->compiler = $injector->has(RouteCompilerInterface::class) ?
            $injector->get(RouteCompilerInterface::class) : new RouteCompiler();
    }

    /**
     * Submit the request to the application.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->findRoute($request);
        foreach ($route->getParameters() as $name => $parameter) {
            $request = $request->withAttribute($name, $parameter);
        }

        $request = $request->withAttribute(Route::class, $route);
        return (new RouteRunner($this->injector, $route->getHandler(), $this->gatherRouteMiddleware($route)))->handle($request);
    }

    /**
     * Get a route instance by its name.
     * @param string $name
     * @return Route|null
     */
    public function getRouteByName(string $name): ?Route
    {
        $routes = $this->getRoutes(function (Route $route) use ($name) {
            return $route->getName() === $name;
        });

        if (empty($routes)) {
            return null;
        }
        return reset($routes);
    }

    /**
     * Gets a set of defined routes.
     * @param \Closure|null $filter
     * @return array
     */
    public function getRoutes(?\Closure $filter = null): array
    {
        if (is_null($filter)) {
            return $this->routes;
        }
        return array_filter($this->routes, $filter, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Define a route group with common attributes.
     * @param array|null $attributes
     * @param \Closure $fn
     */
    public function group(?array $attributes = null, \Closure $fn)
    {
        if (!is_array($attributes)) $attributes = array();

        $group = !empty($this->groups) ? end($this->groups) : new RouteGroup();
        $this->groups[] = $group->withAttributes($attributes);

        $this->injector->execute($fn->bindTo($this), array(
            Router::class => $this
        ));
        array_pop($this->groups);
    }

    /**
     * Set a name for a group of intermediate actions.
     * @param string $name
     * @param array $middlewares
     * @param bool $replace
     * @return Router
     */
    public function groupMiddleware(string $name, array $middlewares, bool $replace = false): self
    {
        if (!array_key_exists($name, $this->middlewares)) {
            $this->middlewares[$name] = array();
        }

        $this->middlewares[$name] = $replace ?
            $middlewares : array_merge($this->middlewares[$name], $middlewares);
        return $this;
    }

    /**
     * Determine if the route collection contains a given named route.
     * @param string $name
     * @return bool
     */
    public function hasNamedRoute(string $name): bool
    {
        return !is_null($this->getRouteByName($name));
    }

    /**
     * Define access routes for specific methods.
     * @param array|string $methods
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function map($methods, string $uri, $action): Route
    {
        if (is_string($methods)) {
            $methods = explode('|', str_replace(array(','), '|', $methods));
        }

        $route = $this->createRoute($methods, $uri, $action);
        foreach ($route->getMethods() as $method) {
            $this->routes[$method.$route->getUri()] = $route;
        }
        return $route;
    }

    /**
     * Create a URL for a named route, if any.
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function url(string $name, array $parameters = []): string
    {
        $route = $this->getRouteByName($name);
        if (is_null($route)) {
            throw new RouterHttpException(sprintf(
                "No such route with name: '%s'.", $name
            ));
        }

        return $this->compiler->reverse($route->getUri(), $parameters);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Create an instance of an access route.
     * @param array $methods
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    protected function createRoute(array $methods, string $uri, $action): Route
    {
        if (empty($this->groups)) {
            return new Route($methods, $uri, $action);
        }

        $group = end($this->groups);
        return $group->createRoute($methods, $uri, $action);
    }

    /**
     * Find the first route that matches the request.
     * @param ServerRequestInterface $request
     * @return Route
     */
    protected function findRoute(ServerRequestInterface $request): Route
    {
        $allowed = array();
        foreach ($this->getSortedRoutes($request) as $route) {
            $regex = $route->getRegex() ?: $route->setRegex(
                $this->compiler->compile($route->getUri(), array_merge($this->patterns, $route->getPatterns()))
            )->getRegex();

            if (!preg_match($regex, rawurldecode($request->getUri()->getPath()), $matches)) {
                continue;
            }

            $allowed = array_unique(array_merge($allowed, $route->getMethods()), SORT_STRING);
            if (!in_array($request->getMethod(), $route->getMethods())) {
                continue;
            }

            $parameters = array_filter($matches, function ($value, $name) {
                return !empty($value) && !is_int($name);
            }, ARRAY_FILTER_USE_BOTH);

            return $route->withParameters($parameters);
        }

        if (!empty($allowed)) {
            throw new MethodNotAllowedHttpException($allowed);
        }
        throw new NotFoundHttpException($request->getUri()->getPath());
    }

    /**
     * Prepare the intermediate actions to be executed.
     * @param Route $route
     * @return array
     */
    protected function gatherRouteMiddleware(Route $route): array
    {
        $middlewares = array();
        foreach ($route->getMiddlewares() as $middleware) {
            $middleware = is_string($middleware) && array_key_exists($middleware, $this->middlewares) ?
                $this->middlewares[$middleware] : array($middleware);

            $middlewares = array_merge($middlewares, $middleware);
        }
        return array_values(array_unique($middlewares, SORT_REGULAR));
    }

    /**
     * Sorts the routes for easy locating.
     * @param ServerRequestInterface $request
     * @return Route[]
     */
    protected function getSortedRoutes(ServerRequestInterface $request): array
    {
        ksort($this->routes);
        $routes = array_filter($this->routes, function ($key) use ($request) {
            return preg_match("/^{$request->getMethod()}/", $key);
        }, ARRAY_FILTER_USE_KEY);

        return array_merge($routes, array_diff_key($this->routes, $routes));
    }
}