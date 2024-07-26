<?php
	/*/
		Load .env file into environment variables
		
		loadEnv('../.env');
		echo getenv('KEY_NAME');
	/*/
	
	function loadEnv($file) {
		if (!file_exists($file)) {
			throw new Exception("Environment file not found: " . $file);
		}
	
		$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			// Skip lines that are comments
			if (strpos(trim($line), '#') === 0) {
				continue;
			}
			
			list($name, $value) = explode('=', $line, 2);
			
			putenv(sprintf('%s=%s', trim($name), trim($value)));
		}
	}
	
	function dd($data) {
		header('Content-Type: application/json');
		echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
?>