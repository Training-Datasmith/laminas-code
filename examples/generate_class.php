<?php

declare(strict_types=1);

/**
 * Example: generating and reflecting PHP class code with laminas-code.
 *
 * Run from the laminas-code project root:
 *   php examples/generate_class.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Reflection\ClassReflection;

// --- Generate a class ---
$class = new ClassGenerator();
$class->setName('UserRepository')
      ->setNamespaceName('App\\Repository')
      ->setExtendedClass('Abstract_Repository')
      ->setImplementedInterfaces(['UserRepositoryInterface'])
      ->addUse('App\\Model\\User')
      ->setDocBlock(DocBlockGenerator::fromArray([
          'shortDescription' => 'Repository for User model persistence.',
          'longDescription'  => 'Provides find, save, and delete operations for User entities.',
          'tags' => [
              ['name' => 'package', 'description' => 'App\\Repository'],
          ],
      ]));

// Add a property
$class->addProperty(
    'table',
    'users',
    PropertyGenerator::FLAG_PROTECTED
);

// Add a method
$findMethod = new MethodGenerator('find_by_email');
$findMethod->setVisibility(MethodGenerator::VISIBILITY_PUBLIC)
           ->setParameter(new ParameterGenerator('email', 'string'))
           ->setReturnType('?User')
           ->setDocBlock(DocBlockGenerator::fromArray([
               'shortDescription' => 'Find a user by their email address.',
               'tags' => [
                   ['name' => 'param',  'description' => 'string $email'],
                   ['name' => 'return', 'description' => 'User|null'],
               ],
           ]))
           ->setBody('return $this->where(\'email\', $email)->first();');

$class->addMethodFromGenerator($findMethod);

echo $class->generate();

// --- Reflect an existing class ---
echo "\n\n--- Reflecting ArrayObject ---\n";
$reflection = new ClassReflection(\ArrayObject::class);

echo "Class:      " . $reflection->getName() . "\n";
echo "Methods:    " . count($reflection->getMethods()) . "\n";

$countMethod = $reflection->getMethod('count');
echo "count() file:     " . ($countMethod->getFileName() ?: 'internal') . "\n";
echo "count() returns:  " . ($countMethod->getReturnType() ?: 'mixed') . "\n";
