<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use Laminas\Code\Reflection\Exception\RuntimeException;
use Laminas\Code\Reflection\Parameter_Reflection;
use function sprintf;
final class Promoted_Parameter_Generator extends Parameter_Generator
{
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PROTECTED = 'protected';
    public const VISIBILITY_PRIVATE = 'private';
    /**
     * @psalm-param non-empty-string $name
     * @psalm-param ?non-empty-string $type
     * @psalm-param PromotedParameterGenerator::VISIBILITY_* $visibility
     */
    public function __construct(string $name, ?string $type = null, private readonly string $visibility = self::VISIBILITY_PUBLIC, ?int $position = null, bool $pass_by_reference = false)
    {
        parent::__construct($name, $type, null, $position, $pass_by_reference);
    }
    /** @psalm-return non-empty-string */
    public function generate(): string
    {
        return $this->visibility . ' ' . parent::generate();
    }
    public static function from_reflection(Parameter_Reflection $reflection_parameter): self
    {
        if (!$reflection_parameter->is_promoted()) {
            throw new RuntimeException(sprintf('Can not create "%s" from unprompted reflection.', self::class));
        }
        $visibility = self::VISIBILITY_PUBLIC;
        if ($reflection_parameter->is_protected_promoted()) {
            $visibility = self::VISIBILITY_PROTECTED;
        } elseif ($reflection_parameter->is_private_promoted()) {
            $visibility = self::VISIBILITY_PRIVATE;
        }
        return self::from_parameter_generator_with_visibility(parent::from_reflection($reflection_parameter), $visibility);
    }
    /** @psalm-param PromotedParameterGenerator::VISIBILITY_* $visibility */
    public static function from_parameter_generator_with_visibility(Parameter_Generator $generator, string $visibility): self
    {
        $name = $generator->get_name();
        $type = $generator->get_type();
        if ('' === $name) {
            throw new \Laminas\Code\Generator\Exception\RuntimeException('Name of promoted parameter must be non-empty-string.');
        }
        if ('' === $type) {
            throw new \Laminas\Code\Generator\Exception\RuntimeException('Type of promoted parameter must be non-empty-string.');
        }
        return new self($name, $type, $visibility, $generator->get_position(), $generator->get_passed_by_reference());
    }
}