<?php
namespace Gut;

use Gut\Generators\EntityTestGenerator;
use Gut\Generators\FactoryTestGenerator;

class Gut
{
	use Output;

	public function generate($sourceFile): void
	{
		$cd = new ClassDetector($sourceFile);
		$fullClassName = $cd->getFullClassName();
		$objectType = $cd->getObjectType();

		$this->print("Detected class: {$fullClassName}");
		$this->print("Object type is: {$objectType}");

		$targetFile = str_replace(['src/', '.php'], ['tests/', 'Test.php'], $sourceFile);
		switch ($objectType) {
			case ClassDetector::TYPE_ENTITY:
				$generator = new EntityTestGenerator($fullClassName);
				break;

			case ClassDetector::TYPE_FACTORY:
				$generator = new FactoryTestGenerator($fullClassName);
				break;

			default:
				echo 'Unhandled object type: ' . $cd->getObjectType();
				return;
		}

		if ($result = $generator->generate()) {
			file_put_contents($targetFile, $result);
			$this->print("Successfully created test file: {$targetFile}");
			$this->runUnitTest($targetFile);
		}

	}

	private function runUnitTest($targetFile): void
	{
		$this->print("\nRunning unit test against generated file...");

		$returnCode = 0;
		passthru("./vendor/kaskus/kaskus-phar/archive/phpunit --testdox --color=always {$targetFile}", $returnCode);

		if (0 != $returnCode) {
			$this->print(PHP_EOL . PHP_EOL . 'Unfortunately, there are some test errors we could not fix. Please check generated test file.');
		} else {
			$this->print(PHP_EOL . PHP_EOL . 'Seems good!');
		}
	}
}
