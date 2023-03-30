# Simple Airtable Caching API
by Stephen Ginn at Crema Design Studio

This PHP script checks for the existence of a cache file. If one doesn't
exist, it connects to Airtable and saves a new one.

### Installation
You can install the package via composer:
```
composer require crema/airtable
```

Define airtables account information in config.json:
```
{
	"airtable": {
		"api_key": "YOUR_API_KEY",
		"base_id": "YOUR_BASE_ID"
	}
}
```

### Usage
Add this code to your PHP file:
```php
require_once '../vendor/autoload.php';

use Crema\Airtable;

$credentials = json_decode(file_get_contents('../config.json'))->airtable;
$airtable = new AirTable($credentials);
$records = $airtable->getTable('YOUR_TABLE_NAME');

header('Content-Type: application/json');
echo json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
```
