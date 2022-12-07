<?php

namespace Gut;

trait Output
{
	public function print($message): void
	{
		echo "\033[36m{$message}\033[0m";
	}

	public function println($message): void
	{
		echo $this->print($message) . "\n";
	}
}
