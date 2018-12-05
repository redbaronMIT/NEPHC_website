<?php
namespace Moto\System; use Moto; use Zend; class Brand { protected static $_instance = null; protected $_name = 'motocms'; protected $_structure = 1; protected $_features = array(); protected $_options = array(); protected $_media = array(); protected $_texts = array(); protected $_styles = array(); protected $_apiUrl = null; protected $_brandFilePath = '@updateTemp/brand.info'; protected $_brandFileTTL = 120; protected $_originalBrand; protected function __construct() { $this->_init(); } public static function getInstance() { if (is_null(static::$_instance)) { static::$_instance = new self(); } return static::$_instance; } public static function init() { return static::getInstance(); } protected function _init() { $brandData = Moto\Config::get('brand', array()); $this->_apiUrl = trim((string) Moto\Util::getFrom($brandData, 'apiUrl', '')); $this->_brandFilePath = Moto\System::getAbsolutePath($this->_brandFilePath); if (Moto\System::isDevelopmentStage()) { $this->_brandFileTTL = Moto\Util::getFrom($brandData, 'brandFileTTL', $this->_brandFileTTL); } unset($brandData['apiUrl']); if (empty($brandData['name'])) { $brandData['name'] = $this->_name; } $this->_originalBrand = $brandData; $brandData = $this->_updateDataByFile($brandData); $brandData = $this->_updateSelfOptions($brandData); $this->_updateCompatibility($brandData); } protected function _resetBrandData() { Moto\Util::filePutContents($this->_brandFilePath, ''); if (is_array($this->_originalBrand)) { } } protected function _updateCompatibility($brandData) { Moto\Config::set('brand', $brandData); Moto\System::setPath('systemFavicon', $this->getMediaPath('favicon.ico', Moto\System::getPath('systemFavicon'))); } public function updateData($data) { $this->_updateSelfOptions($data); $this->_updateCompatibility($this->getInfo()); } protected function _updateSelfOptions($brandData) { if (!empty($brandData['name'])) { $this->_name = $brandData['name']; } if (!empty($brandData['structure'])) { $this->_structure = $brandData['structure']; } if (!empty($brandData['options']) && is_array($brandData['options'])) { $this->_options = Zend\Stdlib\ArrayUtils::merge($this->_options, $brandData['options']); unset($brandData['options']); } if (!empty($brandData['features']) && is_array($brandData['features'])) { $this->_features = Zend\Stdlib\ArrayUtils::merge($this->_features, $brandData['features']); } if (!empty($brandData['media']) && is_array($brandData['media'])) { $this->_media = Zend\Stdlib\ArrayUtils::merge($this->_media, $brandData['media']); } if (!empty($brandData['translations']) && is_array($brandData['translations'])) { $this->_texts = Zend\Stdlib\ArrayUtils::merge($this->_texts, $brandData['translations']); } if (!empty($brandData['styles']) && is_array($brandData['styles'])) { $this->_styles = Zend\Stdlib\ArrayUtils::merge($this->_styles, $brandData['styles']); } return $brandData; } public function getInfo() { $data = array(); $data['name'] = $this->_name; $data['structure'] = $this->_structure; $data['features'] = $this->_features; $data['media'] = $this->_media; $data['translations'] = $this->_texts; $data['styles'] = $this->_styles; return $data; } public function getUpdatedBrandInfo() { try { if (Moto\Config::get('__disabledCustomBrand__', false)) { return $this->getInfo(); } $needDownload = true; $needChecking = true; $checkingData = null; $brandData = null; $brandFilePath = $this->_brandFilePath; if (file_exists($brandFilePath)) { $delta = abs(time() - filemtime($brandFilePath)); if ($delta < $this->_brandFileTTL) { $needDownload = false; $needChecking = false; } } if ($needChecking) { $checkingData = $this->_checkIsBrandingProduct(); if (!is_array($checkingData) || !$checkingData['brand'] || empty($checkingData['name']) || empty($checkingData['file_path'])) { $needDownload = false; } } if ($needDownload) { $brandData = $this->_downloadBrandInfo($checkingData['file_path']); if (is_array($brandData)) { $this->_updateSelfOptions($brandData); } } } catch(\Exception $e) { Moto\System\Log::emergency('BRAND : getUpdatedBrandInfo() : Exception ' . $e->getCode() . ':' . $e->getMessage()); return array(); } return $this->getInfo(); } protected function _createHttpClient($url) { $options = array( 'maxredirects' => 1, 'curloptions' => array( CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 10 ) ); $info = parse_url($url); if (isset($info['scheme'])) { $info['scheme'] = strtolower($info['scheme']); if ($info['scheme'] == 'https') { $options['curloptions'][CURLOPT_SSL_VERIFYPEER] = false; $options['curloptions'][CURLOPT_SSL_VERIFYHOST] = 0; } } $client = new Moto\Http\Client($url, $options); return $client; } protected function _checkIsBrandingProduct() { if (empty($this->_apiUrl)) { $this->_resetBrandData(); return false; } $url = rtrim($this->_apiUrl, '/') . '/' . Moto\Config::get('__product_id__'); $client = $this->_createHttpClient($url); $client->setMethod('POST'); $client->send(); if ($client->hasErrors()) { if (Moto\System::isDevelopmentStage()) { Moto\System\Log::debug('BRAND _checkIsBrandingProduct : cant connect', array('url' => $url, 'errors' => $client->getErrors())); } return false; } $client->getAdapter()->close(); $response = $client->getResponse()->getBody(); $response = trim($response); if ($response[0] != '{') { if (Moto\System::isDevelopmentStage()) { Moto\System\Log::debug('BRAND _checkIsBrandingProduct : bad response', array('url' => $url, 'response' => $response)); } return false; } $response = json_decode($response, true); if (Moto\Util::getFrom($response, 'success') === false && Moto\Util::getFrom($response, 'code') == 404) { return false; } if (!array_key_exists('brand', $response)) { return false; } $response['file_path'] = Moto\Util::getFrom($response, 'file_path', ''); $response['name'] = Moto\Util::getFrom($response, 'name', ''); if (!empty($this->_brandFilePath) && !$response['brand']) { $this->_resetBrandData(); } return $response; } protected function _downloadBrandInfo($url) { $url = (string) $url; $url = trim($url); if (Moto\System::isDevelopmentStage()) { $url = str_replace('https://', 'http://', $url); } if (empty($url)) { return false; } $client = $this->_createHttpClient($url); $client->setMethod('POST'); $client->send(); if ($client->hasErrors()) { if (Moto\System::isDevelopmentStage()) { Moto\System\Log::debug('BRAND _checkIsBrandingProduct : cant connect', array('url' => $url, 'errors' => $client->getErrors())); } return false; } $client->getAdapter()->close(); $response = $client->getResponse()->getBody(); $response = trim($response); if ($response[0] != '{') { if (Moto\System::isDevelopmentStage()) { Moto\System\Log::debug('BRAND _checkIsBrandingProduct : bad response', array('url' => $url, 'response' => $response)); } return false; } if (!empty($this->_brandFilePath)) { Moto\Util::filePutContents($this->_brandFilePath, $response); } $response = json_decode($response, true); return $response; } protected function _updateDataByFile($brandData) { if (Moto\Config::get('__disabledCustomBrand__', false)) { return $brandData; } $data = null; $brandPath = Moto\Config::get('brand.file', '@cpBrand'); $brandPath = Moto\System::getAbsolutePath($brandPath); $brandFile = null; if (file_exists($brandPath . '.json')) { $brandFile = $brandPath . '.json'; } elseif (file_exists($brandPath . '.info')) { $brandFile = $brandPath . '.info'; } $data = $this->_loadBrandFile($brandFile); if (is_array($data) && !empty($data)) { $brandData = Zend\Stdlib\ArrayUtils::merge($brandData, $data); } $remoteBrandFile = $this->_brandFilePath; if (file_exists($remoteBrandFile)) { $data = $this->_loadBrandFile($remoteBrandFile); if (is_array($data) && !empty($data)) { $brandData = Zend\Stdlib\ArrayUtils::merge($brandData, $data); } } return $brandData; } protected function _loadBrandFile($brandFile) { $brandData = null; if (is_string($brandFile) && !empty($brandFile) && file_exists($brandFile)) { $data = file_get_contents($brandFile); $data = trim($data); if (!empty($data)) { if ($data[0] !== '{') { $data = Moto\System\Encryption::decryptLegacy($data, 'BrandSimplePassw'); $data = trim($data); } if ($data[0] === '{') { $data = json_decode($data, true); } if (is_array($data)) { $brandData = $data; } } } return $brandData; } public function getName() { return $this->_name; } public function getOption($name, $default = null) { return Moto\Util::getFromArrayDeep($this->_options, $name, $default); } public function getOptions() { return $this->_options; } public function isEnabled($name, $default = false) { return (array_key_exists($name, $this->_features) ? $this->_features[$name] : $default); } public function getMedia($name = null, $default = null) { if ($name == null) { return $this->_media; } return (array_key_exists($name, $this->_media) ? $this->_media[$name] : $default); } public function getMediaPath($name, $path = null) { if (!empty($this->_media[$name]['path'])) { $path = $this->_media[$name]['path']; } return $path; } public function getMediaAbsolutePath($name, $path = null) { return Moto\System::getAbsolutePath($this->getMediaPath($name, $path)); } public function getMediaRelativeUrl($name, $url = null) { return Moto\System::getRelativeUrl($this->getMediaPath($name, $url)); } public function getMediaAbsoluteUrl($name, $url = null) { return Moto\System::getAbsoluteUrl($this->getMediaPath($name, $url)); } public function getText($name, $default = null) { return Moto\Util::getFromArrayDeep($this->_texts, $name, $default); } public function getStyle($name, $default = null) { return Moto\Util::getFromArrayDeep($this->_styles, $name, $default); } }