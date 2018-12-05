<?php
namespace Website\Widgets\FormElements; use Moto; class AbstractFormElementWidget extends Moto\System\Widgets\AbstractWidget { protected $_mainForm; protected $_templateType = 'templates'; protected $_widgetId = true; public function getMainForm() { if (!$this->_mainForm) { $this->_mainForm = $this->_findParent(function ($widget) { if ($widget instanceof Moto\System\Widgets\AbstractFormWidget) { return $widget; } return null; }); } return $this->_mainForm; } public function getCurrentPreset() { $preset = parent::getCurrentPreset(); if (empty($preset)) { $form = $this->getMainForm(); if ($form) { $preset = $form->getCurrentPreset(); } else { $preset = 'default'; } } return $preset; } } 