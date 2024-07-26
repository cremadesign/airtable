# Simple Airtable Caching API
by Stephen Ginn at Crema Design Studio

This PHP script checks for the existence of a cache file. If one doesn't
exist, it connects to Airtable and saves a new one.

## Installation
You can install the package via composer:
```
composer config repositories.crema/airtable git https://github.com/cremadesign/airtable
composer require crema/airtable:@dev
```

We'd suggest storing your account info in a .env file outside your public folder
```
AIRTABLE_API = YOUR_API_KEY
AIRTABLE_BASE = YOUR_BASE_ID
```

## Usage

### Initialize
```php
require_once '../vendor/autoload.php';

use Crema\Airtable;

$airtable = new AirTable([
	'api_key' => getenv('AIRTABLE_API'),
	'base_id' => getenv('AIRTABLE_BASE')
]);
```

#### Get Entire Table
```php
$data = $airtable->loadTable('TABLE_NAME');
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
```

#### Get Single Record
```
$data = $airtable->loadRecord('TABLE_NAME', 'RECORD_ID');
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
```

## Usage (Compatibility Layer)

#### Get Entire Table
```php
$data = $airtable->getTable('TABLE_NAME');
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
```

#### Get Single Record
```php
$airtable->setTableName('TABLE_NAME');
$data = $airtable->getRecord('RECORD_ID');
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
```
