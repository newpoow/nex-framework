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

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Emits a Response to the PHP Server API.
 * @package Nex\Http
 */
class Emitter
{
    /** @var int */
    protected $bufferLength = 4096;
    /** @var bool */
    protected $ignoreHeaderSend = false;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Get the output buffer size for each iteration.
     * @return int
     */
    public function getBufferLength(): int
    {
        return $this->bufferLength;
    }

    /**
     * Checks if you are ignoring submission of headers.
     * @return bool
     */
    public function isIgnoreHeaderSend(): bool
    {
        return $this->ignoreHeaderSend;
    }

    /**
     * Set the output buffer size for each iteration.
     * @param int $bufferLength
     * @return Emitter
     */
    public function setBufferLength(int $bufferLength): self
    {
        $this->bufferLength = $bufferLength;
        return $this;
    }

    /**
     * Ignore submission of headers if previously sent.
     * @param bool $ignoreHeaderSend
     * @return Emitter
     */
    public function setIgnoreHeaderSend(bool $ignoreHeaderSend): self
    {
        $this->ignoreHeaderSend = $ignoreHeaderSend;
        return $this;
    }

    /**
     * Issues a response, including status, headers, and message body.
     * @param ResponseInterface $response
     */
    public function __invoke(ResponseInterface $response)
    {
        if ($this->assertNoPreviousOutput()) {
            $this->sendHeaders($response);
            $this->sendStatusLine($response);
        }
        $this->sendBody($response);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Checks to see if content has previously been sent.
     * @return bool
     */
    protected function assertNoPreviousOutput(): bool
    {
        $file = $line = null;
        $sent = headers_sent($file, $line);

        if ($sent && !$this->isIgnoreHeaderSend()) {
            throw new RuntimeException(sprintf(
                "Unable to emit response: Headers already sent in file '%s' on line '%s'.", $file, $line
            ));
        }
        return !$sent;
    }

    /**
     * Emit the message body.
     * @param ResponseInterface $response
     */
    protected function sendBody(ResponseInterface $response)
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $buffer = !$response->getHeaderLine('Content-Length') ?
            $body->getSize() : $response->getHeaderLine('Content-Length');
        if (isset($buffer)) {
            while ($buffer > 0 && !$body->eof()) {
                $data = $body->read(min($this->bufferLength, $buffer));
                echo $data;
                $buffer -= strlen($data);
            }
        } else {
            while (!$body->eof()) {
                echo $body->read($this->bufferLength);
            }
        }
    }

    /**
     * Emit response headers.
     * @param ResponseInterface $response
     */
    protected function sendHeaders(ResponseInterface $response)
    {
        $code = $response->getStatusCode();
        foreach ($response->getHeaders() as $header => $values) {
            $header = ucwords($header, '-');

            array_map(function ($value) use ($header, $code) {
                header(sprintf(
                    "%s: %s", $header, $value
                ), stripos($header, 'Set-Cookie') == 0, $code);
            }, $values);
        }
    }

    /**
     * Emit the status line.
     * @param ResponseInterface $response
     */
    protected function sendStatusLine(ResponseInterface $response)
    {
        $code = $response->getStatusCode();
        header(sprintf(
            "HTTP/%s %d %s", $response->getProtocolVersion(), $code, $response->getReasonPhrase()
        ), true, $code);
    }
}