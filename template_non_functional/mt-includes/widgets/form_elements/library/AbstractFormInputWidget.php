<?php
namespace Website\Widgets\FormElements; use Moto; class AbstractFormInputWidget extends AbstractFormElementWidget { protected $_fieldValue = ''; public function getFieldType() { $type = $this->getPropertyValue('type'); if (empty($type)) { return 'text'; } return $type; } public function getFieldName() { return $this->properties['name']; } public function setFieldValue($value) { $this->_fieldValue = $value; } public function getFieldValue() { return (string) $this->_fieldValue; } public function getRawFieldValue() { return $this->_fieldValue; } public function isValueMultiline() { return false; } public function isVisibleInMessage() { return true; } public function getValidationRules() { $rules = array(); $rule = $this->getPropertyValue('validation.stringLength'); if ($rule) { if (Moto\Util::getValue($rule, 'allowMin')) { $rules['minlength'] = $rule['minValue']; } if (Moto\Util::getValue($rule, 'allowMax')) { $rules['maxlength'] = $rule['maxValue']; } } $rule = $this->getPropertyValue('validation.numberValue'); if ($rule) { if (Moto\Util::getValue($rule, 'allowMin')) { $rules['min'] = $rule['minValue']; } if (Moto\Util::getValue($rule, 'allowMax')) { $rules['max'] = $rule['maxValue']; } } return $rules; } public function createInputFilter($factory) { return null; } public function sanitizeInputFilters($filters) { return $filters; } public function sanitizeInputValidators($validators) { return $validators; } } 