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
namespace Nex;

use Nex\Standard\Http\RouterInterface;
use Nex\Support\AwareTraits;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Web Application.
 * @package Nex
 */
class Application extends Kernel
{
    use AwareTraits\LoadSettingsFromPackagesAwareTrait,
        AwareTraits\LoadSettingsFromApplicationAwareTrait,
        AwareTraits\BootPackagesAwareTrait,
        AwareTraits\DrawRoutesOnPackagesAwareTrait;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Web Application.
     * @param null|ContainerInterface $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        parent::__construct($container);

        $this->addPackage(new Configuration\ConfigurationPackage());
        $this->addPackage(new Http\HttpPackage());
    }

    /**
     * Define the application access routes.
     * @param \Closure $fn
     * @return mixed
     */
    public function drawRoutes(\Closure $fn)
    {
        return $this->bindAndRun(RouterInterface::class, $fn);
    }

    /**
     * Run the application by turning a request into a response.
     * @param ServerRequestInterface|null $request
     * @param callable|null $finalHandler
     * @return mixed
     */
    public function run(ServerRequestInterface $request = null, ?callable $finalHandler = null)
    {
        $this->initialize();

        $dispatcher = new Http\Dispatcher(function (ServerRequestInterface $request) {
            return $this->dispatchToRouter($request);
        });

        $request = $request ?: Http\Message\ServerRequestFactory::createfromGlobals();
        return call_user_func($finalHandler ?: new Http\Emitter(), $dispatcher->handle($request));
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Processes the access routes.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function dispatchToRouter(ServerRequestInterface $request): ResponseInterface
    {
        $router = $this->getInjector()->get(RouterInterface::class);
        if (!$router instanceof RouterInterface) {
            throw new \LogicException(sprintf(
                "The provided router does not implement '%s'.", RouterInterface::class
            ));
        }
        return $router->dispatch($request);
    }
}