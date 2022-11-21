<?php
namespace Gut\Generators;

use Gut\Output;
use Kaskus\Forum\tests\Utility\KaskusTestCase;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use ReflectionMethod;

class EntityTestGenerator
{
	use Output;

	private $reflection;
	private $output = '<?php' . PHP_EOL;

	public function __construct(string $targetClass)
	{
		$this->targetClass = $targetClass;
		$this->reflection = new ReflectionClass($targetClass);
		$this->baseClassName = str_replace($this->reflection->getNamespaceName() . '\\', '', $this->reflection->getName());
		$this->namespace = new PhpNamespace($this->reflection->getNamespaceName());
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

	private function setNamespace(): void
	{
		$this->namespace->addUse(KaskusTestCase::class);
	}

	private function createClass(): void
	{
		$this->testClass = $this->namespace->addClass($this->baseClassName . 'Test');
		$this->testClass->setExtends(KaskusTestCase::class);
	}

	private function createSetUpMethod(): void
	{
		$method = $this->testClass->addMethod('setUp');
		$method->setProtected()->setReturnType('void');
		$method->addBody('$this->assocArray = [];');

		foreach ($this->publicMethods as $publicMethod) {
			$methodName = $publicMethod->getShortName();

			if ('set' == substr($methodName, 0, 3)) {
				$attribute = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($methodName, 3)));
				$getterMethodName = preg_replace('/^set/', '', $methodName);

				if ('id' == $attribute) {
					$attribute = '_id';
				}

				$this->attributes[$getterMethodName] = $attribute;

				if ($type = $publicMethod->getParameters()[0]->getType()) {
					$returnType = str_replace('?', '', $type->getName());
				} else {
					$returnType = null;
				}

				$this->print("Found class attribute: {$attribute} ({$returnType})");

				switch ($returnType) {
					case 'float':
					case 'int':
						$method->addBody("\$this->assocArray['{$attribute}'] = rand(100, 999);");
						$this->replacement[$methodName] = 'rand(100, 999);';

						break;

					case 'string':
					case null:
						$method->addBody("\$this->assocArray['{$attribute}'] = uniqid();");
						$this->replacement[$methodName] = 'uniqid();';

						break;

					case 'bool':
						$method->addBody("\$this->assocArray['{$attribute}'] = false;");
						$this->replacement[$methodName] = 'true;';

						break;

					default:
						throw new Exception("Unhandled return type: {$methodName}()" . $publicMethod->getReturnType());
				}
			}
		}

		$method = $this->testClass->addMethod('createEntity');
		$method->setPrivate()->setReturnType($this->targetClass);
		$method->addBody("\$entity = new {$this->baseClassName}(\$this->assocArray);");
		$method->addBody('return $entity;');
	}

	private function createTestMethods(): void
	{
		foreach ($this->publicMethods as $publicMethod) {
			$methodName = $publicMethod->getShortName();

			switch (true) {
				case in_array($methodName, ['__construct', 'toArray', 'isNew']):
					break;

				case 'set' == substr($methodName, 0, 3):
					$getterMethodName = preg_replace('/^set/', '', $methodName);

					if (method_exists($this->targetClass, 'get' . $getterMethodName)) {
						$getterMethodName = 'get' . $getterMethodName;
					} elseif (method_exists($this->targetClass, 'is' . $getterMethodName)) {
						$getterMethodName = 'is' . $getterMethodName;
					}
					$testMethod = $this->testClass->addMethod('test_' . $methodName . '_AllOk_ValueSet');
					$testMethod->setPublic()->setReturnType('void');
					$testMethod->addBody('$entity = $this->createEntity();');
					$testMethod->addBody("\$newValue = {$this->replacement[$methodName]}");
					$testMethod->addBody("\$entity->{$methodName}(\$newValue);");
					$testMethod->addBody("\$this->assertEquals(\$entity->{$getterMethodName}(), \$newValue);");

					break;

				case 'get' == substr($methodName, 0, 3):
				case 'is' == substr($methodName, 0, 2):
					preg_match('/^(get|is)(.*)/', $methodName, $matches);

					if (!empty($this->attributes[$methodName])) {
						$testMethod = $this->testClass->addMethod('test_' . $methodName . '_AllOk_ReturnCorrectValue');
						$testMethod->setPublic()->setReturnType('void');
						$testMethod->addBody('$entity = $this->createEntity();');
						$testMethod->addBody("\$value = \$entity->{$methodName}();");
						$testMethod->addBody("\$this->assertEquals(\$this->assocArray['{$this->attributes[$matches[2]]}'], \$value);");
					}

					break;

				default:
					$testMethod = $this->testClass->addMethod('test_' . $methodName . '_case_expectedOutcome');
					$testMethod->setPublic()->setReturnType('void');

					break;
			}
		}
	}

	private function populatePublicMethods(): void
	{
		$this->publicMethods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
	}
}
