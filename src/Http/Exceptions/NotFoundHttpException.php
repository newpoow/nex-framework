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
namespace Nex\Http\Exceptions;

use Throwable;

/**
 * Exception caused when the server could not find the requested resource.
 * @package Nex\Http
 */
class NotFoundHttpException extends HttpException
{
    /** @var string */
    protected $path;

    /**
     * The server could not find the requested resource.
     * @param string $path
     * @param Throwable|null $previous
     */
    public function __construct(string $path, Throwable $previous = null)
    {
        $this->path = $path;
        $message = sprintf("The requested URL '%s' was not found on this server.", $this->getPath());

        parent::__construct($message, 404, $previous);
    }

    /**
     * Get the http path.
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}