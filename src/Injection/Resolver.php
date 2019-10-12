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
namespace Nex\Injection;

use Nex\Injection\Exceptions\ContainerException;
use Nex\Standard\Injection\ResolverInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionParameter;

/**
 * Parameter resolver for functions/methods.
 * @package Nex\Injection
 */
class Resolver implements ResolverInterface
{
    /** @var ContainerInterface */
    protected $container;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Parameter resolver.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the parameters to be used by a function/method.
     * @param ReflectionFunctionAbstract $reflected
     * @param array $primitives
     * @return array
     * @throws ReflectionException
     */
    public function resolveParameters(ReflectionFunctionAbstract $reflected, array $primitives = []): array
    {
        $parameters = array();
        foreach ($reflected->getParameters() as $index => $parameter) {
            $value = $this->getParameterValue($parameter, $primitives);
            if (!is_null($value) || $parameter->isOptional()) {
                $parameters[$index] = $value;
                continue;
            }

            $where = $reflected->getName();
            if ($class = $parameter->getDeclaringClass()) {
                $where = "{$class->getName()}::{$where}()";
            }

            throw new ContainerException(sprintf(
                "Identifier '$%s' cannot be resolved in '%s'.", $parameter->getName(), $where
            ));
        }
        return $parameters;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Get the value to be used by the parameter.
     * @param ReflectionParameter $parameter
     * @param array $primitives
     * @return mixed|null
     * @throws ReflectionException
     */
    protected function getParameterValue(ReflectionParameter $parameter, array $primitives)
    {
        $name = $parameter->getName();
        if (($value = $this->getFromPrimitives($name, $primitives)) !== null) {
            return $value;
        }

        $class = $parameter->getClass();
        if ($class) {
            $type = $class->getName();
            if (($value = $this->getFromPrimitives($type, $primitives)) !== null) {
                return $value;
            }

            if ($this->container->has($type)) {
                return $this->container->get($type);
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }
        return null;
    }

    /**
     * Get the primitive value if it exists.
     * @param string $nameParameter
     * @param array $primitives
     * @return mixed
     */
    protected function getFromPrimitives(string $nameParameter, array $primitives)
    {
        if (array_key_exists($nameParameter, $primitives)) {
            return $primitives[$nameParameter];
        }
        return null;
    }
}