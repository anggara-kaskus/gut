<?php

namespace Gut\Generator;

use Gut\Output;

abstract class BaseGenerator
{
	use Output;

	protected $reflection;
	protected $output = '<?php' . PHP_EOL;

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
}
