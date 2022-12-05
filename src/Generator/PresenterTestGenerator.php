<?php
namespace Gut\Generator;

use Exception;

class PresenterTestGenerator extends BaseGenerator
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

						if ('CI_Base' == $type) {
							$method->addBody("\$this->{$paramName} = new CI_Base();");
						} else {
							$method->addBody("\$this->{$paramName} = \$this->getMockWithoutConstructor({$shortType}::class);");
						}

						$ruleParams[] = '$this->' . $paramName;
					}

					break;
			}
		}

		$method = $this->testClass->addMethod('createPresenter');
		$method->setPrivate()->setReturnType($this->targetClass);
		$method->addBody("\$presenter = new {$this->baseClassName}(" . implode(', ', $ruleParams) . ');');
		$method->addBody('return $presenter;');
	}

	protected function createTestMethods(): void
	{
		foreach ($this->publicMethods as $publicMethod) {
			$methodName = $publicMethod->getShortName();

			if ('__construct' == $methodName) {
				continue;
			}

			$testMethod = $this->testClass->addMethod('test_' . $methodName . '_TestCase_ExpectedOutcome');

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
						if (class_exists($type)) {
							$this->namespace->addUse($type);
							$testMethod->addBody('$' . $param->getName() . ' = $this->getMockWithoutConstructor(' . $this->getShortClassName($type) . '::class);');
						} else {
							throw new Exception("Unhandled parameter type: {$methodName}() -> {$type} \$" . $param->getName());
						}
				}
			}
			$testMethod->addBody('$presenter = $this->createPresenter();');
			$testMethod->addBody('$result = $presenter->' . $methodName . '(' . implode(', ', $params) . ');');
			$testMethod->setPublic()->setReturnType('void');
		}
	}
}
