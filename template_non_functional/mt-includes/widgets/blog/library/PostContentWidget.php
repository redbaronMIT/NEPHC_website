<?php
namespace Website\Widgets\Blog; use Moto; use Website; class PostContentWidget extends Website\Widgets\Blog\AbstractPostsWidget { protected $_name = 'blog.post_content'; protected static $_defaultProperties = array( 'spacing' => array( 'top' => 'auto', 'right' => 'auto', 'bottom' => 'auto', 'left' => 'auto', ), ); public function getTemplatePath($preset = null) { return '@websiteWidgets/blog/templates/post_content.twig.html'; } } 