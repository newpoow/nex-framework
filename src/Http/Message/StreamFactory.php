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

use InvalidArgumentException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Data Stream Factory
 * @package Nex\Http
 */
class StreamFactory implements StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     * @param string $content
     * @return StreamInterface
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    /**
     * Create a stream from an existing file.
     * @param string $filename
     * @param string $mode
     * @return StreamInterface
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (empty($mode) || !in_array($mode[0], ['r', 'w', 'a', 'x', 'c'])) {
            throw new InvalidArgumentException(sprintf(
                "The mode '%s' is invalid.", $mode
            ));
        }

        set_error_handler(function () use ($filename) {
            throw new RuntimeException(sprintf(
                "The file '%s' cannot be opened.", $filename
            ));
        });
        $resource = fopen($filename, $mode);
        restore_error_handler();

        return new Stream($resource);
    }

    /**
     * Create a new stream from an existing resource.
     * @param resource $resource
     * @return StreamInterface
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}