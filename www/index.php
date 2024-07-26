<?php
	
	require_once '../vendor/autoload.php';
	require_once 'bin.php';
	
	loadEnv('../.env');
	
	use Crema\Airtable;
	
	$airtable = new Airtable([
		'api_key' => getenv('AIRTABLE_API'),
		'base_id' => getenv('AIRTABLE_BASE')
	]);
	
	//$record = $airtable->loadTable('Contacts');
	$record = $airtable->loadRecord('Contacts', 'rec02r537wq2vio8Y');
	
	dd($record);
?>
