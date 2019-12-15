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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Represents values of a file uploaded through an HTTP Request.
 * @package Nex\Http
 */
class UploadedFile implements UploadedFileInterface
{
    /** @var string[] */
    protected const ERROR_MESSAGES = array(
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
    );

    /** @var int */
    protected $error;
    /** @var string|null */
    protected $filename;
    /** @var string|null */
    protected $type;
    /** @var int|null */
    protected $size;
    /** @var StreamInterface */
    protected $stream;

    /**
     * Uploaded File.
     * @param StreamInterface $stream
     * @param int|null $size
     * @param int $error
     * @param string|null $filename
     * @param string|null $mediaType
     */
    public function __construct(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $filename = null,
        ?string $mediaType = null
    ) {
        if (0 > $error || 8 < $error) {
            throw new InvalidArgumentException(
                "Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant."
            );
        }

        $this->stream = $stream;
        $this->error = $error;
        $this->size = $size ?: $stream->getSize();
        $this->filename = $filename;
        $this->type = $mediaType;
    }

    /**
     * Retrieve the filename sent by the client.
     * @return string|null
     */
    public function getClientFilename()
    {
        return $this->filename;
    }

    /**
     * Retrieve the media type sent by the client.
     * @return string|null
     */
    public function getClientMediaType()
    {
        return $this->type;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the file size.
     * @return int|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     * @return StreamInterface
     */
    public function getStream()
    {
        if (!$this->stream instanceof StreamInterface) {
            throw new RuntimeException(
                "Cannot retrieve stream after it has already been moved."
            );
        }
        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     * @param string $targetPath
     */
    public function moveTo($targetPath)
    {
        if (!($this->stream instanceof StreamInterface) || UPLOAD_ERR_OK !== $this->error) {
            throw new RuntimeException(
                self::ERROR_MESSAGES[$this->error] ??
                "The uploaded file cannot be moved due to an error or already moved."
            );
        }

        $folder = dirname($targetPath);
        if (!is_dir($folder) || !is_writable($folder)) {
            throw new InvalidArgumentException(sprintf(
                "The directory '%s' does not exists or is not writable.", $folder
            ));
        }

        set_error_handler(function () use ($targetPath) {
            throw new RuntimeException(sprintf(
                "Uploaded file could not be moved to '%s'.", $targetPath
            ));
        });
        $resource = fopen($targetPath, 'wb+');
        restore_error_handler();

        $target = new Stream($resource);
        $this->stream->rewind();
        while (!$this->stream->eof()) {
            $target->write($this->stream->read(4096));
        }

        $this->stream->close();
        $this->stream = null;
        $target->close();
    }
}