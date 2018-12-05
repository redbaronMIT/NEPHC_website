<?php
namespace Moto\Application\Pages\InputFilter; use Moto\InputFilter\AbstractInputFilter; use Moto; class PageVisibility extends AbstractInputFilter { protected $_name = 'pages.change:visibility'; public function init() { $this->add(array( 'name' => 'id', 'required' => true, 'filters' => array( array('name' => 'StripTags'), array('name' => 'StringTrim'), ), 'validators' => array( array( 'name' => 'Digits', 'break_chain_on_failure' => true ), array( 'name' => 'Db\RecordExists', 'options' => array( 'table' => Moto\Config::get('database.prefix') . 'pages', 'field' => 'id', 'adapter' => Moto\Config::get('databaseAdapter') ) ) ), )); $this->add(array( 'name' => 'type', 'required' => true, 'filters' => array( array('name' => 'StripTags'), array('name' => 'StringTrim'), ), 'validators' => array( array( 'name' => 'InArray', 'options' => array( 'haystack' => array('public', 'protected', 'private') ) ), ), )); $this->add(array( 'name' => 'params', 'required' => true, 'continue_if_empty' => true, 'validators' => array( array( 'name' => 'Moto\Application\Pages\PageVisibilityParamsValidator', ), ), )); } }