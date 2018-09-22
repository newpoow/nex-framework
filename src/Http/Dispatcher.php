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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Process a server request and produce a response.
 * @package Nex\Http
 */
class Dispatcher implements RequestHandlerInterface
{
    /** @var \SplQueue */
    protected $queue;
    /** @var callable */
    protected $callback;

    /**
     * Dispatcher of requisitions.
     * @param callable $defaultHandler
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(callable $defaultHandler, array $middlewares = [])
    {
        $this->queue = new \SplQueue();
        $this->callback = $defaultHandler;

        array_map(function ($middleware) {
            if (is_callable($middleware)) {
                $middleware = new Server\Middleware($middleware);
            } elseif (is_string($middleware)) {
                $middleware = Server\Middleware::lazy($middleware);
            }

            $this->add($middleware);
        }, $middlewares);
    }

    /**
     * Add intermediate actions to the execution queue.
     * @param MiddlewareInterface $middleware
     * @return static
     */
    public function add(MiddlewareInterface $middleware): self
    {
        $this->queue->enqueue($middleware);
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