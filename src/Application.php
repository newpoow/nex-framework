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

use Closure;
use LogicException;
use Nex\Http\Dispatcher;
use Nex\Injection\Injector;
use Nex\Standard\Http\RouterInterface;
use Nex\Support\AwareTraits;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Web Application.
 * @package Nex
 */
class Application extends Kernel
{
    use AwareTraits\LoadSettingsFromPackagesAwareTrait,
        AwareTraits\BootPackagesAwareTrait,
        AwareTraits\DrawRoutesOnPackagesAwareTrait;

    /** @var Dispatcher */
    protected $dispatcher;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Web Application.
     * @param ContainerInterface|null $container
     */
    public function __construct(?ContainerInterface $container = null)
    {
        parent::__construct(new Injector($container));

        $this->getInjector()->instance(Application::class, $this);
        $this->dispatcher = new Dispatcher(function (ServerRequestInterface $request) {
            return $this->dispatchToRouter($request);
        });

        $this->addPackages([
            new Configuration\ConfigurationPackage(),
            new Http\HttpPackage()
        ]);
    }

    /**
     * Add intermediate actions to run in the application.
     * @param mixed ...$middleware
     * @return static
     */
    public function addMiddleware(...$middleware): self
    {
        $this->dispatcher->addMiddlewares(
            isset($middleware[0]) && is_array($middleware[0]) ? $middleware[0] : $middleware
        );
        return $this;
    }

    /**
     * Define the application access routes.
     * @param Closure $fn
     * @return mixed
     */
    public function drawRoutes(Closure $fn)
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
        $finalHandler = $finalHandler ?? new Http\Emitter();
        $request = $request ?? Http\Message\ServerRequestFactory::createFromGlobals();

        $this->getInjector()->instance(ServerRequestInterface::class, $request);
        $this->initialize();

        return call_user_func($finalHandler, $this->dispatcher->handle($request));
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
            throw new LogicException(sprintf(
                "The provided Router does not implement '%s'.", RouterInterface::class
            ));
        }
        return $router->dispatch($request);
    }
}