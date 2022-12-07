<?php
namespace Gut;

use Gut\Generator\EntityTestGenerator;
use Gut\Generator\FactoryTestGenerator;
use Gut\Generator\PresenterTestGenerator;
use Gut\Generator\RepositoryTestGenerator;
use Gut\Generator\ServiceTestGenerator;

class Gut
{
	use Output;

	public function generate($sourceFile): void
	{
		$classDetector = new ClassDetector($sourceFile);
		$fullClassName = $classDetector->getFullClassName();
		$objectType = $classDetector->getObjectType();

		$this->println("Detected class: {$fullClassName}");
		$this->println("Object type is: {$objectType}");

		if (ClassDetector::TYPE_PRESENTER == $objectType) {
			$targetFile = str_replace(['system/application/Kaskus/Forum', '.php'], ['test/UnitTests/Kaskus/Forum/', 'Test.php'], $sourceFile);
		} else {
			$targetFile = str_replace(['src/', '.php'], ['tests/', 'Test.php'], $sourceFile);
		}

		if (file_exists($targetFile)) {
			$this->print('Target file exists. Override? [y/N] ');
			$input = rtrim(fgets(STDIN));
			if (!in_array($input, ['y', 'Y'])) {
				exit(1);
			}
		}

		switch ($objectType) {
			case ClassDetector::TYPE_ENTITY:
				$generator = new EntityTestGenerator($fullClassName);

				break;

			case ClassDetector::TYPE_FACTORY:
				$generator = new FactoryTestGenerator($fullClassName);

				break;

			case ClassDetector::TYPE_SERVICE:
				$generator = new ServiceTestGenerator($fullClassName);

				break;

			case ClassDetector::TYPE_REPOSITORY:
				$generator = new RepositoryTestGenerator($fullClassName);

				break;

			case ClassDetector::TYPE_PRESENTER:
				$generator = new PresenterTestGenerator($fullClassName);

				break;

			default:
				echo 'Unhandled object type: ' . $cd->getObjectType();

				return;
		}

		if ($result = $generator->generate()) {
			$targetFolder = dirname($targetFile);
			if (!file_exists($targetFolder)) {
				mkdir($targetFolder, 0755, true);
			}
			file_put_contents($targetFile, $result);
			$this->println("Successfully created test file: {$targetFile}");
			$this->runUnitTest($targetFile, $objectType);
		}
	}

	private function runUnitTest(string $targetFile, string $objectType): void
	{
		$this->println("\nRunning unit test against generated file...");

		$returnCode = 0;
		if (ClassDetector::TYPE_PRESENTER == $objectType) {
			passthru("./vendor/kaskus/kaskus-phar/archive/phpunit --testdox --color=always -c others/build/phpunit.xml {$targetFile}", $returnCode);
		} else {
			passthru("./vendor/kaskus/kaskus-phar/archive/phpunit --testdox --color=always {$targetFile}", $returnCode);
		}

		if (0 != $returnCode) {
			$this->println(PHP_EOL . PHP_EOL . 'Unfortunately, there are some test errors we could not fix. Please check generated test file.');
		} else {
			$this->println(PHP_EOL . PHP_EOL . 'Seems good!');
		}
	}
}
