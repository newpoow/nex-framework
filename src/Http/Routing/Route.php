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

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Represents an access route.
 * @package Nex\Http
 */
class Route
{
    /** @var RequestHandlerInterface */
    protected $handler;
    /** @var string[] */
    protected $methods = array();
    /** @var string */
    protected $uri;

    /** @var array */
    private $middlewares = array();
    /** @var string|null */
    protected $name;
    /** @var array */
    protected $parameters = array();
    /** @var string[] */
    protected $patterns = array();
    /** @var string|null */
    protected $regex;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * The access route.
     * @param array $methods
     * @param string $uri
     * @param RequestHandlerInterface $handler
     */
    public function __construct(array $methods, string $uri, RequestHandlerInterface $handler)
    {
        $this->setMethods($methods);
        $this->setUri($uri);
        $this->setHandler($handler);
    }

    /**
     * Get the action of the route.
     * @return RequestHandlerInterface
     */
    public function getHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Get the route access methods.
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the intermediate actions that precede the execution of the route.
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Get the short name of the route.
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the access parameters used in the request.
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the route-specific regular expressions.
     * @return string[]
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    /**
     * Get the compiled version of the uri.
     * @return string|null
     */
    public function getRegex(): ?string
    {
        return $this->regex;
    }

    /**
     * Get the URI for route access.
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Defines the intermediate actions that must be performed if the route is matched.
     * @param mixed ...$middlewares
     * @return static
     */
    public function middleware(...$middlewares): self
    {
        if (count($middlewares) === 1 && is_array($middlewares[0])) {
            $middlewares = $middlewares[0];
        }

        foreach ($middlewares as $middleware) {
            if (!is_string($middleware) && !is_callable($middleware) && !$middleware instanceof MiddlewareInterface) {
                throw new InvalidArgumentException(sprintf(
                    "Middleware provided for route '%s' is not valid.", $this->getUri()
                ));
            }
            $this->middlewares[] = $middleware;
        }
        return $this;
    }

    /**
     * Set a short name for the route.
     * @param string $name
     * @return static
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Defines the compiled version of the uri.
     * @param string $regex
     * @return static
     */
    public function setRegex(string $regex): self
    {
        $this->regex = $regex;
        return $this;
    }

    /**
     * Define a regular expression, specific to the route.
     * @param string|array $name
     * @param string|null $pattern
     * @return static
     */
    public function where($name, ?string $pattern = null): self
    {
        $wheres = is_array($name) ? $name : array($name => $pattern);
        foreach ($wheres as $name => $where) {
            $this->patterns[$name] = $where;
        }
        return $this;
    }

    /**
     * Returns a new instance with the added data used for access, such as parameters.
     * @param array $parameters
     * @return Route
     */
    public function withParameters(array $parameters): Route
    {
        $cloned = clone $this;
        $cloned->parameters = array_merge($cloned->parameters, array_map(function ($value) {
            if (is_string($value)) {
                return trim(rawurldecode($value), '\/');
            }
            return $value;
        }, $parameters));
        return $cloned;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Defines the action to take when the route is matched.
     * @param RequestHandlerInterface $handler
     * @return static
     */
    protected function setHandler(RequestHandlerInterface $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Defines the methods for route access.
     * @param array $methods
     * @return static
     */
    protected function setMethods(array $methods): self
    {
        $methods = array_map('strtoupper', $methods);
        if (in_array('GET', $methods) && !in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }
        $this->methods = array_unique($methods, SORT_STRING);
        return $this;
    }

    /**
     * Defines the route access URI.
     * @param string $uri
     * @return static
     */
    protected function setUri(string $uri): self
    {
        $this->uri = '/' . trim($uri, '/');
        return $this;
    }
}