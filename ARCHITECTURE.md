# Architecture: laminas-code

## Purpose
A PHP code generation and reflection library. Provides programmatic generation of PHP classes, interfaces, traits, enums, methods, properties, and DocBlock comments, as well as enhanced reflection wrappers that parse PHPDoc annotations.

## Directory Structure
```
src/
  Generator/
    Class_Generator.php          # Generates a PHP class (properties, methods, constants, etc.)
    Interface_Generator.php      # Generates a PHP interface
    Trait_Generator.php          # Generates a PHP trait
    Method_Generator.php         # Generates a method with type hints and body
    Property_Generator.php       # Generates a class property
    Parameter_Generator.php      # Generates a method parameter
    Doc_Block_Generator.php      # Generates a PHPDoc block
    File_Generator.php           # Generates a complete PHP file
    EnumGenerator/
      Enum_Generator.php         # Generates a PHP 8.1 enum
    DocBlock/Tag/                # Individual PHPDoc tag generators (@param, @return, @throws, etc.)
    TypeGenerator/               # Union, intersection, atomic type string generation
    Value_Generator.php          # Serializes PHP values to source code strings
  Reflection/
    Class_Reflection.php         # Enhanced ReflectionClass with DocBlock awareness
    Method_Reflection.php        # Enhanced ReflectionMethod
    Property_Reflection.php      # Enhanced ReflectionProperty
    Doc_Block_Reflection.php     # Parses DocBlock comments into tag objects
    DocBlock/Tag/                # Individual PHPDoc tag reflection objects
  Scanner/
    Doc_Block_Scanner.php        # Low-level DocBlock token scanner
  Generic/Prototype/             # Prototype pattern utilities for reflection-backed instantiation
  Declare_Statement.php          # Represents a declare(strict_types=1) statement
  Exception/                     # Typed exceptions
```

## Key Design Decisions
- **Separate generator and reflection subsystems** — code generation (`Generator/`) and code introspection (`Reflection/`) are independent. The reflection layer enhances PHP's built-in `Reflection*` classes with PHPDoc awareness.
- **Fluent generator API** — all generator classes support method chaining; `Class_Generator::fromArray()` provides array-based configuration as an alternative.
- **Value serialization** — `Value_Generator` converts PHP scalar, array, and constant values to their PHP source code representation, handling edge cases like `null`, arrays with mixed keys, and class constants.
- **Tag registry** — `DocBlock\Tag_Manager` maps tag names to their reflection/generator class, allowing custom tag types to be registered.

## Extension Points
- Register a custom PHPDoc tag type via `Tag_Manager::addTagNameToClass()`.
- Extend any generator class to add project-specific generation logic.
- Use `File_Generator` to wrap a `Class_Generator` with namespace, use-statements, and `declare(strict_types=1)`.

## Dependency Flow
```
Class_Generator (configure)
  ├─ Method_Generator[] (with Parameter_Generator, TypeGenerator)
  ├─ Property_Generator[] (with Value_Generator)
  ├─ Doc_Block_Generator (with Tag[])
  └─ generate() → PHP source string

Class_Reflection (wraps ReflectionClass)
  └─ getDocBlock() → Doc_Block_Reflection
       └─ getTags() → Tag objects (@param, @return, @throws, …)
```
