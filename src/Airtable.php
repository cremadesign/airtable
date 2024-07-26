<?php
	namespace Crema;
	
	if (!defined('JSON_PRETTIER')) {
		define('JSON_PRETTIER', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
	
	class Airtable extends \stdClass {
		public function __construct($credentials) {
			$credentials = (object) $credentials;
			
			$this->setApiKey($credentials->api_key);
			$this->setBaseId($credentials->base_id);
			$this->setCacheDir('data/.cached');
			$this->setCacheLife(14400); // 4 hours (in seconds)
			
			$this->api = "https://api.airtable.com/v0";
			
			if (!is_dir($this->cacheDir)) {
				mkdir($this->cacheDir, 0755, true);
			}
		}
		
		public function setApiKey(string $apiKey): void {
			$this->apiKey = $apiKey;
		}
		
		public function setBaseId(string $baseId): void {
			$this->baseId = $baseId;
		}
		
		public function setCacheDir(string $cacheDir): void {
			$this->cacheDir = $cacheDir;
		}
		
		public function setCacheLife(string $cacheLife): void {
			$this->cacheLife = $cacheLife;
		}
		
		public function setTableName(string $tableName): void {
			$this->tableName = $tableName;
			$this->tableSlug = $this->slugify($this->tableName);
		}
		
		/*/
			I've commented out these functions, since they aren't fully
			integrated and I'm not sure if we actually use or need yet.
			
			public function setRecordId(string $recordId): void {
				$this->recordId = $recordId;
			}
			
			public function getRecordId(): string {
				return $this->recordId;
			}
			
			public function getApiKey(): string {
				return $this->apiKey;
			}
			
			public function getBaseId(): string {
				return $this->baseId;
			}
			
			public function getTableName(): string {
				return $this->tableName;
			}
		/*/
		
		public function loadTable($tableName) {
			$this->setTableName($tableName);
			$cacheFile = "$this->cacheDir/$this->tableSlug.json";
			
			if (file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
				return json_decode(file_get_contents($cacheFile));
			}
			
			// Refresh the Table Cache
			$url = "$this->api/$this->baseId/" . rawurlencode($tableName);
			$records = $this->request($url)->records;
			
			foreach ($records as &$record) {
				$record = $this->remapRecord($record);
				$record = $this->cacheAttachments($tableName, $record);
			}
			
			file_put_contents($cacheFile, json_encode($records, JSON_PRETTIER));
			
			return $records;
		}
		
		public function loadRecord($tableName, $recordId) {
			$this->setTableName($tableName);
			$cacheFile = "$this->cacheDir/$this->tableSlug-$recordId.json";
			
			if (file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
				return json_decode(file_get_contents($cacheFile));
			}
			
			// Refresh the Records Cache
			$url = "$this->api/$this->baseId/" . rawurlencode($tableName) . "/$recordId";
			$record = $this->request($url);
			$record = $this->remapRecord($record);
			$record = $this->cacheAttachments($tableName, $record);
			
			file_put_contents($cacheFile, json_encode($record, JSON_PRETTIER));
			
			return $record;
		}
		
		// Alias for old getTable function
		public function getTable($tableName) {
			return $this->loadTable($tableName);
		}
		
		// Alias for old getRecord function
		public function getRecord($recordId) {
			return $this->loadRecord($this->tableSlug, $recordId);
		}
		
		private function slugify($text) {
			return strtolower(trim(preg_replace('/[^A-z0-9-]+/', '-', $text), '-'));
		}
		
		private function request($url) {
			$ch = curl_init($url);
			
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => [
					"Authorization: Bearer $this->apiKey"
				]
			]);
			
			$response = curl_exec($ch);
			curl_close($ch);
			
			return json_decode($response);
		}
		
		private function remapRecord($record) {
			if (!isset($record->id) || !isset($record->createdTime) || !isset($record->fields)) {
				throw new \UnexpectedValueException('Record is missing expected properties');
			}
			
			$fields = [
				'id' => $record->id,
				'created' => $record->createdTime
			];
			
			foreach ($record->fields as $key => &$value) {
				$fields[$this->slugify($key, '_')] = $value;
			}
			
			return (object) $fields;
		}
		
		private function cacheAttachments($tableName, $record) {
			if (isset($record->image)) {
				foreach ($record->image as &$attachment) {
					$extension = pathinfo($attachment->filename, PATHINFO_EXTENSION);
					$cacheFile = "$this->cacheDir/$this->tableSlug-{$record->id}-{$attachment->id}.$extension";
					
					if (!file_exists($cacheFile)) {
						$content = file_get_contents($attachment->url);
						file_put_contents($cacheFile, $content);
					}
					
					$attachment->url = $cacheFile;
					$attachment->extension = $extension;
					unset($attachment->thumbnails);
				}
			}
			
			return $record;
		}
		
		private function isCacheValid($cacheFile) {
			return (time() - filemtime($cacheFile)) < $this->cacheLife;
		}
	}

?>