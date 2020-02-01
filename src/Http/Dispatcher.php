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
namespace Nex\Http;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SplQueue;

/**
 * Process a server request and produce a response.
 * @package Nex\Http
 */
class Dispatcher implements RequestHandlerInterface
{
    /** @var callable */
    protected $callback;
    /** @var SplQueue */
    protected $queue;

    /**
     * Dispatcher of requisitions.
     * @param callable $defaultHandler
     */
    public function __construct(callable $defaultHandler)
    {
        $this->queue = new SplQueue();
        $this->callback = $defaultHandler;
    }

    /**
     * Add intermediate actions to the execution queue.
     * @param MiddlewareInterface $middleware
     * @return static
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->queue->enqueue($middleware);
        return $this;
    }

    /**
     * Add multiple intermediate actions to the execution queue.
     * @param array $middlewares
     * @return static
     */
    public function addMiddlewares(array $middlewares): self
    {
        array_map(function ($middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                if (is_callable($middleware)) {
                    $middleware = new Server\Middleware($middleware);
                } elseif (is_string($middleware)) {
                    $middleware = Server\Middleware::lazy($middleware);
                } else {
                    throw new InvalidArgumentException(sprintf(
                        "The value provided is not an implementation of '%s'.",MiddlewareInterface::class
                    ));
                }
            }
            $this->addMiddleware($middleware);
        }, $middlewares);

        return $this;
    }

    /**
     * Handles a request and produces a response.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->queue->isEmpty()) {
            return call_user_func($this->callback, $request);
        }

        $middleware = $this->queue->dequeue();
        return $middleware->process($request, $this);
    }
}