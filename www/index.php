<?php
	
	require_once '../vendor/autoload.php';
	use Crema\Airtable;
	
	$test = $_GET['test'] ?? 'table';
	$credentials = json_decode(file_get_contents('../config.json'))->airtable;
	$airtable = new Airtable($credentials);
	
	switch ($test) {
		case 'table':
			$response = $airtable->getTable('People');
		break;
		case 'record':
			$airtable->setTableName('People');
			$response = $airtable->getRecord('rec5ng53hVBkDqT4r');
		break;
	}
	
	header('Content-Type: application/json');
	echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	
?>
