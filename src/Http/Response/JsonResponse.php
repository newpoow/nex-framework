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
 * @copyright (c) 2020 Nex Framework { https://github.com/newpoow/nex-framework }
 */
namespace Nex\Http\Response;

use InvalidArgumentException;
use Nex\Http\Message\Response;
use RuntimeException;

/**
 * JSON response.
 * @package Nex\Http
 */
class JsonResponse extends Response
{
    /**
     * Create a JSON response with the given data.
     * @param string $body
     * @param int $code
     * @param array $headers
     * @param int|string $flags
     */
    public function __construct(
        $body = '',
        int $code = 200,
        array $headers = [],
        int $flags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
    ) {
        parent::__construct(
            $this->jsonEncode($body, $flags),
            $code,
            array_merge($headers, ['Content-Type' => 'application/json'])
        );
    }

    /**
     * Encodes the data.
     * @param mixed $data
     * @param int $flags
     * @return false|string
     */
    protected function jsonEncode($data, int $flags)
    {
        if (is_resource($data)) {
            throw new InvalidArgumentException('Cannot JSON encode resources');
        }

        $data = json_encode($data, $flags);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                "Failed to encode data as JSON. '%s'.", json_last_error_msg()
            ));
        }
        return $data;
    }
}