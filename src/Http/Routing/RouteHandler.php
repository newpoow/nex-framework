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
namespace Nex\Http\Routing;

use Closure;
use JsonSerializable;
use Nex\Http\Exceptions\RouterException;
use Nex\Http\Message\Response;
use Nex\Http\Response\JsonResponse;
use Nex\Standard\Injection\InjectorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Handler of the action defined in the access route.
 * @package Nex\Http
 */
class RouteHandler implements RequestHandlerInterface
{
    /** @var callable|array */
    protected $action;
    /** @var InjectorInterface */
    protected $injector;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Route Handler constructor.
     * @param InjectorInterface $injector
     * @param RequestHandlerInterface|callable|string|array $action
     */
    public function __construct(InjectorInterface $injector, $action)
    {
        $this->injector = $injector;
        $this->setAction($action);
    }

    /**
     * Performs the action defined by the access route.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        ob_start();
        $level = ob_get_level();

        try {
            $response = $this->prepareResponse(
                $this->injector->execute($this->getAction(), $request->getAttributes())
            );

            $content = '';
            while (ob_get_level() >= $level) {
                $content = ob_get_clean().$content;
            }
            $response->getBody()->write($content);
            return $response;
        } catch (Throwable $exception) {
            while (ob_get_level() >= $level) {
                ob_end_clean();
            }
            throw new RouterException($exception->getMessage(), 500, $exception);
        }
    }

    /**
     * Prepare the action to be performed.
     * @return callable
     */
    public function getAction(): callable
    {
        $action = $this->action;
        if (is_array($action)) {
            $controller = $action['controller'];
            $method = $action['method'];

            if (is_string($controller)) {
                if (!$this->injector->has($controller)) {
                    throw new RouterException(sprintf(
                        "The controller class '%s' has not been defined.", trim($controller, '\\')
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
        return $action instanceof Closure ? $action->bindTo($this->injector) : $action;
    }

    /**
     * Prepend the last group namespace onto the action.
     * @param string $namespace
     * @return RouteHandler
     */
    public function withPrependedNamespace(string $namespace): RouteHandler
    {
        $handler = $this->action;
        $cloned = $this;

        if (is_array($handler) && isset($handler['controller']) && is_string($controller = $handler['controller'])) {
            if (strpos($controller, '\\') !== 0) {
                $controller = $namespace . '\\' . $controller;
            }
            $handler['controller'] = $controller;
            $cloned->action = $handler;
        }
        return $cloned;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Create a response from the provided value.
     * @param mixed $response
     * @return ResponseInterface
     */
    protected function prepareResponse($response): ResponseInterface
    {
        if ($response instanceof ResponseInterface) {
            return $response;
        }

        if (is_array($response) || $response instanceof JsonSerializable) {
            return new JsonResponse($response);
        }

        $response = is_resource($response) ? $response : strval($response);
        return new Response($response, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Defines the action to take when the route is matched.
     * @param RequestHandlerInterface|callable|string|array $action
     * @return static
     */
    protected function setAction($action): self
    {
        if (!is_callable($action) && is_string($action)) {
            $action = explode('@', str_replace(array(':'), '@', $action), 2);
            if (count($action) == 1) {
                $action = array_merge($action, array('__invoke'));
            }
        }

        if ((!is_callable($action) && !is_array($action)) || (is_array($action) && count($action) < 2)) {
            throw new RouterException(
                "The action format entered is not valid."
            );
        }

        if (is_array($action)) {
            list($controller, $method) = $action;
            $action = compact('controller', 'method');
        }
        $this->action = $action;
        return $this;
    }
}