<?php

	namespace Crema;
	
	class Airtable {
		public function __construct($credentials) {
			$this->login($credentials);
			$this->api = "https://api.airtable.com/v0";
			$this->cacheDir = 'data/.cached';
			$this->cacheLife = '120'; // in seconds
			
			if (!is_dir($this->cacheDir)) {
				mkdir($this->cacheDir, 0777, true);
			}
		}
		
		private function request($url) {
			if (strpos($_SERVER["HTTP_HOST"], '.test') !== false) {
				$context = stream_context_create([
					'ssl' => [
						'verify_peer' => false
					]
				]);
			
				return file_get_contents($url, FILE_TEXT, $context);
			} else {
				return file_get_contents($url);
			}
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
		
		private function refreshTableCache() {
			$url = "$this->api/$this->baseId/$this->tableName?api_key=$this->apiKey&view=Grid%20view";
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
			
			// Return Records
			$string = json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
			return str_replace("    ", "\t", $string);
		}
		
		private function refreshRecordCache() {
			$url = "$this->api/$this->baseId/$this->tableName/$this->recordId?api_key=$this->apiKey";
			$record = json_decode($this->request($url));
			
			$fields = [];
			
			foreach ($record->fields as $key => $value) {
				$fields[$this->slugify($key, '_')] = $value;
			}
			
			$fields['id'] = $record->id;
			$fields['created'] = $record->createdTime;
			
			$string = json_encode($fields, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
			return str_replace("    ", "\t", $string);
		}
		
		public function getTable($tableName = null) {
			if (isset($tableName)) {
				$this->setTableName($tableName);
			}
			
			$cacheFile = "$this->cacheDir/$this->tableSlug.json";
			$fileModified = @filemtime($cacheFile); // returns FALSE if file doesn't exist
			
			if (!$fileModified or (time() - $fileModified >= $this->cacheLife)) {
				$result = $this->refreshTableCache();
				file_put_contents($cacheFile, $result);
			} else {
				$result = $this->request($cacheFile);
			}
			
			return json_decode($result, true);
		}
		
		public function getRecord($recordId = null) {
			if (isset($recordId)) {
				$this->setRecordId($recordId);
			}
			
			$cacheFile = "$this->cacheDir/$this->tableSlug-$this->recordId.json";
			$fileModified = @filemtime($cacheFile); // returns FALSE if file doesn't exist
			
			if (!$fileModified or (time() - $fileModified >= $cacheLife)) {
				$result = $this->refreshRecordCache();
				file_put_contents($cacheFile, $result);
			} else {
				$result = readfile($cacheFile);
			}
			
			return json_decode($result, true);
		}
	}
?>
