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

use Nex\Standard\Http\RouterInterface;
use Nex\Standard\Injection\InjectorInterface;
use Nex\Standard\PackageInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Package for handling services with the hypertext transfer protocol.
 * @package Nex\Http
 */
class HttpPackage implements PackageInterface
{
    /**
     * Register services to the dependency injector.
     * @param InjectorInterface $injector
     */
    public function registerServices(InjectorInterface $injector)
    {
        $this->registerPSR($injector);
        $this->registerRouter($injector);
    }

    /**
     * Register the implementations of the psr pattern.
     * @param InjectorInterface $injector
     */
    protected function registerPSR(InjectorInterface $injector)
    {
        $injector->bind(RequestFactoryInterface::class, Message\RequestFactory::class);
        $injector->bind(ResponseFactoryInterface::class, Message\ResponseFactory::class);
        $injector->bind(ServerRequestFactoryInterface::class, Message\ServerRequestFactory::class);
        $injector->bind(StreamFactoryInterface::class, Message\StreamFactory::class);
        $injector->bind(UploadedFileFactoryInterface::class, Message\UploadedFileFactory::class);
        $injector->bind(UriFactoryInterface::class, Message\UriFactory::class);
    }

    /**
     * Register the router.
     * @param InjectorInterface $injector
     */
    protected function registerRouter(InjectorInterface $injector)
    {
        $injector->alias('router', RouterInterface::class);
        $injector->singleton(RouterInterface::class, function (InjectorInterface $injector) {
            return new Routing\Router($injector);
        });
    }
}