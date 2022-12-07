<?php
namespace Gut\Generator;

use Exception;

class FactoryTestGenerator extends BaseGenerator
{
	protected function createSetUpMethod(): void
	{
		$method = $this->testClass->addMethod('setUp');
		$method->setProtected()->setReturnType('void');
		$method->addBody('$this->assocArray = [];');

		$this->println('Detecting class methods...');

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

		$method = $this->testClass->addMethod('createFactory');
		$method->setprotected()->setReturnType($this->targetClass);
		$method->addBody("\$entity = new {$this->baseClassName}(\$this->assocArray);");
		$method->addBody('return $entity;');
	}

	protected function createTestMethods(): void
	{
		foreach ($this->publicMethods as $publicMethod) {
			$methodName = $publicMethod->getShortName();

			$testMethod = $this->testClass->addMethod('test_' . $methodName . '_case_expectedOutcome');
			$returnType = $publicMethod->getReturnType();

			$params = [];

			foreach ($publicMethod->getParameters() as $param) {
				$type = $this->getParameterType($param);
				$params[] = '$' . $param->getName();

				switch ($type) {
					case 'float':
					case 'int':
						$testMethod->addBody('$' . $param->getName() . ' = rand(100, 999);');

						break;

					case 'string':
					case null:
						$testMethod->addBody('$' . $param->getName() . ' = uniqid();');

						break;

					case 'bool':
						$testMethod->addBody('$' . $param->getName() . ' = false;');

						break;

					case 'array':
						$testMethod->addBody('$' . $param->getName() . ' = [];');

						break;

					default:
						throw new Exception("Unhandled parameter type: {$methodName}() -> {$type} \$" . $param->getName());
				}
			}

			$testMethod->addBody(PHP_EOL . '$factory = $this->createFactory();');
			$testMethod->addBody('$result = $factory->' . $methodName . '(' . implode(', ', $params) . ');');

			if (!$this->isPrimitiveType($returnType->getName())) {
				$this->namespace->addUse($returnType->getName());
				$testMethod->addBody('$this->assertInstanceOf(' . $this->getShortClassName($returnType->getName()) . '::class, $result);');
			} else {
				$testMethod->addBody('//$this->assertEquals("", $result);');
			}

			$testMethod->setPublic()->setReturnType('void');
		}
	}
}
