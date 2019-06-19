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
namespace Nex\Injection\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception caused when no entries were found in the container.
 * @package Nex\Injection
 */
class EntryNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}