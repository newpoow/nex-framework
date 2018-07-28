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

use Psr\Http\Message\StreamInterface;

/**
 * Data stream for display in the message.
 * @package Nex\Http
 */
class Stream implements StreamInterface
{
    /** @var resource */
    protected $resource;

    /**
     * Data Stream.
     * @param string $resource
     */
    public function __construct($resource = '')
    {
        if (is_string($resource)) {
            $stream = fopen('php://temp', 'r+b');
            fwrite($stream, $resource);
            rewind($stream);
            $resource = $stream;
        }

        if (!is_resource($resource)) {
            throw new \InvalidArgumentException(
                "Invalid stream resource; must be a string or resource."
            );
        }
        $this->resource = $resource;
    }

    /**
     * Closes the stream and any underlying resources.
     */
    public function close()
    {
        if (is_resource($this->resource)) {
            $resource = $this->detach();
            fclose($resource);
        }
    }

    /**
     * Separates any underlying resources from the stream.
     * @return resource|null
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     * @return bool
     */
    public function eof()
    {
        if (is_resource($this->resource)) {
            return feof($this->resource);
        }
        return true;
    }

    /**
     * Returns the remaining contents in a string
     * @return string
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException("Unable to read stream contents.");
        }

        if (false === ($content = stream_get_contents($this->resource))) {
            throw new \RuntimeException("Unable to read remainder of the stream.");
        }
        return $content;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     * @param string|null $key
     * @return mixed
     */
    public function getMetadata($key = null)
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $metadata = stream_get_meta_data($this->resource);
        if (!is_null($key)) {
            return array_key_exists($key, $metadata) ? $metadata[$key] : null;
        }
        return $metadata;
    }

    /**
     * Get the size of the stream if known.
     * @return int|null
     */
    public function getSize()
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        if (false !== ($stats = fstat($this->resource))) {
            return $stats['size'];
        }
        return null;
    }

    /**
     * Returns whether or not the stream is readable.
     * @return bool
     */
    public function isReadable()
    {
        $mode = $this->getMetadata('mode');
        if (is_string($mode)) {
            return false !== strpbrk($mode, '+r');
        }
        return false;
    }

    /**
     * Returns whether or not the stream is seekable.
     * @return bool
     */
    public function isSeekable()
    {
        $seekable = $this->getMetadata('seekable');
        return is_bool($seekable) ? $seekable : false;
    }

    /**
     * Returns whether or not the stream is writable.
     * @return bool
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');
        if (is_string($mode)) {
            return false !== strpbrk($mode, '+acwx');
        }
        return false;
    }

    /**
     * Read data from the stream.
     * @param int $length
     * @return string
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException("Cannot read from non-readable stream.");
        }

        if (false === ($stream = fread($this->resource, intval($length)))) {
            throw new \RuntimeException("Unable to read from the stream.");
        }
        return $stream;
    }

    /**
     * Seek to the beginning of the stream.
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Seek to a position in the stream.
     * @param int $offset
     * @param int $whence
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException("Stream is not seekable.");
        }

        if (0 !== fseek($this->resource, intval($offset), intval($whence))) {
            throw new \RuntimeException(sprintf(
                "Unable to seek to stream position '%s' with whence %s.",
                $offset, var_export($whence, true)
            ));
        }
    }

    /**
     * Returns the current position of the file read/write pointer
     * @return int
     */
    public function tell()
    {
        if (!is_resource($this->resource)) {
            throw new \RuntimeException("Stream is not resourceable.");
        }

        if (false === ($position = ftell($this->resource))) {
            throw new \RuntimeException("Unable to get the stream pointer position.");
        }
        return $position;
    }

    /**
     * Write data to the stream.
     * @param string $string
     * @return int
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException("Cannot write to a non-writable stream.");
        }

        if (false === ($stream = fwrite($this->resource, strval($string)))) {
            throw new \RuntimeException("Unable to write to stream.");
        }
        return $stream;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     * @return string
     */
    public function __toString()
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (\Throwable $exception) {
            /** ignore... */
        }
        return '';
    }

    /**
     * Close the steam when destroying the object.
     */
    public function __destruct()
    {
        $this->close();
    }
}