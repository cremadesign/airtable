<?php
	
	require_once '../vendor/autoload.php';
	require_once 'bin.php';
	
	loadEnv('../.env');
	
	use Crema\Airtable;
	
	$airtable = new Airtable([
		'api_key' => getenv('AIRTABLE_API'),
		'base_id' => getenv('AIRTABLE_BASE')
	]);
	
	$response = $airtable->loadData('Staff');
	//$response = $airtable->loadData('Staff', 'rec2Or0Z8CfbUx3C9');
	
	dd($response);
?>
