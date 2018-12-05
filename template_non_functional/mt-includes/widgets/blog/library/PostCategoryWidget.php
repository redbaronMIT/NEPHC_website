<?php
namespace Website\Widgets\Blog; use Website; use Moto; class PostCategoryWidget extends Website\Widgets\Blog\AbstractPostsWidget { protected $_name = 'blog.post_category'; protected static $_defaultProperties = array( 'font_style' => 'moto-text_normal', 'spacing' => array( 'top' => 'auto', 'right' => 'auto', 'bottom' => 'auto', 'left' => 'auto', ), 'align' => array( 'desktop' => 'left', 'tablet' => '', 'mobile-v' => '', 'mobile-h' => '', ), 'visible_on' => 'mobile-v' ); protected $_category; public function getTemplatePath($preset = null) { return '@websiteWidgets/blog/templates/post_category.twig.html'; } public function getCategory($page = null) { if (!$page) { $page = $this->getCurrentPage(); } if ($this->isPreviewMode() && $page->isTemplate()) { return [ 'name' => 'Category Name' ]; } if ($this->_category) { return $this->_category; } if (is_callable([$page, 'loadRelation'])) { $page->loadRelation('category'); $this->_category = $page->category; } return $this->_category; } } 