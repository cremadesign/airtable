<?php
	
	require_once '../vendor/autoload.php';
	
	use Crema\Airtable;
	
	$credentials = json_decode(file_get_contents('../config.json'))->airtable;
	$airtable = new Airtable($credentials);
	$staff = $airtable->getTable('People');
	
	header('Content-Type: application/json');
	echo json_encode($staff, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	
?>
