# Engine
File-based module architecture for PHP with:
* Automatic parameter validation
* Highly dense unit testing, integrated by default
* Automatic benchmarking and documentation

## Usage
One-time initialization:
```php
define(ENGINE_ROOT, 'path/to/engines');
require_once('engine.php');
```
Calling an engine file:
```php
$str_hour = call('temporal', 'hourNumberToString', [
	'numeric_hour' => 2330,
	'noonAndMidnight' => true,
]);
echo $str_hour; // "11:30 pm"
```

## Automatic Features
### Validation
```php
$str_hour = call('temporal', 'hourNumberToString', [
	'numeric_hour' => 'WHOOPS!',
]);
// E_USER_ERROR: invalid parameter "numeric_hour" in /temporal/hourNumberToString (expected non-negative int)
```
Example parameter types:
* `'array'`
* `'1-6 char string'`
* `'venue id'`
* `'email'`
* `'positive int'`

### Documentation
```php
echo engineDetails('event', 'get');
/* output: [
	location => "/engines/event/get.php"
	description => "Retrieve a single event from the database",
	parameters => [
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
### Testing & Benchmarking
```php
echo engineResults('temporal', 'hourNumberToString');
/* output: [
	tests => [
		single => [
			total => 12,
			passing => 12,
			cases => [ ... ],
		],
		full => [
			total => 7,
			passing => 5,
			cases => [ ... ],
		],
	],
	performance => [
		ms_maxDuration => 54.843,
		kb_maxMemory => 368.539,
	],
]
*/
```

## Example File
```php
<?php
// /engines/temporal/monthNumberToString
	
if ($desc) return <<<'DESC'
	
	Provide consistent site-wide month/date formats for our app
	input:
		month as int, starting in January 2010
		style as one of several predefined letters
	output:
		human-readable month string
DESC;
	
if ($init) { return [
	[ 'name' => 'numeric_month',  'type' => 'int',     'required' => true,   'default' => null ],
	[ 'name' => 'style',          'type' => 'string',  'required' => false,  'default' => 'a' ],
]; }

if ($test) { return [
	'single' => [
		'numeric_month' => [
			'safe' => 3, // March
			'pass' => [ 1, 9, 12 ], // [ January, September, December ],
			'fail' => [ 0, 13, 25, -2, [] ],
		],
		'style' => [
			'safe' => 'a',
			'pass' => [ 'b', 'c' ],
			'fail' => [ 'foo', 12, null, [] ],
		],
	],
	'full' => [
		[ 'input' => ['numeric_month' => 1],   'output' => 'January 2010'  ],
		[ 'input' => ['numeric_month' => 60],  'output' => 'December 2014' ],
		[ 'input' => ['numeric_month' => 61],  'output' => 'January 2015'  ],
		[ 'input' => ['numeric_month' => 66],  'output' => 'June 2015'     ],
		[ 'input' => ['numeric_month' => 60, 'short' => true],  'output' => "Dec '14" ],
		[ 'input' => ['numeric_month' => 61, 'short' => true],  'output' => "Jan '15" ],
	],
]; }
	

	/* convert monthNum to month and year */ if (true) {
		
		$monthOfTheYear = ($numeric_month - 1) % 12 + 1;
	
		$yearNum = 2010 + ($numeric_month - $monthOfTheYear) / 12;
	}
	
	if ('a' === $style) { // November 2014 (default)
		
		return date('F', mktime(0, 0, 0, $monthOfTheYear, 10)) . ' ' . $yearNum;

	} else if ('b' === $style) { // Nov '14
		
		return date('M', mktime(0, 0, 0, $monthOfTheYear, 10)) . " '" . substr($yearNum, -2);
		
	} else if ('c' === $style) { // 2014-11
		
		return $yearNum . '-' . str_pad($monthOfTheYear, 2, '0', STR_PAD_LEFT);
		
	} else {
	
		trigger_error('unrecognized style code');
	}
```
