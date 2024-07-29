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
		
		public function loadData($tableName, $recordId = null) {
			$this->setTableName($tableName);
			$cacheFile = $recordId ? "$this->cacheDir/$this->tableSlug-$recordId.json" : "$this->cacheDir/$this->tableSlug.json";
			
			if (file_exists($cacheFile) && $this->isCacheValid($cacheFile)) {
				return json_decode(file_get_contents($cacheFile));
			}
			
			// Refresh the Table or Record Cache
			$url = "$this->api/$this->baseId/" . rawurlencode($tableName);
			
			if ($recordId) {
				$record = $this->request("$url/$recordId");
				$record = $this->remapRecord($record);
				$record = $this->cacheAttachments([$record])[0];
				file_put_contents($cacheFile, json_encode($record, JSON_PRETTIER));
				return $record;
			} else {
				$records = $this->request($url)->records;
				foreach ($records as &$record) {
					$record = $this->remapRecord($record);
				}
				$records = $this->cacheAttachments($records);
				file_put_contents($cacheFile, json_encode($records, JSON_PRETTIER));
				return $records;
			}
		}
		
		// Alias for old getTable function
		public function getTable($tableName) {
			return $this->loadData($tableName);
		}
		
		// Alias for old getRecord function
		public function getRecord($recordId) {
			return $this->loadData($this->tableSlug, $recordId);
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
		
		private function cacheAttachments($records) {
			$mh = curl_multi_init();
			$handles = [];
			$cacheFiles = [];
			$batchSize = 10;
			
			foreach ($records as &$record) {
				foreach ($record as $field => $value) {
					if (is_array($value)) {
						foreach ($value as &$attachment) {
							if (isset($attachment->url) && isset($attachment->filename)) {
								$extension = pathinfo($attachment->filename, PATHINFO_EXTENSION);
								$cacheFile = "$this->cacheDir/$this->tableSlug-{$record->id}-{$attachment->id}.$extension";
								
								$cacheFiles[$attachment->url] = $cacheFile;
								
								if (!file_exists($cacheFile)) {
									$ch = curl_init($attachment->url);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
									curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
									curl_setopt($ch, CURLOPT_TIMEOUT, 60);
									$handles[$attachment->url] = $ch;
								} else {
									$attachment->url = $cacheFile;
									$attachment->extension = $extension;
									unset($attachment->thumbnails);
								}
							}
						}
					}
				}
			}
			
			$handleChunks = array_chunk($handles, $batchSize, true);
			
			foreach ($handleChunks as $handleChunk) {
				foreach ($handleChunk as $url => $ch) {
					curl_multi_add_handle($mh, $ch);
				}
				
				$running = null;
				do {
					curl_multi_exec($mh, $running);
					curl_multi_select($mh);
				} while ($running > 0);
				
				foreach ($handleChunk as $url => $ch) {
					$content = curl_multi_getcontent($ch);
					if ($content !== false) {
						file_put_contents($cacheFiles[$url], $content);
					}
					curl_multi_remove_handle($mh, $ch);
				}
			}
			
			curl_multi_close($mh);
			
			// Update attachment URLs and remove thumbnails
			foreach ($records as &$record) {
				foreach ($record as $field => $value) {
					if (is_array($value)) {
						foreach ($value as &$attachment) {
							if (isset($attachment->url) && isset($attachment->filename)) {
								$extension = pathinfo($attachment->filename, PATHINFO_EXTENSION);
								$cacheFile = "$this->cacheDir/$this->tableSlug-{$record->id}-{$attachment->id}.$extension";
								
								if (file_exists($cacheFile)) {
									$attachment->url = $cacheFile;
									$attachment->extension = $extension;
									unset($attachment->thumbnails);
								}
							}
						}
					}
				}
			}
			
			return $records;
		}
		
		private function isCacheValid($cacheFile) {
			return (time() - filemtime($cacheFile)) < $this->cacheLife;
		}
	}

?>