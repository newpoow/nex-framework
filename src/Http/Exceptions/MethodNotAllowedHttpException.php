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
 * Exception caused when the http verb used is not supported.
 * @package Nex\Http
 */
class MethodNotAllowedHttpException extends HttpException
{
    /** @var string[] */
    protected $allowedMethods = array();

    /**
     * The http verb used is not supported.
     * @param array $allowed
     * @param Throwable|null $previous
     */
    public function __construct(array $allowed, Throwable $previous = null)
    {
        $this->allowedMethods = $allowed;
        $message = sprintf(
            "The requested resource is not available for the HTTP method. Supported methods: '%s'.",
            strtoupper(implode(', ', $this->getAllowedMethods()))
        );

        parent::__construct($message, 405, $previous);
    }

    /**
     * Get the http methods allowed.
     * @return string[]
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}