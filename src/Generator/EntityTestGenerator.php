<?php
namespace Gut\Generator;

class EntityTestGenerator extends BaseGenerator
{
	protected function createSetUpMethod(): void
	{
		$method = $this->testClass->addMethod('setUp');
		$method->setProtected()->setReturnType('void');
		$method->addBody('$this->assocArray = [];');

		$this->print('Detecting class attributes from setter methods...');
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

				$returnType = $this->getParameterType($publicMethod->getParameters()[0]);

				$this->print("  - {$attribute} (" . ($returnType ?: 'unspecified') . ')');

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

	protected function createTestMethods(): void
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
