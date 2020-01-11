<?php /** @noinspection PhpIncludeInspection */
declare(strict_types=1);
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
namespace Nex\Configuration\Parsers;

use InvalidArgumentException;
use Nex\Configuration\Exceptions\ParserException;
use Nex\Standard\Configuration\ParserInterface;
use Nex\Standard\Injection\InjectorInterface;

/**
 * Parser for files with the .php extension.
 * @package Nex\Configuration
 */
class PhpParser implements ParserInterface
{
    /** @var InjectorInterface|null */
    protected $injector;

    /**
     * Parser for .php files.
     * @param InjectorInterface|null $injector
     */
    public function __construct(?InjectorInterface $injector = null)
    {
        $this->injector = $injector;
    }

    /**
     * Standardize data to be saved.
     * @param array $data
     * @return string
     */
    public function dump(array $data): string
    {
        return "<?php\nreturn " . var_export($data, true) . ";";
    }

    /**
     * Parse a file and get its contents.
     * @param string $file
     * @return array
     */
    public function parse(string $file): array
    {
        if (($path = realpath($file)) === false) {
            throw new InvalidArgumentException(sprintf(
                "Couldn't compute the absolute path of '%s'.", $file
            ));
        }

        $content = require $path;
        if (is_callable($content) && $this->injector) {
            $content = $this->injector->execute($content);
        }

        if (!is_array($content)) {
            throw new ParserException(sprintf(
                "The file '%s' did not return an array.", $file
            ));
        }
        return $content;
    }
}