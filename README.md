# Engine
A file-based all-in-one module architecture for PHP
* Secure by default and self-validating
* Automatic unit testing, benchmarking, and documentation

## Usage
One-time initialization:
```php
define(ENGINE_ROOT, 'path/to/engines');
require_once('engine.php');
```
Calling the engine file /temporal/hourNumberToString.php:
```php
$str_hour = call('temporal', 'hourNumberToString', array(
	'numeric_hour' => 2330,
	'noonAndMidnight' => true,
));
echo $str_hour; // "11:30 pm"
```

## Automatic Features
### Validation
```php
$str_hour = call('temporal', 'hourNumberToString', array(
	'numeric_hour' => 'WHOOPS!',
	'noonAndMidnight' => true,
));
// E_USER_ERROR: invalid parameter "numeric_hour" in /temporal/hourNumberToString
```
### Documentation
```php
echo engineDetails('event', 'get');
/*
[
	description => "Retrieve a single event from the database",
	params => [
		id => [
			type => "positive int",
			required => true,
			default => null,
		],
		ignoreLinks => [
			type => "bool",
			required => false,
			default => false,
		],
		userId => [
			type => "user id",
			required => false,
			default => null,
		],
	],
]
*/
```
### Testing
```php
echo engineResults('temporal', 'hourNumberToString');
/*
*/
```
