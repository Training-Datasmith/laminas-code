<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function current;
use function dirname;
use function file_put_contents;
use function in_array;
use function is_array;
use function is_string;
use function is_writable;
use Laminas\Code\Declare_Statement;
use Laminas\Code\Exception\InvalidArgumentException;
use Laminas\Code\Generator\Exception\Class_Not_Found_Exception;
use function method_exists;
use function preg_match;
use function preg_replace;
use function property_exists;
use function reset;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_OPEN_TAG;
use const T_WHITESPACE;
use function token_get_all;
use Traversable;
/**
 * @psalm-type InputUses = array<
 *     string|int,
 *     array{
 *      'use': non-empty-string,
 *      'as': non-empty-string|null
 *     }|array{
 *      non-empty-string,
 *      non-empty-string|null
 *     }|non-empty-string
 * >
 */
class File_Generator extends Abstract_Generator
{
    protected string $filename = '';
    protected ?Doc_Block_Generator $doc_block = null;
    /** @var string[] */
    protected array $required_files = [];
    protected string $namespace = '';
    /** @psalm-var list<array{non-empty-string, non-empty-string|null}> */
    protected array $uses = [];
    /**
     * @var ClassGenerator[]
     * @psalm-var array<string, ClassGenerator>
     */
    protected array $classes = [];
    protected string $body = '';
    /**
     * @var DeclareStatement[]
     * @psalm-var array<string, DeclareStatement>
     */
    protected array $declares = [];
    /**
     * Passes $options to {@link setOptions()}.
     *
     * @param array|Traversable|null $options
     */
    public function __construct($options = null)
    {
        if (null !== $options) {
            $this->set_options($options);
        }
    }
    /**
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     */
    public static function from_array(array $values): static
    {
        $file_generator = new static();
        foreach ($values as $name => $value) {
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'filename':
                    $file_generator->set_filename($value);
                    break;
                case 'class':
                    $file_generator->set_class($value instanceof Class_Generator ? $value : Class_Generator::from_array($value));
                    break;
                case 'requiredfiles':
                    $file_generator->set_required_files($value);
                    break;
                case 'declares':
                    $file_generator->set_declares(array_map(static fn($directive, $value): \Laminas\Code\Declare_Statement => Declare_Statement::from_array([$directive => $value]), array_keys($value), $value));
                    break;
                default:
                    if (property_exists($file_generator, $name)) {
                        $file_generator->{$name} = $value;
                    } elseif (method_exists($file_generator, 'set' . $name)) {
                        $file_generator->{'set' . $name}($value);
                    }
            }
        }
        return $file_generator;
    }
    /**
     * @param  DocBlockGenerator|array|string $docBlock
     * @throws Exception\InvalidArgumentException
     */
    public function set_doc_block($doc_block): static
    {
        if (is_string($doc_block)) {
            $doc_block = ['shortDescription' => $doc_block];
        }
        if (is_array($doc_block)) {
            $doc_block = new Doc_Block_Generator($doc_block);
        } elseif (!$doc_block instanceof Doc_Block_Generator) {
            throw new Exception\InvalidArgumentException(sprintf('%s is expecting either a string, array or an instance of %s\DocBlockGenerator', __METHOD__, __NAMESPACE__));
        }
        $this->doc_block = $doc_block;
        return $this;
    }
    public function get_doc_block(): ?\Laminas\Code\Generator\Doc_Block_Generator
    {
        return $this->doc_block;
    }
    /**
     * @param  string[] $requiredFiles
     */
    public function set_required_files(array $required_files): static
    {
        $this->required_files = $required_files;
        return $this;
    }
    /**
     * @return string[]
     */
    public function get_required_files(): array
    {
        return $this->required_files;
    }
    public function get_namespace(): string
    {
        return $this->namespace;
    }
    /**
     * @param  string $namespace
     */
    public function set_namespace($namespace): static
    {
        $this->namespace = (string) $namespace;
        return $this;
    }
    /**
     * Returns an array with the first element the use statement, second is the as part.
     * If $withResolvedAs is set to true, there will be a third element that is the
     * "resolved" as statement, as the second part is not required in use statements
     *
     * @param  bool $withResolvedAs
     * @psalm-return array<int, array{string, null|string, false|null|string}>
     */
    public function get_uses($with_resolved_as = false): array
    {
        $uses = $this->uses;
        if ($with_resolved_as) {
            for ($use_index = 0, $count = count($uses); $use_index < $count; $use_index++) {
                if ($uses[$use_index][1] == '') {
                    if (($last_separator = strrpos($uses[$use_index][0], '\\')) !== false) {
                        $uses[$use_index][2] = substr($uses[$use_index][0], $last_separator + 1);
                    } else {
                        $uses[$use_index][2] = $uses[$use_index][0];
                    }
                } else {
                    $uses[$use_index][2] = $uses[$use_index][1];
                }
            }
        }
        return $uses;
    }
    /**
     * @param InputUses $uses
     */
    public function set_uses(array $uses): static
    {
        foreach ($uses as $use) {
            $use = (array) $use;
            if (array_key_exists('use', $use) && array_key_exists('as', $use)) {
                $this->set_use($use['use'], $use['as']);
            } elseif (count($use) === 2) {
                [$import, $alias] = $use;
                $this->set_use($import, $alias);
            } else {
                $this->set_use(current($use));
            }
        }
        return $this;
    }
    /**
     * @param  non-empty-string      $use
     * @param  null|non-empty-string $as
     */
    public function set_use($use, $as = null): static
    {
        if (!in_array([$use, $as], $this->uses)) {
            $this->uses[] = [$use, $as];
        }
        return $this;
    }
    /**
     * @param  array[]|string[]|ClassGenerator[] $classes
     */
    public function set_classes(array $classes): static
    {
        foreach ($classes as $class) {
            $this->set_class($class);
        }
        return $this;
    }
    /**
     * @param string|null $name
     * @return ClassGenerator
     * @throws ClassNotFoundException
     */
    public function get_class($name = null)
    {
        if ($name === null) {
            reset($this->classes);
            $class = current($this->classes);
            if (false === $class) {
                throw new Class_Not_Found_Exception('No class is set');
            }
            return $class;
        }
        if (false === array_key_exists($name, $this->classes)) {
            throw new Class_Not_Found_Exception(sprintf('Class %s is not set', $name));
        }
        return $this->classes[$name];
    }
    /**
     * @param  array|string|ClassGenerator $class
     * @throws Exception\InvalidArgumentException
     */
    public function set_class($class): static
    {
        if (is_array($class)) {
            $class = Class_Generator::from_array($class);
        } elseif (is_string($class)) {
            $class = new Class_Generator($class);
        } elseif (!$class instanceof Class_Generator) {
            throw new Exception\InvalidArgumentException(sprintf('%s is expecting either a string, array or an instance of %s\ClassGenerator', __METHOD__, __NAMESPACE__));
        }
        // @todo check for dup here
        $class_name = $class->get_name();
        $this->classes[$class_name] = $class;
        return $this;
    }
    /**
     * @param  string $filename
     */
    public function set_filename($filename): static
    {
        $this->filename = (string) $filename;
        return $this;
    }
    public function get_filename(): string
    {
        return $this->filename;
    }
    /**
     * @return ClassGenerator[]
     */
    public function get_classes(): array
    {
        return $this->classes;
    }
    /**
     * @param  string $body
     */
    public function set_body($body): static
    {
        $this->body = (string) $body;
        return $this;
    }
    public function get_body(): string
    {
        return $this->body;
    }
    /**
     * @param DeclareStatement[] $declares
     */
    public function set_declares(array $declares): static
    {
        foreach ($declares as $declare) {
            if (!$declare instanceof Declare_Statement) {
                throw new InvalidArgumentException(sprintf('%s is expecting an array of %s objects', __METHOD__, Declare_Statement::class));
            }
            if (!array_key_exists($declare->get_directive(), $this->declares)) {
                $this->declares[$declare->get_directive()] = $declare;
            }
        }
        return $this;
    }
    /**
     * @return bool
     */
    public function is_source_dirty()
    {
        $doc_block = $this->get_doc_block();
        if ($doc_block && $doc_block->is_source_dirty()) {
            return true;
        }
        foreach ($this->classes as $class) {
            if ($class->is_source_dirty()) {
                return true;
            }
        }
        return parent::is_source_dirty();
    }
    /**
     * @return string
     */
    public function generate()
    {
        if ($this->is_source_dirty() === false) {
            return $this->source_content ?? '';
        }
        $output = '';
        // @note body gets populated when FileGenerator created
        // from a file.  @see fromReflection and may also be set
        // via FileGenerator::setBody
        $body = $this->get_body();
        // start with the body (if there), or open tag
        if (preg_match('#(?:\s*)<\?php#', $body) == false) {
            $output = '<?php' . self::LINE_FEED;
        }
        // if there are markers, put the body into the output
        if (preg_match('#/\* Laminas_Code_Generator_Php_File-(.*?)Marker:#m', $body)) {
            $tokens = token_get_all($body);
            foreach ($tokens as $token) {
                if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_COMMENT, T_DOC_COMMENT, T_WHITESPACE])) {
                    $output .= $token[1];
                }
            }
            $body = '';
        }
        // Add file DocBlock, if any
        if (null !== $doc_block = $this->get_doc_block()) {
            $doc_block->set_indentation('');
            if (preg_match('#/\* Laminas_Code_Generator_FileGenerator-DocBlockMarker \*/#m', $output)) {
                // @codingStandardsIgnoreStart
                $output = preg_replace('#/\* Laminas_Code_Generator_FileGenerator-DocBlockMarker \*/#m', $doc_block->generate(), $output, 1);
                // @codingStandardsIgnoreEnd
            } else {
                $output .= $doc_block->generate() . self::LINE_FEED;
            }
        }
        // newline
        $output .= self::LINE_FEED;
        // declares, if any
        if ($this->declares) {
            $declare_statements = '';
            foreach ($this->declares as $declare) {
                $declare_statements .= $declare->get_statement() . self::LINE_FEED;
            }
            if (preg_match('#/\* Laminas_Code_Generator_FileGenerator-DeclaresMarker \*/#m', $output)) {
                $output = preg_replace('#/\* Laminas_Code_Generator_FileGenerator-DeclaresMarker \*/#m', $declare_statements, $output, 1);
            } else {
                $output .= $declare_statements;
            }
            $output .= self::LINE_FEED;
        }
        // namespace, if any
        $namespace = $this->get_namespace();
        if ($namespace) {
            $namespace = sprintf('namespace %s;%s', $namespace, str_repeat(self::LINE_FEED, 2));
            if (preg_match('#/\* Laminas_Code_Generator_FileGenerator-NamespaceMarker \*/#m', $output)) {
                $output = preg_replace('#/\* Laminas_Code_Generator_FileGenerator-NamespaceMarker \*/#m', $namespace, $output, 1);
            } else {
                $output .= $namespace;
            }
        }
        // process required files
        // @todo marker replacement for required files
        $required_files = $this->get_required_files();
        if (!empty($required_files)) {
            foreach ($required_files as $required_file) {
                $output .= 'require_once \'' . $required_file . '\';' . self::LINE_FEED;
            }
            $output .= self::LINE_FEED;
        }
        $classes = $this->get_classes();
        $class_uses = [];
        //build uses array
        foreach ($classes as $class) {
            //check for duplicate use statements
            $class_uses = array_merge($class_uses, $class->get_uses());
        }
        // process import statements
        $uses = $this->get_uses();
        if (!empty($uses)) {
            $use_output = '';
            foreach ($uses as $use) {
                [$import, $alias] = $use;
                if (null === $alias) {
                    $temp_output = sprintf('%s', $import);
                } else {
                    $temp_output = sprintf('%s as %s', $import, $alias);
                }
                //don't duplicate use statements
                if (!in_array($temp_output, $class_uses)) {
                    $use_output .= 'use ' . $temp_output . ';';
                    $use_output .= self::LINE_FEED;
                }
            }
            $use_output .= self::LINE_FEED;
            if (preg_match('#/\* Laminas_Code_Generator_FileGenerator-UseMarker \*/#m', $output)) {
                $output = preg_replace('#/\* Laminas_Code_Generator_FileGenerator-UseMarker \*/#m', $use_output, $output, 1);
            } else {
                $output .= $use_output;
            }
        }
        // process classes
        if (!empty($classes)) {
            foreach ($classes as $class) {
                // @codingStandardsIgnoreStart
                $regex = str_replace('&', $class->get_name(), '/\* Laminas_Code_Generator_Php_File-ClassMarker: \{[A-Za-z0-9\\\\]+?&\} \*/');
                // @codingStandardsIgnoreEnd
                if (preg_match('#' . $regex . '#m', $output)) {
                    $output = preg_replace('#' . $regex . '#', $class->generate(), $output, 1);
                } else {
                    if ($namespace) {
                        $class->set_namespace_name(null);
                    }
                    $output .= $class->generate() . self::LINE_FEED;
                }
            }
        }
        if (!empty($body)) {
            // add an extra space between classes and
            if (!empty($classes)) {
                $output .= self::LINE_FEED;
            }
            $output .= $body;
        }
        return $output;
    }
    /**
     * @throws Exception\RuntimeException
     */
    public function write(): static
    {
        if ($this->filename == '' || !is_writable(dirname($this->filename))) {
            throw new Exception\RuntimeException('This code generator object is not writable.');
        }
        file_put_contents($this->filename, $this->generate());
        return $this;
    }
}