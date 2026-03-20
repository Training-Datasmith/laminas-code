<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Enum_Generator;

use function array_map;
use function implode;
use Laminas\Code\Generator\Enum_Generator\Cases\Backed_Cases;
use Laminas\Code\Generator\Enum_Generator\Cases\Case_Factory;
use Laminas\Code\Generator\Enum_Generator\Cases\Pure_Cases;
use Reflection_Enum;
/** @psalm-immutable */
final class Enum_Generator
{
    /**
     * Line feed to use in place of EOL
     */
    private const LINE_FEED = "\n";
    /**
     * spaces of indentation by default
     */
    private const INDENTATION = '    ';
    /**
     * @param BackedCases|PureCases $cases
     */
    private function __construct(private readonly Name $name, private $cases)
    {
    }
    public function generate(): string
    {
        $output = '';
        if (null !== $this->name->get_namespace()) {
            $output .= 'namespace ' . $this->name->get_namespace() . ';' . self::LINE_FEED . self::LINE_FEED;
        }
        return $output . 'enum ' . $this->name->get_name() . $this->retrieve_type() . ' {' . self::LINE_FEED . $this->retrieve_cases() . '}' . self::LINE_FEED;
    }
    private function retrieve_type(): string
    {
        if ($this->cases instanceof Backed_Cases) {
            return ': ' . $this->cases->type;
        }
        return '';
    }
    private function retrieve_cases(): string
    {
        return implode('', array_map(fn(string $case): string => self::INDENTATION . 'case ' . $case . ';' . self::LINE_FEED, $this->cases->cases));
    }
    /**
     * @psalm-param array{
     *      name: non-empty-string,
     *      pureCases: list<non-empty-string>,
     * }|array{
     *      name: non-empty-string,
     *      backedCases: array{
     *          type: 'int'|'string',
     *          cases: array<non-empty-string, int|string>,
     *      },
     * } $options
     */
    public static function with_config(array $options): self
    {
        return new self(Name::from_fully_qualified_class_name($options['name']), Case_Factory::from_options($options));
    }
    public static function from_reflection(Reflection_Enum $enum): self
    {
        return new self(Name::from_fully_qualified_class_name($enum->get_name()), Case_Factory::from_reflection_cases($enum));
    }
}