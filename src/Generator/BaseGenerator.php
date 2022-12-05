<?php

namespace Gut\Generator;

use Gut\Output;
use Kaskus\Forum\tests\Utility\KaskusTestCase;
use ReflectionMethod;
use ReflectionParameter;

abstract class BaseGenerator
{
	use Output;

	protected $reflection;
	protected $output = '<?php' . PHP_EOL;

	public function __construct(string $targetClass)
	{
		$this->targetClass = $targetClass;
		$this->reflection = new ReflectionClass($targetClass);
		$this->baseClassName = str_replace($this->reflection->getNamespaceName() . '\\', '', $this->reflection->getName());
		$this->namespace = new PhpNamespace($this->reflection->getNamespaceName());

		$this->uses = $this->namespace->getUses();

		$this->populatePublicMethods();
	}

	public function generate(): string
	{
		$this->setNamespace();
		$this->createClass();
		$this->createSetUpMethod();
		$this->createTestMethods();

		return $this->output . $this->namespace;
	}

	protected function populatePublicMethods(): void
	{
		$this->publicMethods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
	}

	protected function isPrimitiveType(string $type)
	{
		$primitiveTypes = ['bool', 'int', 'float', 'string', 'array'];

		return in_array($type, $primitiveTypes);
	}

	protected function setNamespace(): void
	{
		$this->namespace->addUse(KaskusTestCase::class);
	}

	protected function createClass(): void
	{
		$this->testClass = $this->namespace->addClass($this->baseClassName . 'Test');
		$this->testClass->setExtends(KaskusTestCase::class);
	}

	protected function getParameterType(ReflectionParameter $parameter)
	{
		if ($type = $parameter->getType()) {
			$returnType = str_replace('?', '', $type->getName());
		} else {
			$returnType = null;
		}

		return $returnType;
	}

	protected function getShortClassName(string $className)
	{
		$segs = explode('\\', $className);

		return \array_pop($segs);
	}
}
