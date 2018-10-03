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
use Nex\Http\Exceptions\RouterHttpException;
use Nex\Http\Message\Response;
use Nex\Standard\Injection\InjectorInterface;
use Psr\Http\Message\ResponseFactoryInterface;
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

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Route executor.
     * @param InjectorInterface $injector
     * @param callable|array $routeAction
     * @param array $middlewares
     */
    public function __construct(InjectorInterface $injector, $routeAction, array $middlewares = [])
    {
        $this->injector = $injector;
        $this->handler = $this->prepareHandler($routeAction);

        parent::__construct($this, array_merge($middlewares, $this->getControllerMiddleware()));
    }

    /**
     * Performs the action defined in the access route.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute(ServerRequestInterface::class, $request);
        return $this->prepareResponse(
            $this->injector->execute($this->handler, $request->getAttributes())
        );
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Get the middleware for the route's controller.
     * @return array
     */
    protected function getControllerMiddleware(): array
    {
        if ($this->handler && is_array($this->handler)) {
            list($controller, $method) = $this->handler;
            if (method_exists($controller, 'getMiddleware')) {
                return $controller->getMiddleware($method);
            }
        }
        return array();
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
                    throw new RouterHttpException(sprintf(
                        "The controller class '%s' has not been defined.", $controller
                    ));
                }
                $controller = $this->injector->make($controller);
            }

            if (!method_exists($controller, $method)) {
                throw new RouterHttpException(sprintf(
                    "The controller class '%s' does not have a '%s' method.",
                    get_class($controller), $method
                ));
            }
            return array($controller, $method);
        }

        return $handler instanceof \Closure ? $handler->bindTo($this->injector) : $handler;
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

        /** @var ResponseFactoryInterface $factory */
        $factory = $this->injector->get(ResponseFactoryInterface::class);

        if (is_array($content) || $content instanceof \JsonSerializable) {
            $response = $factory->createResponse(200, 'OK');
            $response->getBody()->write($this->jsonEncode($content));
            return $response->withHeader("Content-Type", "application/json");
        }

        return new Response((string)$content, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Convert content to JSON.
     * @param mixed $content
     * @return string
     */
    protected function jsonEncode($content): string
    {
        $json = json_encode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(sprintf(
                "Unable to encode data to JSON in %s: '%s'.", __CLASS__, json_last_error_msg()
            ));
        }
        return $json;
    }
}