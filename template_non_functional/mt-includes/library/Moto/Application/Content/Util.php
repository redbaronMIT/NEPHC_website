<?php
namespace Moto\Application\Content; use Moto; use Zend; class Util { public static function findContainers($content) { $list = array(); if (preg_match_all('/(<[^>]*data-widget="container"[^>]+>)/', $content, $match)) { foreach ($match[1] as $tag) { $list[] = static::getContainerClassName($tag); } } return $list; } public static function getContainerClassName($content) { if (preg_match('/css-name="([^"]+)"/', $content, $match)) { return $match[1]; } elseif (preg_match('/class="[^"]*(moto-container[^\s"]+)[^"]*"/', $content, $match)) { return $match[1]; } return null; } public static function checkLinkerContent($content) { $_content = preg_replace_callback('/[\\\]?\"[\s]*(\{\{[\s]*Linker\.[^\"]+\}\})[\s]*[\\\]?\"/', 'Moto\Application\Content\Util::_linkerReplaceCallback', $content); $len = strlen($_content); return $_content; } public static function _linkerReplaceCallback($matches) { $str = $matches[0]; $_href = $href = $matches[1]; if (strpos($href, 'Linker.pageLink') !== false) { if (preg_match('/Linker\.pageLink\([\'\"]?([^\'\",]*)[\'\"]?,(.*)\)/', $href, $match)) { $pageId = $match[1]; $fallback = trim($match[2], '"\''); if (preg_match('/Linker\.pageLink\((.*)\)/', $fallback, $match)) { $href = "{{ Linker.pageLink('$pageId', '#') }}"; $str = str_replace($_href, $href, $str); } } } return $str; } public static function compressContainers($params) { $result = array( 'removed' => 0, 'type' => '', ); $isDebug = Moto\Util::getFrom($params, 'isDebug', false); if ($isDebug) { Moto\System\Log::debug(__FUNCTION__ . ': params ', $params); } $type = Moto\Util::getFrom($params, 'type'); if (!in_array($type, array('header', 'content', 'footer'))) { if ($isDebug) { Moto\System\Log::debug(__FUNCTION__ . ': Unknown type ' . $type); } $result['isError'] = true; $result['error'] = 'UNKNOWN_TYPE'; return $result; } if ($isDebug) { Moto\System\Log::debug(__FUNCTION__ . ': type ' . $type); } $table = new Moto\Application\Styles\StylesTable(); $select = new Zend\Db\Sql\Select($table->getTable()); $select->columns(array('id', 'class_name')); $select->where(array( 'type' => 'widget', 'is_system' => 0, )); $select->where(new Zend\Db\Sql\Predicate\Like('class_name', 'moto-container_' . $type . '_%')); $statement = $table->getSql()->prepareStatementForSqlObject($select); $collection = $statement->execute(); if ($isDebug) { Moto\System\Log::debug(__FUNCTION__ . ': Founded ' . $collection->count() . ' container(s)'); } if ($collection->count() < Moto\Util::getFrom($params, 'minimum', 0)) { if ($isDebug) { Moto\System\Log::debug(__FUNCTION__ . ': Skipped low containers'); } $result['isError'] = true; $result['error'] = 'IGNORE_BY_MINIMUM_CONTAINERS'; return $result; } $allContainers = array(); foreach($collection as $item) { $allContainers[] = $item['class_name']; } $where = null; switch($type) { case 'content': $contentTable = new Moto\Application\Pages\PagesTable(); break; default: $contentTable = new Moto\Application\Content\Table\ContentBlocks(); $where = array( 'type' => $type, ); break; } $contentTable->useResultAsModel(false); $items = $contentTable->select($where); foreach ($items as $item) { if (empty($item->content)) { continue; } $containers = Moto\Application\Content\Util::findContainers(trim($item->content)); if (empty($containers)) { continue; } $allContainers = array_diff($allContainers, $containers); } if (count($allContainers)) { $result['removed'] = count($allContainers); $table = new Moto\Application\Styles\StylesTable(); if ($isDebug) { Moto\System\Log::info(__FUNCTION__ . ': Removing ' . count($allContainers) . ' containers'); } $allContainers = array_values($allContainers); $table->deleteByClassName($allContainers); } elseif ($isDebug) { Moto\System\Log::info(__FUNCTION__ . ': Nothing to remove'); } return $result; } }