<?php
namespace Moto\Application\Users; use Moto\InputFilter\AbstractInputFilter; use Zend\InputFilter\Exception; use Zend\InputFilter\InputFilterInterface; use Moto; class SaveUserFilter extends NewUserFilter { protected $_name = 'users.save'; public function init() { $this->add(array( 'name' => 'id', 'required' => true, 'filters' => array( array('name' => 'StripTags'), array('name' => 'StringTrim'), ), 'validators' => array( array( 'name' => 'Digits', ), ), )); parent::init(); $this->remove('password'); $this->remove('password_confirm'); $this->remove('send_email'); $this->_addElementEmail(); $this->add(array( 'name' => 'enabled', 'required' => false, 'validators' => array( array( 'name' => 'InArray', 'options' => array( 'haystack' => array(false, true) ) ), ), )); } protected function _addElementEmail($currentUserId = 0) { $this->remove('email'); $this->add(array( 'name' => 'email', 'required' => true, 'filters' => array( array('name' => 'StripTags'), array('name' => 'StringTrim'), ), 'validators' => array( array( 'name' => 'EmailAddress', 'options' => array( 'useMxCheck' => false, 'useDeepMxCheck' => false, 'useDomainCheck' => false, ), ), array( 'name' => 'Db_NoRecordExists', 'options' => array( 'table' => Moto\Config::get('database.prefix') . 'users', 'field' => 'email', 'adapter' => Moto\Config::get('databaseAdapter'), 'exclude' => array( 'field' => 'id', 'value' => (int) $currentUserId, ) ) ) ), )); } public function setData($data) { if (!empty($data['id'])) { $this->_addElementEmail($data['id']); } $result = parent::setData($data); return $result; } }