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
namespace Nex\Http\Message;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * HTTP Server Request Factory.
 * @package Nex\Http
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * Create a new server request.
     * @param string $method
     * @param UriInterface|string $uri
     * @param array $serverParams
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return (new ServerRequest($serverParams, $uri))->withMethod($method);
    }

    /**
     * Creates the server request instance from superglobals variables.
     * @return ServerRequestInterface
     */
    public static function createfromGlobals(): ServerRequestInterface
    {
        $request = new ServerRequest($_SERVER, self::getUriFromServer());
        foreach (self::getHeadersFromServer() as $name => $value) {
            $request = $request->withAddedHeader($name, $value);
        }

        if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
            $request = $request->withMethod($_SERVER['REQUEST_METHOD']);
        }

        if (array_key_exists('SERVER_PROTOCOL', $_SERVER)
            && preg_match('/^HTTP\/(\d(?:\.\d)?)$/', $_SERVER['SERVER_PROTOCOL'], $matches)) {
            $request = $request->withProtocolVersion($matches[1]);
        }

        $request = $request->withUploadedFiles(self::getUploadedFilesFromServer());
        return $request->withCookieParams($_COOKIE)->withParsedBody($_POST)->withQueryParams($_GET);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Gets the request headers from the given server environment.
     * @return array
     */
    protected static function getHeadersFromServer(): array
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'REDIRECT_') === 0) {
                $key = substr($key, 9);
                if (array_key_exists($key, $_SERVER)) {
                    continue;
                }
            }

            if (strpos($key, 'HTTP_') === 0) {
                $headers[strtr(strtolower(substr($key, 5)), '_', '-')] = $value;
            } elseif (strpos($key, 'CONTENT_') === 0) {
                $headers['content-' . strtolower(substr($key, 8))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Transforms each value into an UploadedFile instance, and ensures that nested arrays are normalized.
     * @return array
     */
    protected static function getUploadedFilesFromServer(): array
    {
        $walker = function ($path, $size, $error, $name, $type) use (&$walker) {
            if (!is_array($path)) {
                return (new UploadedFileFactory())->createUploadedFile(
                    (new StreamFactory())->createStreamFromFile($path, 'rb'),
                    $size, $error, $name, $type
                );
            }

            $files = array();
            foreach ($path as $key => $value) {
                $files[$key] = $walker(
                    $path[$key], $size[$key], $error[$key], $name[$key], $type[$key]
                );
            }
            return $files;
        };

        $files = array();
        foreach ($_FILES as $field => $file) {
            $files = $walker(
                $file['tmp_name'], $file['size'], $file['error'], $file['name'], $file['type']
            );
        }
        return $files;
    }

    /**
     * Gets the request URI from the given server environment.
     * @return UriInterface
     */
    protected static function getUriFromServer(): UriInterface
    {
        $server = array_change_key_case($_SERVER, CASE_LOWER);
        $scheme = 'http';
        if (array_key_exists('https', $server) &&
            ((is_bool($server['https']) && true === $server['https'])
                || 'on' === strtolower($server['https']))) {
            $scheme = 'https';
        }

        $host = 'localhost';
        if (array_key_exists('http_host', $server)) {
            $host = $server['http_host'];
        } elseif (array_key_exists('server_name', $server)) {
            $host = $server['server_name'];
            if (array_key_exists('server_port', $server)) {
                $host .= ':' . $server['server_port'];
            }
        }

        $target = '/';
        if (array_key_exists('request_uri', $server)) {
            $target = $server['request_uri'];
        } elseif (array_key_exists('php_self', $server)) {
            $target = $server['php_self'];
            if (array_key_exists('query_string', $server)) {
                $target .= '?' . ltrim($server['query_string'], '?');
            }
        }
        return (new UriFactory())->createUri($scheme . '://'. $host . $target);
    }
}