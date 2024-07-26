<?php
	
	require_once '../vendor/autoload.php';
	require_once 'bin.php';
	
	loadEnv('../.env');
	
	use Crema\Airtable;
	
	$airtable = new Airtable([
		'api_key' => getenv('AIRTABLE_API'),
		'base_id' => getenv('AIRTABLE_BASE')
	]);
	
	//$record = $airtable->loadTable('Two Words');
	$record = $airtable->loadRecord('Two Words', 'rec71aktPwbfy7GQq');
	
	dd($record);
?>
