<?php
namespace Moto\Twig\Widgets; use Twig_Error_Syntax; use Twig_NodeInterface; use Twig_Token; use Twig_TokenParser; class Widgets_TokenParser extends Twig_TokenParser { public function parse(Twig_Token $token) { $parser = $this->parser; $stream = $parser->getStream(); $widgetName = $stream->expect(Twig_Token::NAME_TYPE)->getValue(); $arguments = $this->parser->getExpressionParser()->parseArguments(true); $stream->expect(Twig_Token::BLOCK_END_TYPE); $this->parser->pushLocalScope(); $body = $this->parser->subparse(array($this, 'decideBlockEnd'), true); if ($stream->test(Twig_Token::NAME_TYPE)) { $value = $stream->next()->getValue(); if ($value != $widgetName) { throw new Twig_Error_Syntax(sprintf("Expected endwidget for widget '$widgetName' (but %s given)", $value), $stream->getCurrent()->getLine(), $stream->getFilename()); } } $this->parser->popLocalScope(); $stream->expect(Twig_Token::BLOCK_END_TYPE); return new Widgets_Node($widgetName, $arguments, $body, $token->getLine(), $this->getTag()); } public function decideBlockEnd(Twig_Token $token) { return $token->test('endwidget'); } public function getTag() { return 'widget'; } }