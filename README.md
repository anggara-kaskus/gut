# gut
Generate unit test

## Installation

Modify your composer.json:
```json
{
    "repositories": [
		{
			"type": "git",
			"url":  "https://github.com/anggara-kaskus/gut.git"
		}
	],
	"require": {
		"anggara-kaskus/gut":"dev-develop"
	},
}
```

then run

```bash
composer update
```

## Usage

from project root folder:

```
# kaskus-core-forum
./vendor/bin/gut /full/path/to/target/file.php
```