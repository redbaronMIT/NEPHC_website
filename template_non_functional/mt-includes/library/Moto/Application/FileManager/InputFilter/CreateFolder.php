<?php
 namespace Moto\Application\FileManager\InputFilter; use Moto\InputFilter\AbstractInputFilter; class CreateFolder extends AbstractInputFilter { protected $_name = 'fileManager.new:folder'; public function init() { $this->add(array( 'name' => 'path', 'required' => true, 'filters' => array( array('name' => 'StripTags'), array('name' => 'StringTrim'), array('name' => 'Moto\Filter\RelativePath'), ), 'validators' => array( array( 'name' => 'StringLength', 'options' => array( 'encoding' => 'UTF-8', 'min' => 1, 'max' => 200, ), 'break_chain_on_failure' => true ) ), )); } } 