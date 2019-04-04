# Engine
A single-file functional paradigm for PHP.

## Usage
**Initialization**
```php
define(ENGINE_ROOT, 'path/to/engines');
require_once('engine.php');
```
**Calling**
```php
$str_hour = call('temporal', 'hourNumberToString', array(
	'numeric_hour' => 2330,
  'noonAndMidnight' => true,
));
echo $str_hour; // "11:30 pm"
```

## Automatic Features
#Testing**
```php
$str_hour = call('temporal', 'hourNumberToString', array(
	'numeric_hour' => 2330,
  'noonAndMidnight' => true,
));
echo $str_hour; // "11:30 pm"
```
