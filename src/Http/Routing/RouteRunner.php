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

use Closure;
use JsonSerializable;
use Nex\Http\Dispatcher;
use Nex\Http\Exceptions\RouterException;
use Nex\Http\Message\Response;
use Nex\Http\Response\JsonResponse;
use Nex\Standard\Injection\InjectorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Executor of access routes and its intermediate actions.
 * @package Nex\Http
 */
class RouteRunner extends Dispatcher
{
    /** @var InjectorInterface */
    protected $injector;
    /** @var callable */
    protected $handler;
    /** @var array */
    protected $groupMiddlewares = array();

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

        parent::__construct($this);
    }

    /**
     * Set a name for a group of intermediate actions.
     * @param string $name
     * @param array $middlewares
     * @param bool $replace
     * @return static
     */
    public function groupMiddleware(string $name, array $middlewares, bool $replace = false): self
    {
        if (!array_key_exists($name, $this->groupMiddlewares)) {
            $this->groupMiddlewares[$name] = array();
        }

        $this->groupMiddlewares[$name] = $replace ?
            $middlewares : array_merge($this->groupMiddlewares[$name], $middlewares);
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
        $this->handler = $this->prepareHandler($route->getHandler());

        return $this->addMiddlewares($this->gatherRouteMiddleware($route))->handle($request);
    }

    /**
     * Performs the action defined in the access route.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if (!is_callable($this->handler)) {
            throw new RouterException(sprintf(
                "The action for the path '%s' has not been defined.", $request->getUri()->getPath()
            ));
        }

        $this->injector->instance(ServerRequestInterface::class, $request);

        return $this->prepareResponse($this->injector->execute(
            $this->handler, $request->getAttributes()
        ));
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
            $middleware = is_string($middleware) && array_key_exists($middleware, $this->groupMiddlewares) ?
                $this->groupMiddlewares[$middleware] : array($middleware);

            $middlewares = array_merge($middlewares, $middleware);
        }
        return array_values(array_unique($middlewares, SORT_REGULAR));
    }

    /**
     * Prepare the action to be performed.
     * @param callable|array $handler
     * @return callable
     */
    protected function prepareHandler($handler): callable
    {
        if (is_array($handler)) {
            $controller = $handler['controller'];
            $method = $handler['method'];

            if (is_string($controller)) {
                if (!$this->injector->has($controller)) {
                    throw new RouterException(sprintf(
                        "The controller class '%s' has not been defined.", $controller
                    ));
                }
                $controller = $this->injector->make($controller);
            }

            if (!method_exists($controller, $method)) {
                throw new RouterException(sprintf(
                    "The controller class '%s' does not have a '%s' method.",
                    get_class($controller), $method
                ));
            }
            return array($controller, $method);
        }

        return $handler instanceof Closure ? $handler->bindTo($this->injector) : $handler;
    }

    /**
     * Create a response from the provided value.
     * @param mixed $content
     * @return ResponseInterface
     */
    protected function prepareResponse($content): ResponseInterface
    {
        if ($content instanceof ResponseInterface) {
            return $content;
        }

        if (is_array($content) || $content instanceof JsonSerializable) {
            return new JsonResponse($content);
        }

        $content = is_resource($content) ? $content : strval($content);
        return new Response($content, 200, ['Content-Type' => 'text/html']);
    }
}