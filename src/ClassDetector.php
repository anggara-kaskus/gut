<?php
namespace Gut;

class ClassDetector
{
	public const TYPE_ENTITY = 'Entity';
	public const TYPE_SERVICE = 'Service';
	public const TYPE_FACTORY = 'Factory';
	public const TYPE_REPOSITORY = 'Repository';
	private $baseNamespace = '';
	private $namespace = '';
	private $class = '';

	public function __construct($filename)
	{
		if (!file_exists($filename)) {
			throw new Exception('File not found: ' . $filename);
		}

		$this->filename = $filename;
		$this->tokenize();
	}

	public function getObjectType()
	{
		return $this->baseNamespace;
	}

	public function getFullClassName()
	{
		return $this->namespace . '\\' . $this->class;
	}

	private function tokenize(): void
	{
		$fp = fopen($this->filename, 'r');
		$buffer = '';
		$i = 0;
		while (!$this->class) {
			if (feof($fp)) {
				break;
			}
			$buffer .= fread($fp, 512);
			$tokens = @token_get_all($buffer);

			if (false === strpos($buffer, '{')) {
				continue;
			}
			for (; $i < count($tokens); ++$i) {
				if (T_NAMESPACE === $tokens[$i][0]) {
					for ($j = $i + 1; $j < count($tokens); ++$j) {
						if (T_STRING === $tokens[$j][0]) {
							$this->baseNamespace = $tokens[$j][1];
							$this->namespace .= '\\' . $this->baseNamespace;
						} elseif ('{' === $tokens[$j] || ';' === $tokens[$j]) {
							break;
						}
					}
				}

				if (T_CLASS === $tokens[$i][0]) {
					for ($j = $i + 1; $j < count($tokens); ++$j) {
						if ('{' === $tokens[$j]) {
							$this->class = $tokens[$i + 2][1];
						}
					}
				}
			}
		}
	}
}
