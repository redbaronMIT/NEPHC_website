<?php
namespace Moto\Application\Blocks; use Moto; class BlockTemplateCollection { protected $_items = array(); protected $_indexById = array(); protected $_indexByCategory = array(); protected $_loaded = false; protected $_options = array( 'themeBlocksDataFile' => '@currentTheme/data/blocks/blocks.json', 'basePath' => '@currentTheme/data/blocks', 'themeName' => 'mt-0000', ); public function __construct($options = null) { if (is_string($options)) { $this->setOption('themeBlocksDataFile', $options); } else { $this->setOptions($options); } if (!$this->isEmptyOption('themeBlocksDataFile')) { $this->loadList(); } $themeInfo = Moto\Website\Theme::getInfo(); $this->setOption('themeName', Moto\Util::getFrom($themeInfo, 'name')); } public function setOption($name, $value) { $this->_options[$name] = $value; return $this; } public function setOptions($options) { if (is_array($options)) { foreach ($options as $name => $value) { $this->setOption($name, $value); } } return $this; } public function isEmptyOption($name) { return (array_key_exists($name, $this->_options) ? empty($this->_options[$name]) : true); } public function loadList() { $filePath = $this->getOption('themeBlocksDataFile'); $filePath = Moto\System::getAbsolutePath($filePath); if (!file_exists($filePath) || $this->_loaded) { return false; } $data = file_get_contents($filePath); $data = json_decode($data, true); $this->_items = array(); $items = Moto\Util::getFrom($data, 'items', array()); foreach ($items as $item) { $item = $this->_createItem($item); if (!$item) { continue; } $this->_items[] = $item; } $this->_loaded = true; return true; } public function getOption($name, $default = null) { return (array_key_exists($name, $this->_options) ? $this->_options[$name] : $default); } protected function _createItem($data) { $item = new BlockTemplateItem($data, $this); if (array_key_exists($item->id, $this->_indexById)) { return false; } $this->_indexById[$item->id] = $item; if (!isset($this->_indexByCategory[$item->category])) { $this->_indexByCategory[$item->category] = array(); } $this->_indexByCategory[$item->category][] = $item; return $item; } public function getItems() { return $this->_items; } public function getById($id) { return (array_key_exists($id, $this->_indexById) ? $this->_indexById[$id] : null); } public function addItem($item) { if (!($item instanceof BlockTemplateItem)) { $item = $this->create($item); } else { if (array_key_exists($item->id, $this->_indexById)) { return false; } $item->setCollection($this); } $this->_items[] = $item; $this->_indexById[$item->id] = $item; if (!isset($this->_indexByCategory[$item->category])) { $this->_indexByCategory[$item->category] = array(); } $this->_indexByCategory[$item->category][] = $item; return $item; } public function create($data = array()) { if (is_array($data)) { $data['id'] = null; } return new BlockTemplateItem($data, $this); } public function save() { foreach ($this->_items as $item) { $item->save(); } if (!defined('JSON_PRETTY_PRINT')) { define('JSON_PRETTY_PRINT', 128); } $filePath = $this->getOption('themeBlocksDataFile'); $filePath = Moto\System::getAbsolutePath($filePath); Moto\Util::filePutContents($filePath, json_encode($this->toArray(), JSON_PRETTY_PRINT)); } public function toArray() { $data = array( 'items' => array(), ); foreach ($this->_items as $item) { if ($item->isDraft() || $item->trashed()) { continue; } $data['items'][] = $item->toArray(); } return $data; } public function generateItemId($item = null) { $id = $this->getOption('themeName'); if (!empty($id)) { $id .= '-'; } $id .= Moto\Util::getUniqueId(); return $id; } } class BlockTemplateItem { protected $_data = array( 'id' => null, 'name' => 'block', 'previewUrl' => null, 'category' => 'more', 'order' => 1, 'allowed' => true, 'options' => array(), 'created_at' => null, 'updated_at' => null, ); protected $_collection; protected $_template; protected $_content; protected $_modified = array(); protected $_basePath = '@currentTheme/data/blocks'; protected $_namespaceId; public function __construct($data = array(), $collection) { if (is_array($data)) { $this->fromArray($data); } $this->setCollection($collection); $this->_updateData(); } public function fromArray($data) { $data = (array)$data; foreach ($data as $name => $value) { $this->__set($name, $value); } return $this; } public function setCollection($collection) { $this->_collection = $collection; return $this; } protected function _updateData() { if (!$this->id) { $this->id = $this->_collection->generateItemId($this); } if (!$this->created_at) { $this->updated_at = time(); $this->created_at = $this->updated_at; } if (empty($this->previewUrl)) { $this->previewUrl = $this->_getBasePath() . '/' . $this->category . '/' . $this->id . '.jpg'; } if ($this->isEmptyOption('contentUrl') && !empty($this->_content)) { $this->setOption('contentUrl', $this->_getBasePath() . '/' . $this->category . '/' . $this->id . '.json'); } } protected function _getBasePath() { return $this->_collection->getOption('basePath', $this->_basePath); } public function isEmptyOption($name) { return (array_key_exists($name, $this->_data['options']) ? empty($this->_data['options'][$name]) : true); } public function setOption($name, $value) { $this->_data['options'][$name] = $value; return $this; } public function toArray() { return $this->_data; } public function __get($name) { $method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name))) . 'Attribute'; if (method_exists($this, $method)) { return $this->{$method}(); } return (array_key_exists($name, $this->_data) ? $this->_data[$name] : null); } public function __set($name, $value) { $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name))) . 'Attribute'; if (method_exists($this, $method)) { return $this->{$method}($value); } else { $this->_data[$name] = $value; } } public function __isset($name) { return array_key_exists($name, $this->_data); } public function setTemplateAttribute($value) { $value = trim($value); if ($this->_template !== $value) { $this->_modified['template'] = true; } $this->_template = $value; } public function getTemplateAttribute() { if (empty($this->_template)) { $templateUrl = $this->getOption('templateUrl'); if (!empty($templateUrl)) { $templateUrl = Moto\System::getAbsolutePath($templateUrl); $this->_template = file_get_contents($templateUrl); $this->_template = trim($this->_template); } } return $this->_template; } public function setContentAttribute($value) { if ($this->_content !== $value) { $this->_modified['content'] = true; } $this->_content = $value; } public function getContentAttribute($value) { if (empty($this->_content)) { $contentUrl = $this->getOption('contentUrl'); if (!empty($contentUrl)) { $contentUrl = Moto\System::getAbsolutePath($contentUrl); $this->_content = file_get_contents($contentUrl); $this->_content = trim($this->_content); } } return $this->_content; } public function getOption($name, $default = null) { return (array_key_exists($name, $this->_data['options']) ? $this->_data['options'][$name] : $default); } public function setOptionsAttribute($value) { if (!is_array($value)) { return false; } $contentUrl = Moto\Util::getFrom($value, 'contentUrl'); if (is_string($contentUrl)) { $value['contentUrl'] = $contentUrl; } else { $value['templateUrl'] = Moto\Util::getFrom($value, 'templateUrl'); } $this->_data['options'] = $value; } public function isDraft() { return (empty($this->id) || empty($this->previewUrl)); } public function delete() { $this->deleted_at = time(); } public function save() { if ($this->trashed()) { $previewUrl = $this->previewUrl; $previewUrl = Moto\System::getAbsolutePath($previewUrl); if (file_exists($previewUrl)) { unlink($previewUrl); } $templateUrl = $this->getOption('templateUrl'); if (is_string($templateUrl)) { $templateUrl = Moto\System::getAbsolutePath($templateUrl); if (file_exists($templateUrl)) { unlink($templateUrl); } } $contentUrl = $this->getOption('contentUrl'); if (is_string($contentUrl)) { $contentUrl = Moto\System::getAbsolutePath($contentUrl); if (file_exists($contentUrl)) { unlink($contentUrl); } } return; } if (!$this->isModified()) { return; } $this->_updateData(); $this->updated_at = time(); if ($this->isModified('content') && !empty($this->_content)) { $contentUrl = $this->getOption('contentUrl'); $contentUrl = Moto\System::getAbsolutePath($contentUrl); Moto\Util::filePutContents($contentUrl, $this->_content); $templateUrl = $this->getOption('templateUrl'); if (is_string($templateUrl)) { $templateUrl = Moto\System::getAbsolutePath($templateUrl); if (file_exists($templateUrl)) { unlink($templateUrl); } unset($this->_data['options']['templateUrl']); } } } public function trashed() { return (array_key_exists('deleted_at', $this->_data)); } public function isModified($name = null) { if (empty($name)) { return !!count($this->_modified); } else { return array_key_exists($name, $this->_modified); } } } 