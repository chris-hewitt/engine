<?
/*
	engine.php
	
	All the wrapper functions that interface with an <engine> file.
	
	NOTE: to be able to test properly, an engine should never trigger_error. it must always return a result.
		  if there is an error, return array('error', '[error message]')
]*/

	function call($category, $function, $params = array(), $testMode = false) {
	
		/* track memory perfomance */ if (true) {
			
			if (!empty($GLOBALS['chewittcom']) && isDebugMode()) {
			
				$bytes_startMemory = memory_get_usage();
			}
		}
			
		/* track time perfomance */ if (true) {
		
			$currentEngine = $category . '/' . $function;
			
			// bank resource usage to the PARENT engine from the last timer point to now, and reset the timer
			bankComponentUsage('engine');
			
			if (empty($GLOBALS['performance']['currentEngine'])) { // this is the first time any engine has been called
			
			} else {
			
				$parentEngine = $GLOBALS['performance']['currentEngine'];
				
				if ('core/validate' === $currentEngine) {
				
					$GLOBALS['performance']['engines'][$parentEngine]['validateCalls']++;
					
				} else {
				
					$GLOBALS['performance']['engines'][$parentEngine]['subCalls']++;
				}
			}
			
			// time past this point should be banked to THIS engine
			startBankingToNewComponentInstance('engine', $currentEngine);
			$seconds_birthTime = $GLOBALS['performance']['engineTimer'];
		}
		
		$fileName = engineGetFileName($category, $function);
		
		$paramDefinitions = engineGetDefinition($fileName);
		
		/* process and validate parameters */ if (true) {
		
			if (!empty($paramDefinitions)) {
			
				try {
				
					$params = engineProcessParams($paramDefinitions, $params, '/' . $category . '/' . $function);
					
				} catch (Exception $e) {
					
					if ($testMode) {
					
						return error('[in engine ' . $category . '/' . $function . ']: ' . $e->getMessage());
						
					} else {
					
						trigger_error($e->getMessage(), E_USER_ERROR);
					}
				}
			}
		}
		
		/* execute the action */ if (true) {
		
			$result = engineExecute($fileName, $params, '/' . $category . '/' . $function);
		}
		
		/* error handling */ if (true) {
		
			if (isError($result)) {
			
				$errors = $result[1];
				
				if (!is_array($errors) && !empty($GLOBALS['chewittcom']) && isDebugMode()) { // if the error value is an array, it's for percolating up as a form error, so leave it clean
				
					$errors = '[in engine ' . $category . '/' . $function . ']: ' . $errors;
				}
				
				return error($errors);
			}
		}
		
		/* track time perfomance */ if (true) {
		
			// bank all resource usage to the current engine from the last timer point to now, and reset the timer
			bankComponentUsage('engine');
			
			// component is finished so record its lifespan
			$seconds_alive = $GLOBALS['performance']['engineTimer'] - $seconds_birthTime;
			$GLOBALS['performance']['engines'][$currentEngine]['ms_alive'] = round($GLOBALS['performance']['engines'][$currentEngine]['ms_alive'] + $seconds_alive * 1000, 2);
			
			/* space-saving single-string view
			$GLOBALS['performance']['list'] .=
				'engine: ' . $category . '/' . $function .
				'ms_duration: ' . ($seconds_duration * 1000) .
				'bytes_memoryFootprint: ' . $bytes_memoryPeak . '\n';
			*/
			
			/* show every call in order
			$GLOBALS['performance'][$profilingIndex] = array(
				'engine' => $category . '/' . $function,
				'ms_duration' => ($seconds_duration * 1000),
				'bytes_memoryFootprint' => $bytes_memoryPeak,
			);
			*/
			
			if (!empty($parentEngine)) {
			
				$GLOBALS['performance']['currentEngine'] = $parentEngine;
			}
		}
		
		if (!empty($GLOBALS['chewittcom']) && isDebugMode()) {
			
			/* track memory perfomance */ if (true) {
			
				$bytes_memoryPeak = memory_get_peak_usage() - $bytes_startMemory;
				$GLOBALS['performance']['engines'][$currentEngine]['kb_memoryFootprint'] += round($bytes_memoryPeak / 1000);
			}
			
			unset($GLOBALS['lastEngine_startParams']); // the engine executed successfully, so we can forget about the debugging data
		}
		
		return $result;
	}
	
	function startBankingToNewComponentInstance($componentType, $componentName) {
	
		if (!empty($GLOBALS['performance']['current' . ucfirst($componentType)])) {
		
			$parentComponent = $GLOBALS['performance']['current' . ucfirst($componentType)];
		}
		
		$GLOBALS['performance']['current' . ucfirst($componentType)] = $componentName;

		if (empty($GLOBALS['performance'][$componentType . 's'][$componentName])) { // this is the first time this component has been called
		
			$GLOBALS['performance'][$componentType . 's'][$componentName]['seconds_duration'] = 0;
			$GLOBALS['performance'][$componentType . 's'][$componentName]['numCalls'] = 0;
			$GLOBALS['performance'][$componentType . 's'][$componentName]['ms_alive'] = 0;
			$GLOBALS['performance'][$componentType . 's'][$componentName]['ms_working'] = 0;
			$GLOBALS['performance'][$componentType . 's'][$componentName]['kb_memoryFootprint'] = 0;
			$GLOBALS['performance'][$componentType . 's'][$componentName]['subCalls'] = 0;
			$GLOBALS['performance'][$componentType . 's'][$componentName]['validateCalls'] = 0;
		}
		
		$GLOBALS['performance'][$componentType . 's'][$componentName]['numCalls']++;
		
		if (!empty($parentComponent)) {
		
			if (empty($GLOBALS['performance'][$componentType . 's'][$componentName]['calledBy'][$parentComponent])) {
			
				$GLOBALS['performance'][$componentType . 's'][$componentName]['calledBy'][$parentComponent] = 1;
				
			} else {
			
				$GLOBALS['performance'][$componentType . 's'][$componentName]['calledBy'][$parentComponent]++;
			}
		}
	}

	function bankComponentUsage($componentType) {
	
		if (empty($GLOBALS['performance']['current' . ucfirst($componentType)])) { // this is the beginning of the first call to this type of component
	
			$GLOBALS['performance'][$componentType . 'Timer'] = microtime(true);
			return;
		}
		
		$componentName = $GLOBALS['performance']['current' . ucfirst($componentType)];
		
		$startTime = $GLOBALS['performance'][$componentType . 'Timer'];

		// set or reset the timer
		$GLOBALS['performance'][$componentType . 'Timer'] = microtime(true);
		
		$seconds_newComponentActivity = $GLOBALS['performance'][$componentType . 'Timer'] - $startTime;
	
		$GLOBALS['performance'][$componentType . 's'][$componentName]['ms_working'] = round($GLOBALS['performance'][$componentType . 's'][$componentName]['ms_working'] + $seconds_newComponentActivity * 1000, 2);
	}
	
	function engineGetCachedTestSummary($category, $function) {
	// returns several metrics including how many tests there are, how many are passing, and performance data
	
		$siteName = engineDetermineSite($category, $function);
		
		$cacheFile = fileo(array(
			'path' => array('cache', 'testResults', $siteName, $category),
			'filename' => $function . '.txt',
		));
		
		if (!$cacheFile->exists()) return null;
		
		$summary = $cacheFile->contentsAsNestedArray($cacheFile);
		
		foreach ($summary as $key => $value) {
			
			$summary[$key] = firstKey($value);
			
			if (is_null($summary[$key])) $summary[$key] = 0;
		}
		
		return $summary;
	}
	
	function engineGetTestResults($category, $function, $site) {
		
		$fileName = engineGetFileName($category, $function);
		
		$results = array(
			'single' => testSingle($category, $function, $site),
			'validation' => testValidation($category, $function, $site),
			'full' => testFull($category, $function, $site),
			'summary' => array(),
		);
		
		//$validationResults = testValidation($category, $function, $site);

		$singleTotal = 0;
		$singlePassed = 0;
		
		//if (!empty($results['single']['cases'])) {
			
			foreach ($results['single'] as $paramName => $cases) {
			
				foreach ($cases as $caseName => $caseResult) {
					
					$singleTotal++;
					
					if ($caseResult['compliant']) $singlePassed++;
				}
			}
		//}
		
		$fullTotal = 0;
		$fullPassed = 0;
		
		foreach ($results['full']['cases'] as $case) {
			
			$fullTotal++;
			
			if ($case['compliant']) $fullPassed++;
		}
		
		$results['summary'] = array(
			'instant' => time(),
			'total' => ($singleTotal + $fullTotal),
			'passing' => ($singlePassed + $fullPassed),
			'ms_longestDuration' => round(max($results['validation']['ms_longestDuration'], $results['full']['ms_longestDuration']), 3),
			'kb_maxMemory' => round(max($results['validation']['kb_maxMemory'], $results['full']['kb_maxMemory']), 3),
		);
		
		/* every time we retest, store the result summary for quick reference or non-admin access */ if (true) {
		
			$siteName = engineDetermineSite($category, $function);

			/* in the filesystem cache */ if (true) {
				
				$cacheFile = fileo(array(
					'path' => array('cache', 'testResults', $siteName, $category),
					'filename' => $function . '.txt',
					'createDirectories' => true,
				));
				
				$cacheFile->overwriteContentsWithIndentedLines($results['summary'], 'delete all current content');
			}
			
			/* in the database */ if (true) {
				
				/*
				$result = dbSave('app_engine_test_results', parameterize(array(
					'site' => $siteName,
					'category' => $category,
					'function' => $function,
					'totalTests' => $results['summary']['total'],
					'passingTests' => $results['summary']['passing'],
					'msLongestDuration' => $results['summary']['ms_longestDuration'],
					'kbMaxMemory' => $results['summary']['kb_maxMemory'],
				)));
				*/
			}
		}
		
		return $results;
	}
	
	function testSingle($category, $function) {
	//test single parameters (using a default value for the rest) and check the return state
	
		$fileName = engineGetFileName($category, $function);
		
		//$paramDefinitions = engineGetDefinition($fileName);

		$testCases = engineGetTestCases($fileName);
		
		/* get an array of safe values for required params */ if (true) {
		
			$safeArray = array();
			
			foreach ($testCases['single'] as $paramName => $paramDef) {
				
				if (isset($paramDef['safe'])) {
				
					$safeArray[$paramName] = $paramDef['safe'];
					
				} else {
				
					trigger_error('[in engine ' . $category . DS . $function . ']: safe value not set for parameter "' . $paramName . '"');
				}
			}
		}
		
		$results = array();
		//$results['kb_maxMemory'] = 0; // TODO: make this work
		//$results['ms_longestDuration'] = 0; // TODO: make this work
		
		foreach ($testCases['single'] as $paramName => $paramDef) {
		
			foreach (array('pass', 'fail') as $expectedResult) {
			
				foreach ($paramDef[$expectedResult] as $value) {
				
					$input = array_merge($safeArray, array($paramName => $value));
				
					$output = call($category, $function, $input, true);
					
					$prettyValue = gettype($value) . '_' . $value . '_';
					
					$results[$paramName][$prettyValue]['expected'] = $expectedResult;
					
					if (isError($output)) { // we would expect the engine would usually fail when passing only one parameter
					
						$errors = $output[1];
						
						/*
						if (empty($errors[$paramName])) {
						
							$results[$paramName][$prettyValue]['actual'] = 'NO PARAM ERRORS';
							$results[$paramName][$prettyValue]['compliant'] = ('pass' == $expectedResult);
							
						} else {
						*/
							if (is_array($errors)) {
							
								$results[$paramName][$prettyValue]['actual'] = $errors[$paramName];
								
							} else {
							
								$results[$paramName][$prettyValue]['actual'] = $errors;
							}
							
							$results[$paramName][$prettyValue]['compliant'] = ('fail' == $expectedResult);
						//}
						
						$results[$paramName][$prettyValue]['errors'] = $errors;
						
					} else {
					
						$results[$paramName][$prettyValue]['actual'] = 'FUNCTION PASSED';
						$results[$paramName][$prettyValue]['compliant'] = ('pass' == $expectedResult);
					}
				}
			}
		}
		
		return $results;
	}
	
	function testFull($category, $function) {
	// test full cases and compare the expected return value with the actual
	
		$fileName = engineGetFileName($category, $function);
		
		//$paramDefinitions = engineGetDefinition($fileName);

		$testCases = engineGetTestCases($fileName);

		$results = array();
		$results['kb_maxMemory'] = 0;
		$results['ms_longestDuration'] = 0;
		$results['cases'] = array();

		$index = 0;
		
		foreach ($testCases['full'] as $case) {

			if ( (count($case) !== 2) || !hasKey($case, 'input') || !hasKey($case, 'output') ) trigger_error('each full test case must be in the form array(\'input\' => array(...), \'output\' => ...)');
			
			$results['cases'][$index] = engineExecuteTest($category, $function, $case['input'], $case['output']);
	
			if (!empty($case['description'])) {
			
				$results['cases'][$index]['description'] = $case['description'];
			}
			
			if ($results['cases'][$index]['kb_memoryFootprint'] > $results['kb_maxMemory']) {
			
				$results['kb_maxMemory'] = $results['cases'][$index]['kb_memoryFootprint'];
			}
			
			if ($results['cases'][$index]['ms_duration'] > $results['ms_longestDuration']) {
			
				$results['ms_longestDuration'] = $results['cases'][$index]['ms_duration'];
			}
			
			$index++;
		}
		
		return $results;
	}
	
	function testValidation($category, $function) {
	
		$fileName = engineGetFileName($category, $function);
		
		$desc = false;
		$init = true;
		$paramDefinitions = require($fileName);
	
		$desc = false;
		$init = false;
		$test = true;
		$testCases = require($fileName);

		$results = array();
		$results['kb_maxMemory'] = 0;
		$results['ms_longestDuration'] = 0;
		
		$index = 0;
		
		if (!empty($testCases['validation'])) {
		
			foreach ($testCases['validation'] as $type => $cases) {

				foreach ($cases as $case) {
			
					$results['cases'][$index] = engineExecuteTest($category, $function, array('type' => $type, 'value' => $case[0]), $case[1]);

					if ($results['cases'][$index]['kb_memoryFootprint'] > $results['kb_maxMemory']) {
					
						$results['kb_maxMemory'] = $results['cases'][$index]['kb_memoryFootprint'];
					}
					
					if ($results['cases'][$index]['ms_duration'] > $results['ms_longestDuration']) {
					
						$results['ms_longestDuration'] = $results['cases'][$index]['ms_duration'];
					}
					
					$index++;
				}
			}
		}
		
		return $results;
	}

	
	// private functions:

	function engineGetFileName($category, $function, $site = null) {
	/*	any engine can be implemented in the core app, or the site, or both.
		if the engine is implemented in both places, use the site-specific implementation.
	*/
	
		$site = engineDetermineSite($category, $function, $site);
		
		if ('app' === $site) {
			
			return engineFilenameApp($category, $function);
			
		} else {
			
			return engineFilenameSite($category, $function, $site);
		}
	}
	
	function engineFilenameApp($category, $function) {
		
		return APP_ROOT . '/engines/' . $category . '/' . $function . '.php';
	}
	
	function engineFilenameSite($category, $function, $site) {
		
		return VERSION_ROOT . '/sites/' . $site . '/engines/' . $category . '/' . $function . '.php';
	}
	
	function engineDetermineSite($category, $function, $site = null) {
	/*	any engine can be implemented in the core app, or in one or more individaul sites, or both.
		if the engine is implemented in both places, use the site-specific implementation.
	*/
	
		if (!is_string($category)) trigger_error('engine category was not a string: ' . $category);
		if (!is_string($function)) trigger_error('engine category was not a string: ' . $category);
		if (empty($category)) trigger_error('engine category was empty');
		if (empty($function)) trigger_error('engine category was empty');
		
		if (!empty($site)) {
			
			$fileName = engineFilenameSite($category, $function, $site);
			
			if (isValidResource($fileName)) return $site;
			
			trigger_error('site-specific engine not found: /' . $fileName);
		}
		
		// try current site root - is this a good idea?
		$siteFileName = SITE_ROOT . '/engines/' . $category . '/' . $function . '.php';
		
		if (isValidResource($siteFileName)) return SITE;
		
		// TODO: try other site roots?
		
		$appFileName = engineFilenameApp($category, $function);
		
		if (isValidResource($appFileName)) return 'app';
		
		trigger_error('engine not found: /' . $category . '/' . $function . ' using site ' . SITE . '(looking for ' . $appFileName . ')');
	}
	
	function engineGetDescription($fileName) {
		
		$desc = true;
		$init = true; // in case there's no $desc conditional in the engine file
		
		$description = require($fileName);
		
		if (!is_string($description)) return; // assume there was no $desc conditional and that we got the init array back instead
		
		$description = trim($description);
		
		if (empty($description)) return;
		
		return $description;
	}
	
	function engineGetDefinition($fileName) {
	// returns the parameter definition of an engine file
	// should probably be called engineGetParams or engineGetParamDefinition
	
		$desc = false;
		$init = true;
		
		$paramDefinitions = require($fileName);
		
		if (!is_array($paramDefinitions)) trigger_error('invalid parameter definition in engine file ' . $fileName);
		
		return $paramDefinitions;
	}
	
	function engineGetTestCases($fileName) {
	
		$desc = false;
		$init = false;
		$test = true;
		
		$cases = require($fileName);
		
		if (empty($cases)) trigger_error('no test case definition in engine file ' . $fileName);
		
		if ( !hasKey($cases, 'single') || !hasKey($cases, 'full') ) trigger_error('invalid case definition in engine file ' . $fileName);
		
		return $cases;
	}
	
	function engineProcessParams($expectedParams, $actualParams, $engineString) {
	/*	process the parameters sent to an engine and validate them based on what's expected from the engine definition
	
		there's a reason we throw exceptions instead of triggering errors in this function:
		if there's a validation error while running the engine as a test, we don't want to terminate execution, so throw an error that we can catch and test against
	*/
	
		if (!is_array($actualParams)) trigger_error("engine parameters must be passed as an array, e.g. call('foo', 'bar', array('baz' => 'quux'))");
		
		if (isset($expectedParams['validateInputType'])) {
		
			$validateInputType = $expectedParams['validateInputType'];
			
			unset($expectedParams['validateInputType']);
			
		} else {
		
			$validateInputType = true;
		}
		
		$cleanParams = array();
		
		foreach ($expectedParams as $expectedParam) {
		
			/* check that this parameter's definition is complete */ if (true) {

				if (!isset($expectedParam['name'])) throw new Exception('Parameter definition with no name in ' . $engineString);
				
				$paramName = $expectedParam['name'];
			
				foreach(array('type', 'required') as $item) {
				
					if (!isset($expectedParam[$item])) throw new Exception($paramName . ' parameter missing required definition component "' . $item . '" in ' . $engineString);
				}
			}
			
			//if (isset($actualParams[$paramName])) {
			if (array_key_exists($paramName, $actualParams)) {
			
				$paramIsSet = true;
				
				$paramValue = $actualParams[$paramName];
				
				unset($actualParams[$paramName]);
				
				if (!$validateInputType || (null === $paramValue) || ('any' === $expectedParam['type']) || ('/core/validate' === $engineString)) { //no validation will be performed!
				
					$cleanParams[$paramName] = $paramValue;
					
				} else {
				
					$result = call('core', 'validate', array('value' => $paramValue, 'type' => $expectedParam['type']));
					
					if (isError($result)) throw new Exception('invalid parameter "' . $paramName . '" in ' . $engineString . ': ' . $result[1] . '(value was [' . gettype($paramValue) . ']' . $paramValue . ')');
					
					$cleanParams[$paramName] = $result;
				}
			
			} else {
			
				if ($expectedParam['required']) throw new Exception('required parameter "' . $paramName . '" in ' . $engineString . ' was empty.');
				
				if (array_key_exists('default', $expectedParam)) {
					
					$cleanParams[$paramName] = $expectedParam['default'];
				}
			}
			
		}

		if (!empty($actualParams)) throw new Exception('unexpected param(s) sent to engine ' . $engineString . ': ' . implode(' & ', array_keys($actualParams)));

		return $cleanParams;
	}
	
	function engineExecute($fileNameXYZ, $paramsXYZ, $engineStringXYZ) {
	// use unusual parameter names to avoid variable name collisions when we call extract()
	
		extract($paramsXYZ);
		
		if (isset($desc)) trigger_error('cannot pass parameter named "desc"; it will conflict with engine variables');
		if (isset($init)) trigger_error('cannot pass parameter named "init"; it will conflict with engine variables');
		if (isset($test)) trigger_error('cannot pass parameter named "test"; it will conflict with engine variables');
		
		$desc = false;
		$init = false;
		$test = false;
		
		/*
			if (!isProduction()) {
			
				writeLog('latest_request', 'calling engine ' . $engineStringXYZ . ' with parameters ' . json_encode(get_defined_vars()) . '\n');
			}
		*/
		
		ob_start();
		
			//if ( !empty($GLOBALS['chewittcom']) && isDebugMode() ) {
			if (!empty($GLOBALS['chewittcom'])) {
			
				$GLOBALS['debug']['engine']['current']['beforeParams'] = get_defined_vars();
			}
			
			$result = require($fileNameXYZ);
			
			/*
				if (!isProduction()) {
					writeLog('latest_request', '...result: ' . $result . '\n');
				}
			*/
			
			//if ( !empty($GLOBALS['chewittcom']) && isDebugMode() ) {
			if (!empty($GLOBALS['chewittcom'])) {
			
				if ($GLOBALS['debug']['settings']['debugEngines']) {

					//$GLOBALS['debug']['engine']['current']['after'] = get_defined_vars();
					
					$GLOBALS['debug']['engine'] [] = array(
						'name' => $engineStringXYZ,
						'beforeParams' => $paramsXYZ,
						//'beforeParams' => $GLOBALS['debug']['engine']['current']['beforeParams'],
						//'afterParams' => get_defined_vars(),
						'return' => $result,
					);
					
				} else {
				
					unset($GLOBALS['debug']['engine']['current']['before']);
				}
			}
		
		$outputBuffer = ob_get_clean();
		
		if (!empty($outputBuffer)) {

			if (isDebugMode()) {
				
				echo $outputBuffer;
				
			} else {
				
				trigger_error('engine created output: ' . ( strlen($outputBuffer) > 50 ? substr($outputBuffer, 0, 50) . '...' : $outputBuffer ));
			}
		}
		
		return $result;
	}
	
	function engineExecuteTest($category, $function, $input, $expectedOutput) {

		$memoryBefore = memory_get_usage();
	
		$start = microtime(true);
	
		$output = call($category, $function, $input, true);
		
		$seconds_duration = microtime(true) - $start;
		$ms_duration = $seconds_duration * 1000;
	
		$memoryPeak = memory_get_peak_usage();
		
		$bytes_memoryFootprint = $memoryPeak - $memoryBefore;
		
		if (('pass' === $expectedOutput) || ('fail' === $expectedOutput)) {
		
			$simpleOutput = (isError($output)) ? 'fail' : 'pass';
			
			if ($expectedOutput === $simpleOutput) {
			
				$output = $simpleOutput;
			}
		}
		
		$results = array(
			'input' => $input,
			'expected' => $expectedOutput,
			'actual' => $output,
			'compliant' => ($expectedOutput === $output),
			'kb_memoryFootprint' => $bytes_memoryFootprint / 1024,
			'ms_duration' => $ms_duration,
		);
		
		return $results;
	}
	
	function x_benchmark($category, $function) {
	
		$init = false;
		$test = true;
		$testCases = require(APP_ROOT . DS . 'engines' . DS . $category . DS . $function . '.php');
	
		/* get an array of safe values for required params */ if (true) {
		
			$safeArray = array();
			foreach ($testCases['single'] as $paramName => $paramDef) {
				
				$safeArray[$paramName] = $paramDef['safe'];
			}
		}
		
		$numIterations = 10;
		$start = microtime(true);
		for ($i = 0; $i < $numIterations; $i++) {
		
			$output = call($category, $function, $safeArray);
		}
		
		//return round((microtime(true) - $start), 2) . ' seconds for ' . $numIterations . ' iterations';
		
		$seconds_duration = (microtime(true) - $start) / $numIterations;
		$ms_duration = $seconds_duration * 1000;
		
		return round($ms_duration, 2) . ' ms';
	}
