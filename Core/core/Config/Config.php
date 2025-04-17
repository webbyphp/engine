<?php 

namespace Base\Config;

use Exception;

use Base\Config\Parser\Json;
use Base\Config\Writer\WriterInterface;
use Base\Config\Parser\ParserInterface;
use Base\Exceptions\FileNotFoundException;
use Base\Exceptions\EmptyDirectoryException;
use Base\Exceptions\UnsupportedFormatException;

/**
 * Configuration reader and writer for PHP.
 *
 * @package    Config
 * @author     Jesus A. Domingo <jesus.domingo@gmail.com>
 * @author     Hassan Khan <contact@hassankhan.me>
 * @author     Filip Š <projects@filips.si>
 * @link       https://github.com/noodlehaus/config
 * @license    MIT
 */

#[\AllowDynamicProperties]
class Config extends AbstractConfig
{

    /**
     * All formats supported by Config.
     *
     * @var array
     */
    protected $parsers = [
        'Base\Config\Parser\Php',
        'Base\Config\Parser\Json',
        'Base\Config\Parser\Yaml',
        'Base\Config\Parser\Serialize',
    ];

    /**
     * All formats supported by Config.
     *
     * @var array
     */
    protected $writers = [
        'Base\Config\Writer\Json',
        'Base\Config\Writer\Yaml',
        'Base\Config\Writer\Serialize'
    ];

    public function __construct(?array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * Loads a Config instance.
     *
     * @param  string|array    $values Filenames or string with configuration
     * @param  ParserInterface $parser Configuration parser
     * @param  bool            $string Enable loading from string
     */
    public function with($values, ?ParserInterface $parser = null, $string = false)
    {
        $config = null;

        if ($string === true) {

            $this->useString($values, $parser);

            $config = new Config($this->data);
        } else {
            $this->useFile($values, $parser);
        }

        parent::__construct($this->data);

        return $config;
    }

    /**
     * @param mixed $options
     * @return mixed
     */
    public function use(mixed $options, $parser = null, $isString = false)
    {

        if (
            is_string($options) 
            && $parser == 'json' 
            || str_contains($options, '.json')
        ) {
            return $this->useJson($options);
        }

        if (is_string($options)) {
            $options = new $options;
        }
        
        if (!is_object($options)) {
            throw new Exception('Config object must be an object');
        }

        $config = new static;

        foreach ($options as $name => $value) {
            $config->$name = $value;
        }

        return $config;
    }

    public function useJson($string)
    {

        if (is_json($string)) {
            $string = $this->useString($string, new Json);
        }

        if (str_contains($string, '.json')) {
            $string = $this->useFile($string, new Json);
        }

        return $string;
    }

    /**
     * Loads configuration from string.
     *
     * @param string          $configuration String with configuration
     * @param ParserInterface $parser        Configuration parser
     */
    protected function useString($configuration, ParserInterface $parser)
    {
        $this->data = [];

        // Try to parse string
        $this->data = array_replace_recursive($this->data, $parser->parseString($configuration));

        return new static($this->data);
    }

        /**
     * Loads configuration from file.
     *
     * @param  string|array     $path   Filenames or directories with configuration
     * @param  ParserInterface  $parser Configuration parser
     *
     * @throws \Base\Exceptions\EmptyDirectoryException If `$path` is an empty directory
     */
    protected function useFile($path, ?ParserInterface $parser = null)
    {
        $paths      = $this->getValidPath($path);
        $this->data = [];

        foreach ($paths as $path) {
            
            if ($parser === null) {
                // Get file information
                $info      = pathinfo($path);
                $parts     = explode('.', $info['basename']);
                $extension = array_pop($parts);

                // Skip the `dist` extension
                if ($extension === 'dist') {
                    $extension = array_pop($parts);
                }

                // Get file parser
                $parser = $this->getParser($extension);

                // Try to load file
                $this->data = array_replace_recursive($this->data, $parser->parseFile($path));

                // Clean parser
                $parser = null;
            } else {
                // Try to load file using specified parser
                $this->data = array_replace_recursive($this->data, $parser->parseFile($path));
            }
        }

        return new static($this->data);
    }

    /**
     * Writes configuration to file.
     *
     * @param  string           $filename   Filename to save configuration to
     * @param  WriterInterface  $writer Configuration writer
     *
     * @throws \Base\Exceptions\WriteException if the data could not be written to the file
     */
    public function toFile($filename, ?WriterInterface $writer = null)
    {
       
        if ($writer === null) {
            // Get file information
            $info      = pathinfo($filename);
            $parts     = explode('.', $info['basename']);
            $extension = array_pop($parts);

            // Skip the `dist` extension
            if ($extension === 'dist') {
                $extension = array_pop($parts);
            }

            // Get file writer
            $writer = $this->getWriter($extension);

            // Try to save file
            $writer->toFile($this->all(), $filename);

            // Clean writer
            $writer = null;
        } else {
            // Try to load file using specified writer
            $writer->toFile($this->all(), $filename);
        }
    }

    /**
     * Writes configuration to string.
     *
     * @param  WriterInterface  $writer Configuration writer
     * @param boolean           $pretty Encode pretty
     */
    public function toString(WriterInterface $writer, $pretty = true)
    {
        return $writer->toString($this->all(), $pretty);
    }

    /**
     * Gets a parser for a given file extension.
     *
     * @param  string $extension
     *
     * @return \Base\Config\Parser\ParserInterface
     *
     * @throws \Base\Exceptions\UnsupportedFormatException If `$extension` is an unsupported file format
     */
    protected function getParser($extension)
    {
        foreach ($this->parsers as $parser) {
            if (in_array($extension, $parser::getSupportedExtensions())) {
                return new $parser();
            }
        }

        // If none exist, then throw an exception
        throw new UnsupportedFormatException('Unsupported configuration format');
    }

    /**
     * Gets a writer for a given file extension.
     *
     * @param  string $extension
     *
     * @return \Base\Config\Writer\WriterInterface
     *
     * @throws \Base\Exceptions\UnsupportedFormatException If `$extension` is an unsupported file format
     */
    protected function getWriter($extension)
    {
        foreach ($this->writers as $writer) {
            if (in_array($extension, $writer::getSupportedExtensions())) {
                return new $writer();
            }
        }

        // If none exist, then throw an exception
        throw new UnsupportedFormatException('Unsupported configuration format'.$extension);
    }

    /**
     * Gets an array of paths
     *
     * @param  array $path
     *
     * @return array
     *
     * @throws \Base\Exceptions\FileNotFoundException   If a file is not found at `$path`
     */
    protected function getPathFromArray($path)
    {
        $paths = [];

        foreach ($path as $unverifiedPath) {
            try {
                // Check if `$unverifiedPath` is optional
                // If it exists, then it's added to the list
                // If it doesn't, it throws an exception which we catch
                if ($unverifiedPath[0] !== '?') {
                    $paths = array_merge($paths, $this->getValidPath($unverifiedPath));
                    continue;
                }

                $optionalPath = ltrim($unverifiedPath, '?');
                $paths = array_merge($paths, $this->getValidPath($optionalPath));
            } catch (FileNotFoundException $e) {
                // If `$unverifiedPath` is optional, then skip it
                if ($unverifiedPath[0] === '?') {
                    continue;
                }

                // Otherwise rethrow the exception
                throw $e;
            }
        }

        return $paths;
    }

    /**
     * Checks `$path` to see if it is either an array, a directory, or a file.
     *
     * @param  string|array $path
     *
     * @return array
     *
     * @throws \Base\Exceptions\EmptyDirectoryException If `$path` is an empty directory
     *
     * @throws \Base\Exceptions\FileNotFoundException   If a file is not found at `$path`
     */
    protected function getValidPath($path)
    {
        // If `$path` is array
        if (is_array($path)) {
            return $this->getPathFromArray($path);
        }

        // If `$path` is a directory
        if (is_dir($path)) {
            $paths = glob($path . '/*.*');
            if (empty($paths)) {
                throw new EmptyDirectoryException("Configuration directory: [$path] is empty");
            }

            return $paths;
        }

        // If `$path` is not a file, throw an exception
        if (!file_exists($path)) {
            throw new FileNotFoundException("Configuration file: [$path] cannot be found");
        }

        return [$path];
    }

}
