<?php
	namespace Crema;
	
	class Airtable extends \stdClass {
		public function __construct($credentials) {
			$this->login($credentials);
			$this->api = "https://api.airtable.com/v0";
			$this->cacheDir = 'data/.cached';
			$this->cacheLife = '14400'; // 4 hours (in seconds)
			
			if (!is_dir($this->cacheDir)) {
				mkdir($this->cacheDir, 0777, true);
			}
		}
		
		private function request($url) {
			if (strpos($_SERVER["HTTP_HOST"], '.test') !== false) {
				$options = [
					'ssl' => [
						'verify_peer' => false
					],
					'http' => [
						'method'  => 'GET',
						'header' => 'Authorization: Bearer '. $this->apiKey
					]
				];
			} else {
				$options = [
					'http' => [
						'method'  => 'GET',
						'header' => 'Authorization: Bearer '. $this->apiKey
					]
				];
			}
			
			$context  = stream_context_create($options);
			return file_get_contents($url, false, $context);
		}
		
		private function slugify($string, $replacement = '-') {
			$slug = strtolower(preg_replace('/[^A-z0-9-]+/', $replacement, $string));
			return trim($slug, $replacement);
		}
		
		public function login($credentials) {
			$this->apiKey = $credentials->api_key;
			$this->baseId = $credentials->base_id;
		}
		
		public function setCacheDir(string $cacheDir): void {
			$this->cacheDir = $cacheDir;
		}
		
		public function setCacheLife(string $cacheLife): void {
			$this->cacheLife = $cacheLife;
		}
		
		public function setApiKey(string $apiKey): void {
			$this->apiKey = $apiKey;
		}
		
		public function getApiKey(): string {
			return $this->apiKey;
		}
		
		public function setBaseId(string $baseId): void {
			$this->baseId = $baseId;
		}
		
		public function getBaseId(): string {
			return $this->baseId;
		}
		
		public function setTableName(string $tableName): void {
			$this->tableName = $tableName;
			$this->tableSlug = $this->slugify($this->tableName);
		}
		
		public function getTableName(): string {
			return $this->tableName;
		}
		
		public function setRecordId(string $recordId): void {
			$this->recordId = $recordId;
		}
		
		public function getRecordId(): string {
			return $this->recordId;
		}
		
		// Refresh the Table Cache
		private function refreshTables() {
			$url = "$this->api/$this->baseId/$this->tableName?view=Grid%20view";
			$records = json_decode($this->request($url))->records;
			
			$result = array_map(function($record) {
				$fields = [];
			
				foreach ($record->fields as $key => $value) {
					$fields[$this->slugify($key, '_')] = $value;
				}
			
				$fields['id'] = $record->id;
				$fields['created'] = $record->createdTime;
			
				return $fields;
			}, $records);
			
			return json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		}
		
		// Refresh the Records Cache
		private function refreshRecords() {
			$url = "$this->api/$this->baseId/$this->tableName/$this->recordId?api_key=$this->apiKey";
			$record = json_decode($this->request($url));
			$fields = [];
			
			foreach ($record->fields as $key => $value) {
				$fields[$this->slugify($key, '_')] = $value;
			}
			
			$fields['id'] = $record->id;
			$fields['created'] = $record->createdTime;
			
			return json_encode($fields, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		}
		
		private function getData($type, $cacheFile) {
			$fileModified = @filemtime($cacheFile); // returns FALSE if file doesn't exist
			
			if (!$fileModified or (time() - $fileModified >= $this->cacheLife)) {
				$result = ($type == "table") ? $this->refreshTables() : $this->refreshRecords();
				file_put_contents($cacheFile, $result);
			} else {
				$result = $this->request($cacheFile);
			}
			
			return json_decode($result, true);
		}
		
		public function getTable($tableName = null) {
			if (isset($tableName)) $this->setTableName($tableName);
			return $this->getData("table", "$this->cacheDir/$this->tableSlug.json");
		}
		
		public function getRecord($recordId = null) {
			if (isset($recordId)) $this->setRecordId($recordId);
			return $this->getData("record", "$this->cacheDir/$this->tableSlug-$this->recordId.json");
		}
	}
?>
