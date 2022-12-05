<?php
namespace Gut\Generator;

class RuleTestGenerator extends BaseGenerator
{
	protected function createSetUpMethod(): void
	{
		$method = $this->testClass->addMethod('setUp');
		$method->setProtected()->setReturnType('void');

		$this->print('Detecting required dependencies for constructor...');
		$ruleParams = [];
		foreach ($this->publicMethods as $publicMethod) {
			$methodName = $publicMethod->getShortName();

			switch ($methodName) {
				case '__construct':
					$parameters = $publicMethod->getParameters();

					foreach ($parameters as $param) {
						$paramName = $param->getName();
						$type = $this->getParameterType($param);
						$this->namespace->addUse($type);
						$shortType = $this->getShortClassName($type);
						$method->addBody("\$this->{$paramName} = \$this->getMockWithoutConstructor({$shortType}::class);");

						$ruleParams[] = '$this->' . $paramName;
					}

					break;
			}
		}

		$method = $this->testClass->addMethod('createRule');
		$method->setPrivate()->setReturnType($this->targetClass);
		$method->addBody("\$rule = new {$this->baseClassName}(" . implode(', ', $ruleParams) . ');');
		$method->addBody('return $rule;');
	}

	protected function createTestMethods(): void
	{
		foreach ($this->publicMethods as $publicMethod) {
			$methodName = $publicMethod->getShortName();

			if ('__construct' == $methodName) {
				continue;
			}

			$testMethod = $this->testClass->addMethod('test_' . $methodName . '_ValidParameters_ReturnTrue');

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
			$testMethod->addBody('$rule = $this->createRule();');
			$testMethod->addBody('$result = $rule->' . $methodName . '(' . implode(', ', $params) . ');');
			$testMethod->addBody('$this->assertTrue($result);');
			$testMethod->setPublic()->setReturnType('void');

			break;
		}
	}
}
