<?php
namespace Moto\System; use Moto; use Zend; class ContentSectionHelper { protected static $_cache = array(); protected $_type; protected $_options = array( 'logging' => true, ); protected $_defaultSettings = array( 'enabled' => true, 'page_id' => null, 'show_comments' => true, 'date_format' => 'd.m.Y', 'post_template' => null, 'build' => 1, ); protected $_mainPage; protected $_defaultMainPageData = array( 'name' => '', 'url' => '@@TYPE@@', 'type' => '@@TYPE@@.index', 'parent_id' => 0, 'status' => Moto\Application\Pages\PageModel::STATUS_PUBLISH, 'visibility' => Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC, 'is_system' => true, 'content' => '
<div class="moto-widget moto-widget-row row-fixed" data-widget="row">
    <div class="container-fluid">
        <div class="row">
            <div class="moto-cell col-sm-9" data-container="container">
                {{ widget("@@TYPE@@.post_list", {"spacing":{"top":"auto","right":"auto","bottom":"auto","left":"auto"},"item_count":3,"read_more_label":"Read More","preset":"default"}) }}
            </div>
            <div class="moto-cell col-sm-3" data-container="container">
                {{ widget("@@TYPE@@.recent_posts", {"spacing":{"top":"auto","right":"auto","bottom":"auto","left":"auto"},"item_count":5,"title":{"label":"Recent Posts"},"preset":"default"}) }}
            </div>
        </div>
    </div>
</div>
'); protected $_postTemplate; protected $_tagTemplate; protected $_categoryTemplate; protected $_defaultPostTemplateData = array( 'name' => 'Post Template', 'url' => 'moto-template-@@TYPE@@-post', 'type' => 'template.@@TYPE@@.post', 'parent_id' => 0, 'status' => Moto\Application\Pages\PageModel::STATUS_PUBLISH, 'visibility' => Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC, 'is_system' => true, 'content' => '
<div class="moto-widget moto-widget-row row-fixed" data-widget="row">
    <div class="container-fluid">
        <div class="row">
            <div class="moto-cell col-sm-9" data-container="container">
                {{ widget("@@TYPE@@.post_name", {"spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"},"preset":"default","text_style":"moto-text_system_7"}) }}
				<div class="moto-widget moto-widget-row" data-widget="row">
					<div class="container-fluid">
						<div class="row">
							<div class="moto-cell col-sm-2" data-container="container">
								{{ widget("@@TYPE@@.post_published_on", {"spacing":{"top":"auto","right":"auto","bottom":"small","left":"auto"},"preset":"default","font_style":"moto-text_normal"}) }}
							</div>
							<div class="moto-cell col-sm-2" data-container="container">
								{{ widget("@@TYPE@@.post_author", {"spacing":{"top":"auto","right":"auto","bottom":"small","left":"auto"},"preset":"default","font_style":"moto-text_normal"}) }}
							</div>
							<div class="moto-cell col-sm-8" data-container="container">
								{{ widget("@@TYPE@@.post_category", {"spacing":{"top":"auto","right":"auto","bottom":"small","left":"auto"},"preset":"default","font_style":"moto-text_normal"}) }}
							</div>
						</div>
					</div>
				</div>
				{{ widget("@@TYPE@@.post_content", {"spacing":{"top":"auto","right":"auto","bottom":"small","left":"auto"}}) }}
				{{ widget("@@TYPE@@.post_tags", {"title":"Tags:","titleTextStyle":"moto-text_normal","spacing":{"top":"auto","right":"auto","bottom":"small","left":"auto"}}) }}
				{{ widget("@@TYPE@@.post_comments", {"spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"}}) }}
			</div>
			<div class="moto-cell col-sm-3" data-container="container">
                {{ widget("@@TYPE@@.recent_posts", {"spacing":{"top":"auto","right":"auto","bottom":"auto","left":"auto"},"item_count":5,"title":{"label":"Recent Posts"},"preset":"default"}) }}
            </div>
        </div>
    </div>
</div>
'); protected $_defaultPostContent = array( 'name' => 'My first post', 'url' => '', 'type' => '@@TYPE@@.post', 'status' => Moto\Application\Pages\PageModel::STATUS_PUBLISH, 'visibility' => Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC, 'short_description' => '<p class="moto-text_system_10">This is a short description for your post. You can easily change it under post properties using "Edit Short Description" button.</p>', 'content' => '
<div class="moto-widget moto-widget-row" data-widget="row">
    <div class="container-fluid">
        <div class="row">
            <div class="moto-cell col-sm-12" data-container="container">
                <div class="moto-widget moto-widget-text moto-preset-default moto-spacing-top-auto moto-spacing-right-auto moto-spacing-bottom-auto moto-spacing-left-auto" data-widget="text" data-preset="default" data-spacing="aaaa">
                    <div class="moto-widget-text-content moto-widget-text-editable"><p class="moto-text_system_10">This is a post content. Click here for editing</p></div>
                </div>
            </div>
        </div>
    </div>
</div>' ); protected $_defaultTagTemplateData = array( 'name' => 'Tag Template', 'url' => 'moto-template-@@TYPE@@-tag', 'type' => 'template.@@TYPE@@.tag', 'parent_id' => 0, 'status' => Moto\Application\Pages\PageModel::STATUS_PUBLISH, 'visibility' => Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC, 'is_system' => true, 'content' => '
<div class="moto-widget moto-widget-row moto-spacing-top-auto moto-spacing-right-auto moto-spacing-bottom-auto moto-spacing-left-auto row-fixed" data-grid-type="sm" data-widget="row" data-spacing="aaaa" style="" data-bg-position="left top">
    <div class="container-fluid">
        <div class="row" data-container="container">
            <div class="moto-widget moto-widget-row__column moto-cell col-sm-12 moto-spacing-top-auto moto-spacing-right-auto moto-spacing-bottom-auto moto-spacing-left-auto" style="" data-widget="row.column" data-container="container" data-spacing="aaaa" data-bg-position="left top">
                {{ widget( {"name":"@@TYPE@@.tag_name","properties":{"htmlTag":"h1","text_style":"moto-text_system_7","visible_on":"mobile-v","spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"},"align":{"desktop":"left","tablet":"","mobile-v":"","mobile-h":""}}} ) }}
                {{ widget( {"name":"@@TYPE@@.tag_description","properties":{"source":"short_description","firstPageOnly":false,"visible_on":"mobile-v","spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"}}} ) }}
                {{ widget( {"name":"@@TYPE@@.post_list","properties":{"spacing":{"top":"auto","right":"auto","bottom":"auto","left":"auto"},"item_count":5,"read_more_label":"Read More","style":{"title":{"font_style":"moto-text_system_7"},"meta":{"font_style":"moto-text_system_11"},"button":{"preset":"5","size":"small"},"feature_image":{"preset":"default"},"divider":{"preset":"default"}},"version":1}} ) }}
                {{ widget( {"name":"@@TYPE@@.tag_description","properties":{"source":"long_description","firstPageOnly":false,"visible_on":"mobile-v","spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"}}} ) }}
            </div>
        </div>
    </div>
</div>
'); protected $_defaultCategoryTemplateData = array( 'name' => 'Category Template', 'url' => 'moto-template-@@TYPE@@-category', 'type' => 'template.@@TYPE@@.category', 'parent_id' => 0, 'status' => Moto\Application\Pages\PageModel::STATUS_PUBLISH, 'visibility' => Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC, 'is_system' => true, 'content' => '
<div class="moto-widget moto-widget-row moto-spacing-top-auto moto-spacing-right-auto moto-spacing-bottom-auto moto-spacing-left-auto row-fixed" data-grid-type="sm" data-widget="row" data-spacing="aaaa" style="" data-bg-position="left top">
    <div class="container-fluid">
        <div class="row" data-container="container">
            <div class="moto-widget moto-widget-row__column moto-cell col-sm-12 moto-spacing-top-auto moto-spacing-right-auto moto-spacing-bottom-auto moto-spacing-left-auto" style="" data-widget="row.column" data-container="container" data-spacing="aaaa" data-bg-position="left top">
                {{ widget( {"name":"@@TYPE@@.category_name","properties":{"htmlTag":"h1","text_style":"moto-text_system_7","visible_on":"mobile-v","spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"},"align":{"desktop":"left","tablet":"","mobile-v":"","mobile-h":""}}} ) }}
                {{ widget( {"name":"@@TYPE@@.category_description","properties":{"source":"short_description","firstPageOnly":false,"visible_on":"mobile-v","spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"}}} ) }}
                {{ widget( {"name":"@@TYPE@@.post_list","properties":{"spacing":{"top":"auto","right":"auto","bottom":"auto","left":"auto"},"item_count":5,"read_more_label":"Read More","style":{"title":{"font_style":"moto-text_system_7"},"meta":{"font_style":"moto-text_system_11"},"button":{"preset":"5","size":"small"},"feature_image":{"preset":"default"},"divider":{"preset":"default"}},"version":1}} ) }}
                {{ widget( {"name":"@@TYPE@@.category_description","properties":{"source":"long_description","firstPageOnly":false,"visible_on":"mobile-v","spacing":{"top":"small","right":"auto","bottom":"small","left":"auto"}}} ) }}
            </div>
        </div>
    </div>
</div>
'); public function __construct($type) { $this->_type = $type; $this->init(); } public function init() { } public static function get($type) { if (!array_key_exists($type, static::$_cache)) { $class = __NAMESPACE__ . '\\' . ucfirst(strtolower($type)) . 'Helper'; if (class_exists($class)) { static::$_cache[$type] = new $class($type); } else { echo "Create instance from SELF\n"; static::$_cache[$type] = new self($type); } } return static::$_cache[$type]; } public function getType() { return $this->_type; } public function getPageType() { return Moto\Website\PageType::get($this->_type); } public function isConfigured() { $settings = $this->getCurrentSettings(); return (!empty($settings) && !empty($settings->page_id) && !empty($settings->post_template)); } public function getCurrentSettings($force = false) { $result = Moto\Website\Settings::get('content_section_' . $this->_type); if ($force) { return $result; } if (empty($result)) { $result = $this->getDefaultSettings(); } return $result; } public function getDefaultSettings($asArray = false) { if ($asArray) { return $this->_defaultSettings; } else { return json_decode(json_encode($this->_defaultSettings)); } } public function setDefaultSettings($settings) { if (!is_array($settings)) { $settings = json_decode(json_encode($settings), true); } if (!is_array($settings)) { return false; } $this->_defaultSettings = $settings; return true; } public function getOption($name, $default = null) { return Moto\Util::getFromArrayDeep($this->_options, $name, $default); } public function setOption($name, $value) { Moto\Util::setToArrayDeep($this->_options, $name, $value); return $this; } public function setCurrentSettings($settings) { if (is_array($settings)) { $settings = json_decode(json_encode($settings)); } Moto\Website\Settings::loadData(true); if (!Moto\Website\Settings::add('content_section_' . $this->_type, $settings, 'object')) { Moto\Website\Settings::set('content_section_' . $this->_type, $settings); } return $this; } public function info($str) { if ($this->getOption('logging', true)) { Moto\System\Log::info('@CONTENT_SECTION : ' . ucfirst($this->_type) . ' : ' . trim($str)); } } public function notice($str) { if ($this->getOption('logging', true)) { Moto\System\Log::notice('@CONTENT_SECTION : ' . ucfirst($this->_type) . ' : ' . trim($str)); } } public function critical($str) { Moto\System\Log::critical('@CONTENT_SECTION : ' . ucfirst($this->_type) . ' : ' . trim($str)); } public function exception($str, $e = null) { if ($e instanceof Moto\Exception) { Moto\System\Log::emergency('@CONTENT_SECTION : ' . ucfirst($this->_type) . ' : ' . trim($str), array( 'errors' => $e->getErrors(), )); } else { Moto\System\Log::emergency('@CONTENT_SECTION : ' . ucfirst($this->_type) . ' : ' . trim($str)); } if ($e instanceof \Exception) { throw $e; } else { throw new \Exception($str); } } public function getPostsCount() { $table = Moto\System::getDbTable('pages'); $items = $table->select(array( 'type' => $this->_type . '.post', )); return $items->count(); } public function createSamplePost($index = null) { $mainPage = $this->getOrCreateMainPage(); $postTemplate = $this->getOrCreatePostTemplate(); if ($index !== null && empty($this->_digitToWord[$index])) { $this->critical('Cant create sample post by "digitToWord"'); return false; } $table = new Moto\Application\Pages\PagesTable(); $table->useResultAsModel(true); if ($index === null) { $url = 'my-first-post'; } else { $url = 'post-' . $index; } $post = $table->getByUrl($mainPage->url . '/' . $url); if ($post) { $this->notice('Sample Post Exists [ url = ' . $url . ' ]'); } else { $this->info('Creating Sample Post [ url = ' . $url . ' ]'); $data = $this->_defaultPostContent; $data = json_encode($data); $data = str_replace('@@TYPE@@', $this->_type, $data); $data = str_replace('@@INDEX@@', $index, $data); if ($index !== null) { $data = str_replace('@@INDEX_NAME@@', $this->_digitToWord[$index], $data); } $data = json_decode($data, true); if ($index !== null && empty($data['name'])) { $data['name'] = $this->_digitToWord[$index] . ' Post'; } $data['parent_id'] = $mainPage->id; $data['url'] = $url; try { $post = Moto\Application\Pages\Service::save($data); } catch (\Exception $e) { $this->exception('Sample Post Not Saved', $e); } $post->type = $data['type']; $post->status = Moto\Application\Pages\PageModel::STATUS_DRAFT; $post->visibility = Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC; $post->background_id = 0; $post->background = ''; if ($post->save()) { $this->info('Sample Post Created [ id = ' . $post->id . ' ]'); } else { $this->exception('Sample Post Created but Not Updated [ id = ' . $post->id . ' ]'); } } return $post; } public function getOrCreateMainPage() { if ($this->_mainPage !== null) { return $this->_mainPage; } $table = new Moto\Application\Pages\PagesTable(); $table->useResultAsModel(true); $items = $table->select(array( 'type' => $this->_type . '.index', )); if ($items->count() === 0) { $this->info('Creating Main Page'); $data = $this->_defaultMainPageData; $settings = $this->getCurrentSettings(); $settings->page_id = null; $data = json_encode($data); $data = str_replace('@@TYPE@@', $this->_type, $data); $data = json_decode($data, true); if (empty($data['name'])) { $data['name'] = ucfirst($this->_type); } $data['type'] = $this->_type . '.index'; $url = $data['url']; for ($index = 1; $index < 1000; $index++) { $mainPage = $table->getByUrl($url); if (!$mainPage) { $data['url'] = $url; break; } $url = $data['url'] . '-' . ($index + 1); } $filter = new Moto\Application\Pages\InputFilter\NewPage(); $filter->setData($data); $values = $filter->getValues(); $mainPage = $table->create(); $mainPage->setFromArray($values); $mainPage->type = $data['type']; $mainPage->status = Moto\Util::getFromArrayDeep($data, 'status', Moto\Application\Pages\PageModel::STATUS_DRAFT); $mainPage->visibility = Moto\Util::getFromArrayDeep($data, 'visibility', Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC); $mainPage->is_system = true; $mainPage->properties->meta->hideCanonical = true; try { if ($mainPage->save()) { $this->info('Main Page Saved [ id = ' . $mainPage->id . ' ]'); } else { $this->exception('Main Page Not Saved'); } } catch (\Exception $e) { $this->exception('Main Page Not Saved', $e); } } else { $mainPage = $items->current(); $this->notice('Main Page Exists [ id = ' . $mainPage->id . ' ]'); } $this->_mainPage = $mainPage; return $this->_mainPage; } public function getOrCreatePostTemplate() { if ($this->_postTemplate !== null) { return $this->_postTemplate; } $mainPage = $this->getOrCreateMainPage(); $table = new Moto\Application\Pages\PagesTable(); $table->useResultAsModel(true); $items = $table->select(array( 'type' => 'template.' . $this->_type . '.post', )); if ($items->count() === 0) { $this->info('Creating Post Template'); $settings = $this->getCurrentSettings(); $settings->post_template = null; $data = $this->_defaultPostTemplateData; $data = json_encode($data); $data = str_replace('@@TYPE@@', $this->_type, $data); $data = json_decode($data, true); if (empty($data['name'])) { $data['name'] = ucfirst($this->_type); } $data['type'] = 'template.' . $this->_type . '.post'; $url = $data['url']; for ($index = 0; $index < 1000; $index++) { $postTemplate = $table->getByUrl($url); if (!$postTemplate) { $data['url'] = $url; break; } $url = $data['url'] . '-' . ($index + 1); } $filter = new Moto\Application\Pages\InputFilter\NewPage(); $filter->setData($data); $values = $filter->getValues(); $postTemplate = $table->create(); $postTemplate->setFromArray($values); $postTemplate->type = $data['type']; $postTemplate->status = Moto\Util::getFromArrayDeep($data, 'status', Moto\Application\Pages\PageModel::STATUS_PUBLISH); $postTemplate->visibility = Moto\Util::getFromArrayDeep($data, 'visibility', Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC); $postTemplate->is_system = true; $postTemplate->properties->meta->hideCanonical = true; try { if ($postTemplate->save()) { $this->info('Post Template Saved [ id = ' . $postTemplate->id . ' ]'); } else { $this->exception('Post Template Not Saved'); } } catch (\Exception $e) { $this->exception('Post Template Not Created', $e); } } else { $postTemplate = $items->current(); $this->notice('Post Template Exists [ id = ' . $postTemplate->id . ' ]'); } $this->_postTemplate = $postTemplate; return $this->_postTemplate; } public function getOrCreateTagTemplate() { if ($this->_tagTemplate !== null) { return $this->_tagTemplate; } $mainPage = $this->getOrCreateMainPage(); $table = new Moto\Application\Pages\PagesTable(); $table->useResultAsModel(true); $items = $table->select(array( 'type' => 'template.' . $this->_type . '.tag', )); if ($items->count() === 0) { $this->info('Creating Tag Template'); $settings = $this->getCurrentSettings(); $settings->tag_template = null; $data = $this->_defaultTagTemplateData; $data = json_encode($data); $data = str_replace('@@TYPE@@', $this->_type, $data); $data = json_decode($data, true); if (empty($data['name'])) { $data['name'] = ucfirst($this->_type); } $data['type'] = 'template.' . $this->_type . '.tag'; $url = $data['url']; for ($index = 0; $index < 1000; $index++) { $tagTemplate = $table->getByUrl($url); if (!$tagTemplate) { $data['url'] = $url; break; } $url = $data['url'] . '-' . ($index + 1); } $filter = new Moto\Application\Pages\InputFilter\NewPage(); $filter->setData($data); $values = $filter->getValues(); $tagTemplate = $table->create(); $tagTemplate->setFromArray($values); $tagTemplate->type = $data['type']; $tagTemplate->status = Moto\Util::getFromArrayDeep($data, 'status', Moto\Application\Pages\PageModel::STATUS_PUBLISH); $tagTemplate->visibility = Moto\Util::getFromArrayDeep($data, 'visibility', Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC); $tagTemplate->is_system = true; $tagTemplate->properties->meta->hideCanonical = true; try { if ($tagTemplate->save()) { $this->info('Tag Template Saved [ id = ' . $tagTemplate->id . ' ]'); } else { $this->exception('Tag Template Not Saved'); } } catch (\Exception $e) { $this->exception('Tag Template Not Created', $e); } } else { $tagTemplate = $items->current(); $this->notice('Tag Template Exists [ id = ' . $tagTemplate->id . ' ]'); } $this->_tagTemplate = $tagTemplate; return $this->_tagTemplate; } public function getOrCreateCategoryTemplate() { if ($this->_categoryTemplate !== null) { return $this->_categoryTemplate; } $mainPage = $this->getOrCreateMainPage(); $table = new Moto\Application\Pages\PagesTable(); $table->useResultAsModel(true); $items = $table->select(array( 'type' => 'template.' . $this->_type . '.category', )); if ($items->count() === 0) { $this->info('Creating Category Template'); $settings = $this->getCurrentSettings(); $settings->category_template = null; $data = $this->_defaultCategoryTemplateData; $data = json_encode($data); $data = str_replace('@@TYPE@@', $this->_type, $data); $data = json_decode($data, true); if (empty($data['name'])) { $data['name'] = ucfirst($this->_type); } $data['type'] = 'template.' . $this->_type . '.category'; $url = $data['url']; for ($index = 0; $index < 1000; $index++) { $categoryTemplate = $table->getByUrl($url); if (!$categoryTemplate) { $data['url'] = $url; break; } $url = $data['url'] . '-' . ($index + 1); } $filter = new Moto\Application\Pages\InputFilter\NewPage(); $filter->setData($data); $values = $filter->getValues(); $categoryTemplate = $table->create(); $categoryTemplate->setFromArray($values); $categoryTemplate->type = $data['type']; $categoryTemplate->status = Moto\Util::getFromArrayDeep($data, 'status', Moto\Application\Pages\PageModel::STATUS_PUBLISH); $categoryTemplate->visibility = Moto\Util::getFromArrayDeep($data, 'visibility', Moto\Application\Pages\PageModel::VISIBILITY_PUBLIC); $categoryTemplate->is_system = true; $categoryTemplate->properties->meta->hideCanonical = true; try { if ($categoryTemplate->save()) { $this->info('Category Template Saved [ id = ' . $categoryTemplate->id . ' ]'); } else { $this->exception('Category Template Not Saved'); } } catch (\Exception $e) { $this->exception('Category Template Not Created', $e); } } else { $categoryTemplate = $items->current(); $this->notice('Category Template Exists [ id = ' . $categoryTemplate->id . ' ]'); } $this->_categoryTemplate = $categoryTemplate; return $this->_categoryTemplate; } protected function _beforeCheckOrInstall() { } protected function _afterCheckOrInstall() { } public function checkOrInstall() { $this->info('Check or Install'); Moto\Website\Settings::loadData(true); $this->_beforeCheckOrInstall(); $settings = $this->getCurrentSettings(); if (!$this->isConfigured()) { $this->setCurrentSettings($settings); } $mainPage = $this->getOrCreateMainPage(); $postTemplate = $this->getOrCreatePostTemplate(); $tagTemplate = $this->getOrCreateTagTemplate(); $categoryTemplate = $this->getOrCreateCategoryTemplate(); if (!$this->isConfigured() && $this->getPostsCount() === 0) { $this->createSamplePost(); } $settings->page_id = $mainPage->id; $settings->post_template = $postTemplate->url; $settings->post_template_id = $postTemplate->id; $settings->tag_template = $tagTemplate->url; $settings->tag_template_id = $tagTemplate->id; $settings->category_template = $categoryTemplate->url; $settings->category_template_id = $categoryTemplate->id; $this->setCurrentSettings($settings); $this->_afterCheckOrInstall(); $this->info('Settings Saved'); $this->info('Done'); } } class BlogHelper extends ContentSectionHelper { protected $_defaultSettings = array( 'enabled' => true, 'page_id' => null, 'show_comments' => true, 'date_format' => 'd.m.Y', 'post_template' => null, 'build' => 1, 'design' => array( 'post_list' => array( 'feature_image' => array( 'preset' => 'default', ), 'title' => array( 'font_style' => 'moto-text_system_7', ), 'meta' => array( 'font_style' => 'moto-text_system_11', ), 'button' => array( 'preset' => '5', 'size' => 'small', ), 'divider' => array( 'preset' => 'default', ), ), 'recent_posts' => array( 'feature_image' => array( 'preset' => 'default', ), 'heading' => array( 'font_style' => 'moto-text_system_7', ), 'title' => array( 'font_style' => 'moto-text_system_8', ), ), ) ); protected function _afterCheckOrInstall() { $this->getOrCreatePaginationPreset(); } public function getOrCreatePaginationPreset() { $table = new Moto\Application\Presets\PresetsTable(); $table->useResultAsModel(true); $preset = $table->getWidgetPreset('pagination', 'moto-preset-default'); if ($preset) { $this->notice('Pagination Preset Exists [ id = ' . $preset->id . ' ]'); } else { $this->info('Creating Pagination Preset'); $preset = $table->create(array( 'name' => 'Pagination 1', 'widget_name' => 'pagination', 'class_name' => 'moto-preset-default', 'is_system' => 1, 'is_responsive' => 0, 'properties' => '{"pagination":{"desktop":{"base":{"font-family":"tahoma, arial, helvetica, sans-serif","font-style":"normal","font-weight":"400","font-size":"14px"}}},"item":{"desktop":{"base":{"margin-right":"5px"}}},"item_link_active":{"desktop":{"base":{"font-style":"normal","font-weight":"700","color":"#2e3a46","background-color":"","border-color":"transparent","text-decoration":"none"}}},"item_link":{"desktop":{"base":{"color":"#81868c","background-color":"transparent","border-color":"transparent","border-width":"1px","border-radius":"0%","border-style":"solid","text-decoration":"none","width":"31px","height":"31px"},"hover":{"color":"#d3d8db","background-color":"","border-color":"","text-decoration":""}}}}', 'template' => 'default', )); try { if ($table->save($preset)) { $this->info('Pagination Preset Saved [ id = ' . $preset->id . ' ]'); } else { $this->exception('Pagination Preset Not Saved'); } } catch (\Exception $e) { $this->exception('Pagination Preset Not Created', $e); } } return $preset; } } class NotFoundPopupHelper extends ContentSectionHelper { public function checkOrInstall() { $this->info('Checking...'); Moto\Website\Settings::loadData(true); $table = new Moto\Application\Content\Table\ContentBlocks(); $table->useResultAsModel(false); $record = null; $popupId = Moto\Website\Settings::get('notfound_popup_id', 0) * 1; if ($popupId > 0) { $record = $table->getById($popupId); if ($record) { $record->is_system = 1; $table->save($record); $this->info('Already exists'); return true; } } $result = $table->select(array( 'is_system' => 1, 'type' => 'popup' )); if ($result->count() > 1) { $this->critical('Found multiple system popups'); return false; } if ($result->count() === 1) { $record = $result->current(); $this->info('Already exists'); } else { $result = $table->select(array( 'name' => 'Popup 404', 'type' => 'popup' )); if ($result->count() === 1) { $record = $result->current(); $this->info('Already exists'); } } if (!$record) { $this->info('Create default popup...'); $values = array( 'name' => 'Popup 404', 'type' => 'popup', 'content' => '<div class="moto-widget moto-widget-row moto-spacing-top-auto moto-spacing-right-auto moto-spacing-bottom-auto moto-spacing-left-auto" data-grid-type="sm" data-widget="row" data-spacing="aaaa" style="">
    <div class="container-fluid">
        <div class="row" data-container="container">
			<div class="moto-widget moto-widget-row__column moto-cell col-sm-12 moto-spacing-top-auto moto-spacing-right-auto moto-spacing-bottom-auto moto-spacing-left-auto" style="" data-widget="row.column" data-container="container" data-spacing="aaaa">
				<div class="moto-widget moto-widget-text moto-preset-default moto-spacing-top-medium moto-spacing-right-auto moto-spacing-bottom-medium moto-spacing-left-auto" data-widget="text" data-preset="default" data-spacing="mama" data-animation="">
					<div class="moto-widget-text-content moto-widget-text-editable"><p class="moto-text_system_8" style="text-align: center;">Unfortunately, the popup was not found.</p></div>
				</div>
			</div>
        </div>
    </div>
</div>', 'properties' => array( 'width' => '600px' ) ); try { $record = Moto\Application\Content\Service::save($values); $record = (object) $record->toArray(); } catch (Moto\Json\Server\Exception $e) { $this->exception('Not Saved because [ ' . $e->getMessage() . ' ]', $e); } $record->is_system = 1; if (!$table->save($record)) { $this->exception('Can not update popup [ id = ' . $record->id . ' ]'); return false; } } if (Moto\Website\Settings::isExists('notfound_popup_id')) { Moto\Website\Settings::set('notfound_popup_id', $record->id); } else { Moto\Website\Settings::add('notfound_popup_id', $record->id, 'int'); } Moto\Website\Settings::loadData(true); } } 