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
namespace Nex\Http\Server;

use Nex\Support\Facade;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Intermediate action, in the process of requesting and producing the response.
 * @package Nex\Http
 */
final class Middleware implements MiddlewareInterface
{
    /** @var callable */
    private $callback;

    /**
     * Intermediate action.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Process the request and/or change the response.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return call_user_func($this->callback, $request, $handler);
    }

    /**
     * Create a new lazy middleware.
     * The $middleware identifier is not required to be a class name,
     * any string that refers to a container identifier can be used.
     * @param string $middleware
     * @return MiddlewareInterface
     */
    public static function lazy(string $middleware): MiddlewareInterface
    {
        return new Middleware(
            function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($middleware) {
                $instance = Facade\Injector::get($middleware);
                if (!$instance instanceof MiddlewareInterface) {
                    throw new RuntimeException(sprintf(
                        "The provided middleware '%s' is not an implementation of '%s'.",
                        is_object($middleware) ? get_class($middleware) : gettype($middleware),
                        MiddlewareInterface::class
                    ));
                }
                return $instance->process($request, $handler);
            }
        );
    }
}