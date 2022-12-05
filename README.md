# gut
Generate unit test


## Installation

### Globally (preferred)

Modify your composer global config

```bash
nano `composer config --global home`/config.json
```

```json
{
    "config": {},
    "repositories": [
        {
            "type": "git",
            "url":  "https://github.com/anggara-kaskus/gut.git"
        }
    ]
}
```

`cd` into your project directory, and then run:
```bash
composer require anggara-kaskus/gut:dev-develop 
```

### Locally
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

From the project's root folder:

```
./vendor/bin/gut /full/path/to/target/file.php
```

<details>
<summary>Example</summary>
<img alt="Screen Shot 2022-12-06 at 02 04 08" src="https://user-images.githubusercontent.com/7742225/205721428-12551b55-f2b3-4575-8ac1-b2d4622b88fd.png">
</details>

## Limitation
|Type|Rule and Limitation|
|---|---|
|Entity|Will detect attibute name from setter methods and convert it to snake_case, and its data type from typehint.<br>If there is no typehint specified, it will be assumed as `string`<br><br>Example:<br>- `setCommunityId(?int $value)` -> `'community_id' (int)`<br>- `setPIC(string $value)` -> `'p_i_c' (string)` (Note the underscores before every uppercase letters)<br>- `setId(string $id)` -> `'_id' (string)` (Special case, to respect `MongoId`)|
|Factory|Only generate skeleton, need to manually add assertion for set values|
|Rule|Only generate skeleton, need to manually add assertion and logic branches|
|Repository|Only generate skeleton|
|Service|Only generate skeleton|
|Presenter|Only generate skeleton|