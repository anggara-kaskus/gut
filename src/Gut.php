<?php
namespace Gut;

use Gut\Generators\EntityTestGenerator;

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

		switch ($objectType) {
			case ClassDetector::TYPE_ENTITY:
				$generator = new EntityTestGenerator($fullClassName);
				$targetFile = str_replace(['src/', '.php'], ['tests/', 'Test.php'], $sourceFile);
				if ($result = $generator->generate()) {
					file_put_contents($targetFile, $result);
					$this->print("Successfully created test file: {$targetFile}");
					$this->runUnitTest($targetFile);
				}

				break;

			default:
				echo 'Unhandled object type: ' . $cd->getObjectType();
		}
	}

	private function runUnitTest($targetFile): void
	{
		$this->print('Running unit test against generated file...');

		$returnCode = 0;
		passthru("./vendor/kaskus/kaskus-phar/archive/phpunit --color=always {$targetFile}", $returnCode);

		if (0 != $returnCode) {
			$this->print(PHP_EOL . PHP_EOL . 'Unfortunately, there are some test errors we could not fix. Please check generated test file.');
		} else {
			$this->print(PHP_EOL . PHP_EOL . 'Seems good!');
		}
	}
}
