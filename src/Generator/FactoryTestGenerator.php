<?php
namespace Gut\Generator;

use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;

class FactoryTestGenerator extends BaseGenerator
{
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

	private function createSetUpMethod(): void
	{
		$method = $this->testClass->addMethod('setUp');
		$method->setProtected()->setReturnType('void');
		$method->addBody('$this->assocArray = [];');

		$this->print('Detecting class methods...');

		$classMethods = [];

		foreach ($this->publicMethods as $publicMethod) {
			$methodName = $publicMethod->getShortName();
			$classMethods[$methodName] = [
				'params' => [],
				'return' => null
			];

			foreach ($publicMethod->getParameters() as $param) {
				if (!$param) {
					continue;
				}
				$type = $param->getType();
				$classMethods[$methodName]['params'][$param->getName()] = $type ? $type->getName() : null;
			}

			if ($type = $publicMethod->getReturnType()) {
				$classMethods[$methodName]['return'] = $type->getName();
			}
		}

		var_dump($classMethods);

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
}
