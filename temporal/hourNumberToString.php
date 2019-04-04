<?php
//	/engines/temporal/hourNumberToString

if ($desc) return <<<'DESC'

  input:
  numeric_hour as hour number (one of the <temporal data types>)
  charLimit as int - setting this to a smaller value will abreviate the string.
  output:
  For charLimit values 8 or above (when $noonAndMidnight = false) output will be:
    11:00 pm, 11:30 pm, 12:00 am, 12:30 am, ...
  For charLimit values 8 or above (when $noonAndMidnight = true) output will be:
    11:00 pm, 11:30 pm, Midnight, 12:30 am, ...
  For charLimit values 6 or 7, output will be:
    11pm, 11:30p, 12a, 12:30a, ...
  For charLimit value 5, output will be:
    11pm, 11:30, 12a, 12:30, ...
  TODO: change the return data structure to give additional numDays info for values above 23.5
DESC;

if ($init) { return array(
  array('name' => 'numeric_hour',		'type' => 'non-negative int',	'required' => true,		'default' => null),
  array('name' => 'charLimit',		'type' => 'positive int',		'required' => false,	'default' => null),
  array('name' => 'noonAndMidnight',	'type' => 'bool',				'required' => false,	'default' => false),
  array('name' => 'ideal',			'type' => 'bool',				'required' => false,	'default' => false),
  array('name' => 'compactAmPm',		'type' => 'bool',				'required' => false,	'default' => false),
  //array('name' => 'alwaysShowMinutes', 'type' => 'bool', 'required' => false, 'default' => false),
); }

if ($test) { return array(
  'single' => array(
    'numeric_hour' => array(
      'safe' => 1830, // 18.5,
      'pass' => array(1830, '1830', 0, 2600), // array('18.5', 18.5, 0, 26),
      'fail' => array(array()),
    ),
    'charLimit' => array(
      'safe' => 8,
      'pass' => array(8, 5, 10),
      'fail' => array(-3, 0, 2.5, null, array()),
    ),
    'noonAndMidnight' => array(
      'safe' => true,
      'pass' => array(true, false),
      'fail' => array(-3, 2.5, null, array()),
    ),
  ),
  'full' => array(
    array('input' => array('numeric_hour' => 2300,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '11:00 pm'	),
    array('input' => array('numeric_hour' => 2300,	'charLimit' => null,	'noonAndMidnight' => true	), 'output' => '11:00 pm'	),
    array('input' => array('numeric_hour' => 2300,	'charLimit' => 6,		'noonAndMidnight' => true	), 'output' => '11pm'		),
    array('input' => array('numeric_hour' => 2300,	'charLimit' => 5,		'noonAndMidnight' => true	), 'output' => '11pm'		),

    array('input' => array('numeric_hour' => 2330,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '11:30 pm'	),
    array('input' => array('numeric_hour' => 2330,	'charLimit' => null,	'noonAndMidnight' => true	), 'output' => '11:30 pm'	),
    array('input' => array('numeric_hour' => 2330,	'charLimit' => 6,		'noonAndMidnight' => true	), 'output' => '11:30p'		),
    array('input' => array('numeric_hour' => 2330,	'charLimit' => 5,		'noonAndMidnight' => true	), 'output' => '11:30'		),

    array('input' => array('numeric_hour' => 2400,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '12:00 am'	),
    array('input' => array('numeric_hour' => 0000,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '12:00 am'	),
    array('input' => array('numeric_hour' => 2400,	'charLimit' => null,	'noonAndMidnight' => true	), 'output' => 'Midnight'	),
    array('input' => array('numeric_hour' => 2400,	'charLimit' => 6,		'noonAndMidnight' => true	), 'output' => '12am'		),
    array('input' => array('numeric_hour' => 2400,	'charLimit' => 5,		'noonAndMidnight' => true	), 'output' => '12am'		),

    array('input' => array('numeric_hour' => 30,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '12:30 am'	),
    array('input' => array('numeric_hour' => 30,	'charLimit' => null,	'noonAndMidnight' => true	), 'output' => '12:30 am'	),
    array('input' => array('numeric_hour' => 30,	'charLimit' => 6,		'noonAndMidnight' => true	), 'output' => '12:30a'		),
    array('input' => array('numeric_hour' => 30,	'charLimit' => 5,		'noonAndMidnight' => true	), 'output' => '12:30'		),

    array('input' => array('numeric_hour' => 100,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '1:00 am'	),
    array('input' => array('numeric_hour' => 100,	'charLimit' => null,	'noonAndMidnight' => true	), 'output' => '1:00 am'	),
    array('input' => array('numeric_hour' => 100,	'charLimit' => 6,		'noonAndMidnight' => true	), 'output' => '1am'		),
    array('input' => array('numeric_hour' => 100,	'charLimit' => 5,		'noonAndMidnight' => true	), 'output' => '1am'		),

    array('input' => array('numeric_hour' => 1200,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '12:00 pm'	),
    array('input' => array('numeric_hour' => 1200,	'charLimit' => null,	'noonAndMidnight' => true	), 'output' => 'Noon'		),
    array('input' => array('numeric_hour' => 1200,	'charLimit' => 6,		'noonAndMidnight' => true	), 'output' => 'Noon'		),
    array('input' => array('numeric_hour' => 1200,	'charLimit' => 5,		'noonAndMidnight' => true	), 'output' => 'Noon'		),
    array('input' => array('numeric_hour' => 1200,	'charLimit' => 5,		'noonAndMidnight' => false	), 'output' => '12pm'		),

    array('input' => array('numeric_hour' => 2630,	'charLimit' => null,	'noonAndMidnight' => false	), 'output' => '2:30 am'	),

    array('input' => array('numeric_hour' => 230,	'ideal' => true	), 'output' => '2:30a'		),
    array('input' => array('numeric_hour' => 1900,	'ideal' => true	), 'output' => '7p'			),
    array('input' => array('numeric_hour' => 1200,	'ideal' => true	), 'output' => 'Noon'		),
    array('input' => array('numeric_hour' => 2400,	'ideal' => true	), 'output' => 'Midnight'	),
  ),
); }
	

	if (is_array($numeric_hour) or !is_numeric($numeric_hour)) return trigger_error('invalid hour input: ' . $numeric_hour); // return error('invalid input');
	//if ($hour < 0) return array('error', 'hour must be positive');

	if ($ideal) {
	
		$noonAndMidnight = true;
		$charLimit = 10;
		
	} else {
	
		if (empty($charLimit)) $charLimit = 10;
	}

	if ($numeric_hour > 2400) {
	
		//return error('hour number is more than 1 day long');
		$numeric_hour = $numeric_hour % 2400;
	}
	
	$hourPortion = (int)($numeric_hour / 100);
	$minutePortion = $numeric_hour % 100;

	if ((0 == $minutePortion) && $noonAndMidnight) {
	
		if ( ((0 == $hourPortion) || (24 == $hourPortion)) && ($charLimit >= 8) ) return 'Midnight';

		if ((12 == $hourPortion) && ($charLimit >= 4)) return 'Noon';
	}
	
	if (2400 == $numeric_hour) {
	
		$hourPortion = 12;
		$am = true;
	
	} else if ((0 == $hourPortion) || (24 == $hourPortion)) {
	
		$hourPortion = 12;
		$am = true;
	
	} else if ($hourPortion > 12) {
	
		$hourPortion = $hourPortion - 12;
		$am = false;
		
	} else if (12 == $hourPortion) {
	
		$am = false;
		
	} else {
	
		$am = true;
	}
	
	if (0 == $minutePortion) {
	
		$halfHour = false;
		
	} elseif (30 == $minutePortion) {
	
		$halfHour = true;
		
	} else {
	
		//return array('error', 'time must be on the hour or half-hour');
		trigger_error('numeric_hour must be on the hour or half-hour e.g. 900 or 1830: ' . $numeric_hour);
	}
	
	if ($ideal) {
	
		if ($halfHour) {
			
			return $hourPortion . ':30' . (($am)? 'a' : 'p');
			
		} else {
		
			return $hourPortion . (($am)? 'a' : 'p');
		}
	}
	
	if ($charLimit <= 5) {
	
		if ($halfHour) {
			
			return $hourPortion . ':30';
			
		} else {
		
			return $hourPortion . (($am)? 'am' : 'pm');
		}
	}
	
	if ($charLimit <= 7) {
	
		if ($halfHour) {
			
			return $hourPortion . ':30' . (($am)? 'a' : 'p');
			
		} else {
		
			return $hourPortion . (($am)? 'am' : 'pm');
		}
	}
	
	if ($compactAmPm) {
	
		return $hourPortion . (($halfHour)? ':30' : ':00') . (($am)? 'a' : 'p');
		
	} else {
	
		return $hourPortion . (($halfHour)? ':30 ' : ':00 ') . (($am)? 'am' : 'pm');
	}
