<?php
namespace Moto; use Moto; use Zend; class System { const ENGINE_KEY = 'MOTO_ENGINE'; const ENGINE_VALUE = 'website'; const ENV_DEVELOPMENT = 'development'; const ENV_TESTING = 'testing'; const ENV_PRODUCTION = 'production'; protected static $_stage = 'production'; protected static $_systemRouter = null; protected static $_paths = array( 'website' => './', 'plugins' => '@website/mt-content/plugins', 'library' => '@website/mt-includes/library', 'config' => '@website/mt-includes/config', 'baseConfigFile' => '@website/mt-includes/config/base.php', 'userConfigFile' => '@website/mt-includes/config/settings.php', 'admin' => '@website/mt-admin', 'install' => '@website/mt-admin/install', ); protected static $_urls = array( 'website' => '', 'adminApplication' => './js', 'adminStyles' => './css', ); protected static $_config = array(); protected static $_applicationInstances = array(); protected static $_applicationsMap = array( 'website' => 'Moto\\Website\\Application', ); protected static $_defaultApplicationName = 'website'; protected static $_databaseAdapter = null; protected static $_server = null; protected static $_installedFlag = null; protected static $_request; protected static $_initialized = false; protected static $_initializing = false; protected static $_flags = array(); protected static $_interceptors = array(); public static function addInterceptor($name, $callback) { if (empty(static::$_interceptors[$name])) { static::$_interceptors[$name] = array(); } static::$_interceptors[$name][] = $callback; } protected static function _callInterceptor($name, $target = null) { if (empty(static::$_interceptors[$name]) || !is_array(static::$_interceptors[$name])) { return null; } $args = func_get_args(); $args = array_slice($args, 2); $event = array( 'name' => $name, 'target' => $target, 'args' => $args, ); $event = (object) $event; $interceptors = static::$_interceptors[$name]; foreach ($interceptors as $interceptor) { try { if (is_callable($interceptor)) { $interceptorArgs = $args; array_unshift($interceptorArgs, $event, $target); $response = call_user_func_array($interceptor, $interceptorArgs); if (!is_object($response) && $response !== null) { $event->target = $response; $target = $response; } } } catch (\Exception $e) { } } return $target; } public static function bootstrap($params = array()) { if (static::$_initializing) { return; } static::$_initializing = true; if (!defined('MOTO_PLUGINS_BOOT')) { define('MOTO_PLUGINS_BOOT', true); } if (!defined('MOTO_SYSTEM_REQUEST_AUTO_REDIRECT')) { define('MOTO_SYSTEM_REQUEST_AUTO_REDIRECT', true); } $level = error_reporting(); $display = @ini_get('display_errors'); error_reporting(E_ALL); @ini_set('display_errors', 'on'); try { $result = static::_callInterceptor('bootstrap:params', $params); if (is_array($result)) { $params = $result; } defined(self::ENGINE_KEY) or define(self::ENGINE_KEY, self::ENGINE_VALUE); static::_initPaths(static::_safeGet($params, 'paths', array())); static::_initConfig(static::_safeGet($params, 'config', array())); static::initEnvironment(); static::loadConfig(); static::initIncludePath(); static::initAutoloader(); static::_callInterceptor('bootstrap:starting'); static::processWebsiteMaintenancePage(); Moto\Hook::init(); Moto\System\Event::init(); static::_callInterceptor('events.initialized'); Moto\Hook::on(Moto\Hook::SYSTEM_BOOTSTRAP_BEFORE, function () { Moto\Config::init(); Moto\System::initBrand(); Moto\System\Log::init(Moto\Config::get('logLevel')); }); Moto\Hook::trigger(Moto\Hook::SYSTEM_BOOTSTRAP_BEFORE); Moto\Hook::on(Moto\Hook::SYSTEM_BOOTSTRAP, function () { Moto\System::checkInstall(); Moto\System\Request::init(); Moto\System\Request::autoRedirect(MOTO_SYSTEM_REQUEST_AUTO_REDIRECT); }, Moto\Hook::_PRIORITY_DEFAULT + 10); Moto\Hook::on(Moto\Hook::SYSTEM_BOOTSTRAP, function (Zend\EventManager\Event $event) { if (Moto\System::isInstallEngine() && !Moto\System::isInstalled()) { $event->stopPropagation(); } }); Moto\Hook::on(Moto\Hook::SYSTEM_BOOTSTRAP, function () { Moto\System::initDatabaseAdapter(); Moto\System::checkDatabase(); Moto\Website\Settings::init(); Moto\System\ProductInformation::init(); Moto\System::initAcl(); Moto\Features::init(); Moto\Website\Theme::init(); Moto\Website\PasswordProtection::init(); Moto\Application\Util\Mailer::init(); }, Moto\Hook::_PRIORITY_DEFAULT - 10); Moto\Hook::on(Moto\Hook::SYSTEM_BOOTSTRAP_AFTER, function () { Moto\System\PluginManager::init(MOTO_PLUGINS_BOOT); Moto\System::_initPlugins(); }, Moto\Hook::_PRIORITY_DEFAULT); Moto\Hook::trigger(Moto\Hook::SYSTEM_BOOTSTRAP); if (static::isInstalled()) { $auth = Moto\Authentication\AuthenticationService::getInstance(); $auth->setOption('cookieIsSecure', Moto\System\Request::isSSL()); $auth->setOption('cookieDomain', null); $cookiePath = static::getRelativeUrl(); if (!empty($cookiePath)) { $auth->setOption('cookiePath', $cookiePath); @ini_set('session.cookie_path', $cookiePath); } } Moto\Hook::trigger(Moto\Hook::SYSTEM_BOOTSTRAP_AFTER); static::_callInterceptor('bootstrap:finished'); } catch (\Exception $e) { } error_reporting($level); @ini_set('display_errors', $display); static::$_initialized = true; } protected static function _initPaths($paths = null) { if (!is_array($paths)) { return false; } foreach ($paths as $name => $path) { static::setPath($name, $path); } return true; } protected static function _initUrls($urls = null) { if (!is_array($urls)) { return false; } foreach ($urls as $name => $url) { static::setUrl($name, $url); } return true; } protected static function _initConfig($config) { static::mergeConfig($config); } protected static function loadConfig() { static::loadConfigFile(static::getAbsolutePath('@baseConfigFile')); static::loadConfigFile(static::getAbsolutePath('@userConfigFile')); static::loadConfigHostFile(); if (static::$_config['database']['driver'] == 'Mysqli') { static::$_config['database']['options'] = array('buffer_results' => true); } static::_callInterceptor('config.loaded', static::$_config); static::$_config['env'] = APPLICATION_ENV; if (!empty(static::$_config['path'])) { static::_initPaths(static::$_config['path']); } if (!empty(static::$_config['url'])) { static::_initUrls(static::$_config['url']); } } protected static function loadConfigFile($file) { $config = array(); if (is_file($file)) { include_once $file; static::mergeConfig($config); } } public static function loadConfigHostFile() { if (APPLICATION_ENV != 'production' && !empty($_SERVER['HTTP_HOST'])) { $host = $_SERVER['HTTP_HOST']; $host = str_replace('www.', '', $host); $host = strtolower($host); $configFile = '@config'; if (getenv('APPLICATION_DEMO_MODE') === 'yes' || APPLICATION_ENV == 'demo') { $configFile .= '/config.demo.php'; } else { $configFile .= '/config.' . $host . '.php'; } $configFile = static::getAbsolutePath($configFile); static::loadConfigFile($configFile); } } public static function mergeConfig($config) { if (is_array($config)) { static::$_config = static::_merge(static::$_config, $config); } } protected static function _merge(array $a, array $b, $keepNumeric = false) { foreach ($b as $key => $value) { if (array_key_exists($key, $a)) { if (is_int($key) && !$keepNumeric) { $a[] = $value; } elseif (is_array($value) && is_array($a[$key])) { $a[$key] = static::_merge($a[$key], $value, $keepNumeric); } else { $a[$key] = $value; } } else { $a[$key] = $value; } } return $a; } public static function getConfig() { $config = static::$_config; return $config; } public static function setPath($name, $value) { $value = trim($value); $value = preg_replace('/[\/\\\]+/', '/', $value); if ($value !== '/') { $value = rtrim($value, '/'); } static::$_paths[$name] = $value; } public static function getPath($name, $default = null) { $result = (array_key_exists($name, static::$_paths) ? static::$_paths[$name] : $default); return $result; } public static function hasPath($name) { return (array_key_exists($name, static::$_paths)); } public static function setUrl($name, $value, $force = false) { $value = trim($value); if (!$force) { $value = rtrim($value, '/') . '/'; } static::$_urls[$name] = $value; return $value; } public static function unsetUrl($name) { unset(static::$_urls[$name]); } public static function getUrl($name, $default = null, $urls = null) { if (empty($urls)) { $urls = static::$_urls; } else { $urls = array_merge(static::$_urls, $urls); } $result = (array_key_exists($name, $urls) ? $urls[$name] : $default); return $result; } public static function hasUrl($name) { return (array_key_exists($name, static::$_urls)); } public static function getUrls() { return static::$_urls; } public static function getPaths() { return static::$_paths; } public static function initIncludePath() { static::addIncludePath(static::getAbsolutePath('@library')); } public static function addIncludePath($path, $before = true) { $includePath = get_include_path(); if (is_array($path)) { $path = implode(PATH_SEPARATOR, $path); } if ($before) { $includePath = $path . PATH_SEPARATOR . $includePath; } else { $includePath .= PATH_SEPARATOR . $path; } set_include_path($includePath); } public static function initAutoloader() { $files = static::$_config['autoloadFiles']; if (is_array($files)) { foreach ($files as $file) { $file = Moto\System::getAbsolutePath($file); if (file_exists($file)) { require_once $file; } } } $config = static::$_config['autoloaderOptions']; foreach ($config as $class => $rules) { if (!isset($config[$class])) { $config[$class] = array(); } if ($class === 'Zend\Loader\ClassMapAutoloader') { if (is_array($rules) && !empty($rules)) { $paths = array(); foreach ($rules as $index => $path) { if ($path[0] === '@') { $path = static::getAbsolutePath($path); } if (file_exists($path)) { $path[] = $path; } } $config[$class] = $paths; } continue; } foreach ($rules as $type => $pair) { foreach ($pair as $key => $value) { $config[$class][$type][$key] = static::getAbsolutePath($value); } } } static::$_config['autoloaderOptions'] = $config; require_once 'Zend/Loader/AutoloaderFactory.php'; Zend\Loader\AutoloaderFactory::factory($config); static::_callInterceptor('autoloader.initialized'); } public static function addClassToMapLoader($classes, $path = null) { if (is_string($classes) && is_string($path)) { $classes = array($classes => $path); } if (!is_array($classes) || empty($classes)) { return false; } $loader = Zend\Loader\AutoloaderFactory::getRegisteredAutoloader('Zend\Loader\ClassMapAutoloader'); if (!$loader) { return false; } foreach ($classes as $class => $path) { if ($path[0] === '@') { $classes[$class] = static::getAbsolutePath($path); } } $loader->registerAutoloadMap($classes); return true; } public static function addFileMapToMapLoader($path) { if (!is_string($path)) { return false; } if ($path[0] === '@') { $path = static::getAbsolutePath($path); } if (!file_exists($path)) { return false; } $loader = Zend\Loader\AutoloaderFactory::getRegisteredAutoloader('Zend\Loader\ClassMapAutoloader'); if (!$loader) { return false; } $loader->registerAutoloadMap($path); return true; } public static function addNamespaceToLoader($namespaces, $path = null) { if (is_string($namespaces) && is_string($path)) { $namespaces = array($namespaces => $path); } if (!is_array($namespaces) || empty($namespaces)) { return false; } $loader = Zend\Loader\AutoloaderFactory::getRegisteredAutoloader('Zend\Loader\StandardAutoloader'); if (!$loader) { return false; } foreach ($namespaces as $namespace => $path) { if ($path[0] === '@') { $path = static::getAbsolutePath($path); } $loader->registerNamespace($namespace, $path); } return true; } public static function getRelativePath($path, $root = 'website') { $namespace = null; if ($path[0] !== '@' && strpos($path, '/') === false) { $path = '@' . $path; } if ($path[0] == '@') { $pos = strpos($path, '/'); if ($pos) { $namespace = substr($path, 1, $pos - 1); $path = substr($path, $pos + 1); } else { $namespace = substr($path, 1); $path = ''; } } if ($namespace === $root) { return $path; } if (null != $namespace) { $path = static::getPath($namespace) . (empty($path) ? '' : '/' . $path); } if ($path[0] === '@') { $path = static::getRelativePath($path); } return $path; } public static function getAbsolutePath($path) { $namespace = null; if (isset($path[0]) && $path[0] !== '@' && strpos($path, '/') === false) { $path = '@' . $path; } if (isset($path[0]) && $path[0] == '@') { $pos = strpos($path, '/'); if ($pos) { $namespace = substr($path, 1, $pos - 1); $path = substr($path, $pos + 1); } else { $namespace = substr($path, 1); $path = ''; } } if (null != $namespace) { $path = static::getPath($namespace) . (empty($path) ? '' : '/' . $path); } if (isset($path[0]) && $path[0] === '@') { $path = static::getAbsolutePath($path); } else { $path = preg_replace('/[\/\\\]+/', '/', (string) $path); } return $path; } public static function getRelativeUrl($path = '@website', $urls = array(), $isFile = null) { if (empty($path) || ($path[0] !== '@' && !preg_match('/^([a-z]+:)?\.?\/\/?/', $path))) { $path = '@website/' . ltrim($path, '/'); } $info = parse_url(static::getUrl('website')); $websitePath = '/'; if (!empty($info['path'])) { $websitePath = rtrim($info['path'], '/') . '/'; } $urls['website'] = $websitePath; return static::getAbsoluteUrl($path, $urls, $isFile); } public static function getAbsoluteUrl($path = '', $urls = null, $isFile = null) { $namespace = null; if (empty($path) || ($path[0] !== '@' && !preg_match('/^([a-z]+:)?\.?\/\/?/', $path))) { $path = '@website/' . ltrim($path, '/'); } if ($path[0] !== '@' && strpos($path, '/') === false) { $path = '@' . $path; } if ($path[0] == '@') { $pos = strpos($path, '/'); if ($pos) { $namespace = substr($path, 1, $pos - 1); $path = substr($path, $pos + 1); } else { $namespace = substr($path, 1); $path = ''; } } if (null != $namespace) { if ($isFile === null) { $isFile = (substr($namespace, -4) === 'File'); } $namespacePath = static::getUrl($namespace, null, $urls); if (null === $namespacePath) { $namespacePath = static::getPath($namespace); } if (empty($path)) { $path = $namespacePath; } else { $path = rtrim($namespacePath, '/') . '/' . $path; } if ($isFile) { $path = rtrim($path, '/'); } } if ($path[0] === '@') { $path = static::getAbsoluteUrl($path, $urls, $isFile); } return $path; } public static function getUploadUrl($path) { if (!is_string($path) || strlen($path) < 1) { return $path; } if ($path[0] !== '@' && !preg_match('/^([a-z]+:)?\.?\/\/?/', $path)) { $path = '@userUploads/' . $path; } return static::getRelativeUrl($path, null, true); } public static function getUploadAbsoluteUrl($path) { if (!is_string($path) || strlen($path) < 1) { return $path; } if ($path[0] !== '@' && !preg_match('/^([a-z]+:)?\.?\/\/?/', $path)) { $path = '@userUploads/' . $path; } return static::getAbsoluteUrl($path, null, true); } public static function getUploadAbsolutePath($path) { if (!is_string($path) || strlen($path) < 1) { return $path; } if ($path[0] !== '@' && !preg_match('/^([a-z]+:)?\.?\/\/?/', $path)) { $path = '@userUploads/' . $path; } return static::getAbsolutePath($path); } protected static function initEnvironment() { $stage = static::$_stage; if (!defined('PHP_VERSION_ID')) { $version = explode('.', PHP_VERSION); define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2])); } if (defined('APPLICATION_ENV')) { $stage = APPLICATION_ENV; } else { if (getenv('APPLICATION_ENV')) { $stage = getenv('APPLICATION_ENV'); } define('APPLICATION_ENV', $stage); } $timezone = null; if (function_exists('date_default_timezone_get')) { $timezone = @date_default_timezone_get(); } if (empty($timezone)) { $timezone = 'UTC'; } if (function_exists('date_default_timezone_set')) { @date_default_timezone_set($timezone); } if (function_exists('mb_internal_encoding')) { @mb_internal_encoding('UTF-8'); } static::$_stage = $stage; static::_callInterceptor('environment.initialized'); } public static function getStage() { return static::$_stage; } public static function isDevelopmentStage() { return (static::getStage() == static::ENV_DEVELOPMENT); } public static function isInstalled() { if (null === static::$_installedFlag) { static::$_installedFlag = true; static::$_installedFlag &= MOTO_INSTALLED; static::$_installedFlag &= !!Moto\Config::get('database.database'); } return static::$_installedFlag; } public static function checkInstall() { if (!defined('MOTO_INSTALLED')) { define('MOTO_INSTALLED', false); } if (MOTO_CHECK_INSTALL && !static::isInstalled()) { $dir = static::getAbsolutePath('@install'); $location = static::getRelativePath('@install'); if (MOTO_ENGINE === 'admin') { $location = strrev(dirname(strrev($location))); } $location = './' . $location; if (is_dir($dir)) { header('Location: ' . $location); } else { echo 'Error: "' . $location . '" directory is missing'; } exit; } if (MOTO_INSTALLED && MOTO_ENGINE === 'admin') { $path = static::getAbsolutePath('@website/mt-install'); if (file_exists($path)) { $exception = new \Exception('INSTALLATION_FOLDER_EXISTS'); static::reportException($exception); exit; } } } public static function reportException($exception) { $path = static::getAbsolutePath('@adminServerErrorFile'); if (file_exists($path)) { include $path; } } public static function getSystemRouter() { if (null == static::$_systemRouter) { static::$_systemRouter = Moto\System\Router::getInstance('system'); static::$_systemRouter->addPath('*', 'website', 5); Moto\Website\BlogApplication::bootstrap(); if (static::getStage() == static::ENV_DEVELOPMENT) { $applications = Moto\Config::get('__systemRouter__.applications'); if (is_array($applications)) { foreach ($applications as $application) { static::registerApplication($application['name'], $application['class'], $application['urls'], $application['priority']); } } } } return static::$_systemRouter; } public static function processRedirection() { if (static::isAdminEngine() || !Moto\System\Request::isGet() || array_key_exists(__FUNCTION__, static::$_flags)) { return; } static::$_flags[__FUNCTION__] = true; $router = Moto\System\Router::getInstance('redirection'); $router->setOption('returnMatch', true); $rules = Moto\Website\Settings::get('redirection_rules'); if (empty($rules) || !is_array($rules)) { return; } for ($i = 0, $len = count($rules); $i < $len; $i++) { $rule = $rules[$i]; if (!Moto\Util::getFrom($rule, 'enabled', true)) { continue; } $sourceUrl = Moto\Util::getFromArrayDeep($rule, 'source.url'); $targetUrl = Moto\Util::getFromArrayDeep($rule, 'target.url'); if (empty($sourceUrl) || empty($targetUrl)) { continue; } $priority = $len - $i + 25; $options = array(); $options['wildcard'] = (Moto\Util::getFromArrayDeep($rule, 'source.type', 'url') == 'wildcard'); $options['case_sensitive'] = Moto\Util::getFromArrayDeep($rule, 'source.case_sensitive', true); $router->addPath($sourceUrl, $rule, $priority, $options, true); } $requestUrl = Moto\System\Request::getRequestUrl(); $requestUri = Moto\System\Request::getRequestUri(); $queryString = ''; $mode = 'hard'; if ($mode == 'hard') { $requestUrl = $requestUri; } elseif (strpos($requestUri, '?')) { $requestUri = explode('?', $requestUri, 2); $queryString = $requestUri[1]; $queryString = ltrim($queryString, '?'); } $rule = $router->findOne($requestUrl); if ($rule) { $targetUrl = Moto\Util::getFromArrayDeep($rule, 'target.url'); if (Moto\Util::getFromArrayDeep($rule, 'target.type') == 'wildcard') { $match = $rule['_match']; if (empty($match[1])) { $match[1] = ''; } $targetUrl = preg_replace('/[*]+/', '*', $targetUrl); $targetUrl = str_replace('*', $match[1], $targetUrl); } if (Moto\Util::getFromArrayDeep($rule, 'target.query') == 'join' && !empty($queryString)) { $noSlash = ($targetUrl[0] !== '/'); $targetUrl = Moto\Util::extendUrl($targetUrl, array('query' => $queryString), array('query' => 'join')); if ($noSlash) { $targetUrl = ltrim($targetUrl, '/'); } } if (Moto\Util::isInnerUrl($targetUrl)) { $targetUrl = Moto\System::getAbsoluteUrl($targetUrl); } static::redirect($targetUrl, Moto\Util::getFromArrayDeep($rule, 'target.code', 301)); } return; } public static function getApplication($name = null) { if (null == $name) { if (MOTO_SYSTEM_REQUEST_AUTO_REDIRECT) { static::processRedirection(); } $router = static::getSystemRouter(); $url = Moto\System\Request::getRequestUrl(); $name = $router->findOne($url); if (!array_key_exists($name, static::$_applicationsMap)) { $name = static::$_defaultApplicationName; } } if (!array_key_exists($name, static::$_applicationsMap)) { $name = static::$_defaultApplicationName; } $class = static::$_applicationsMap[$name]; if (!class_exists($class)) { $class = static::$_applicationsMap[static::$_defaultApplicationName]; } static::$_applicationInstances[$name] = $class::getInstance(); return static::$_applicationInstances[$name]; } public static function registerApplication($name, $class, $urls = null, $priority = null) { static::$_applicationsMap[$name] = $class; if (!empty($urls)) { static::getSystemRouter()->addPath($urls, $name, $priority); } } public static function isDatabaseConnected() { return (null !== static::$_databaseAdapter); } public static function initDatabaseAdapter() { if (null == static::$_databaseAdapter) { $databaseConfig = Moto\Config::get('database'); $databaseAdapter = new Zend\Db\Adapter\Adapter($databaseConfig); if (!empty($databaseConfig['prefix'])) { Moto\Application\Model\AbstractTable::setTablePrefix($databaseConfig['prefix']); } if (Moto\Config::get('database.profiler')) { $databaseProfiler = new Zend\Db\Adapter\Profiler\Profiler(); $databaseAdapter->setProfiler($databaseProfiler); } static::setDatabaseAdapter($databaseAdapter); Moto\Database\Provider::initEloquent($databaseAdapter, $databaseConfig); static::_callInterceptor('database.initialized'); } return true; } public static function checkAndReconnectDatabaseAdapter() { $databaseAdapter = static::getDatabaseAdapter(); try { $sql = 'SHOW TABLES'; $query = $databaseAdapter->query($sql); $query->execute(); } catch (\Exception $e) { static::$_databaseAdapter = null; static::initDatabaseAdapter(); } } public static function getDatabaseAdapter() { static::initDatabaseAdapter(); return static::$_databaseAdapter; } public static function setDatabaseAdapter($databaseAdapter) { if ($databaseAdapter instanceof Zend\Db\Adapter\Adapter) { Moto\Application\Model\AbstractTable::setDefaultAdapter($databaseAdapter); Moto\Config::set('databaseAdapter', $databaseAdapter); static::$_databaseAdapter = $databaseAdapter; return true; } return false; } public static function getEngineType() { return MOTO_ENGINE; } public static function isInstallEngine() { return (static::getEngineType() === 'install'); } public static function isAdminEngine() { return (static::getEngineType() === 'admin' || static::getEngineType() === 'admin_api'); } public static function isWebsiteEngine() { return (static::getEngineType() === 'website'); } public static function isUpdateEngine() { $type = static::getEngineType(); return ($type === 'update_api' || $type === 'update'); } public static function getResponse() { if (null == static::$_request) { static::$_request = new Moto\System\Response(); } return static::$_request; } public static function redirect($url, $code = 301) { $response = Moto\System::getResponse(); header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); header('Cache-Control: no-store, no-cache, must-revalidate'); header('Cache-Control: post-check=0, pre-check=0', false); header('Pragma: no-cache'); $response->redirect($url, $code); $content = ''; $content .= '<!DOCTYPE HTML>
<html><head><title>' . $code . ' ' . $response->httpCodeToString($code) . '</title></head><body>
<h1>' . $response->httpCodeToString($code) . '</h1>
<p>The document has moved <a href="' . $url . '">here</a>.</p>
</body></html>'; $response->setContent($content); echo $response; exit; } public static function checkDatabase() { try { $databaseAdapter = static::getDatabaseAdapter(); $sql = 'SHOW TABLES'; $query = $databaseAdapter->query($sql); $result = $query->execute(); if (!$result->count()) { throw new \Exception('Database is empty : use dump'); } $onConnected = Moto\Config::get('database.on_connected'); if (is_array($onConnected)) { foreach ($onConnected as $query) { try { $result = $databaseAdapter->query($query)->execute(); if (Moto\System::isDevelopmentStage()) { Moto\System\Log::info(__CLASS__ . '::' . __FUNCTION__, array('query' => $query, 'result' => $result->current())); } } catch (\Exception $e) { if (Moto\System::isDevelopmentStage()) { Moto\System\Log::error(__CLASS__ . '::' . __FUNCTION__ . ' : [' . $e->getCode() . '] ' . $e->getMessage(), array('query' => $query)); } } } } static::_callInterceptor('database.checked'); } catch (\Exception $e) { static::_callInterceptor('database.checking:failed'); echo '<pre>'; echo "Database not connected\n"; if (Moto\System::isDevelopmentStage()) { echo $e->getCode() . ' :: ' . $e->getMessage() . "\n"; echo $e->getTraceAsString(); } exit; } } public static function initAcl() { $permissions = Moto\Website\Settings::get('permissions'); $permissionsNotExists = (!$permissions); if ($permissionsNotExists) { $permissions = array(); } if (empty($permissions['roles'])) { $permissions['roles'] = array( 'guest' => array( 'allow' => false ), 'user' => array( 'parents' => array('guest'), ), 'author' => array( 'parents' => array('user'), ), 'editor' => array( 'parents' => array('author'), ), 'root' => array( 'allow' => true ), 'admin' => array( 'parents' => array('root'), ) ); } if (empty($permissions['resources'])) { $permissions['resources'] = array( 'auth' => array(), 'blocks' => array(), 'users' => array(), 'profile' => array(), 'languages' => array(), 'roles' => array(), 'settings' => array(), 'pages' => array(), 'menus' => array(), 'styles' => array(), 'fileManager' => array(), 'mediaLibrary' => array(), 'content' => array(), 'themes' => array(), 'theme' => array(), 'fonts' => array(), 'extra' => array(), 'updates' => array(), 'presets' => array(), 'widget.mail_chimp' => array(), 'widget.disqus' => array(), 'contentSection' => array(), 'support' => array(), ); } if (empty($permissions['access'])) { $permissions['access'] = array( array('role' => 'guest', 'resource' => array('auth'), 'allow' => true), array('role' => 'guest', 'resource' => 'pages', 'privileges' => array('getPage'), 'allow' => true), array('role' => 'guest', 'resource' => 'pages', 'privileges' => array('getDraftPage'), 'allow' => false), array('role' => 'user', 'resource' => 'profile', 'allow' => true), array('role' => 'editor', 'resource' => 'pages', 'allow' => true), array('role' => 'editor', 'resource' => 'users', 'privileges' => array('get'), 'allow' => true), array('role' => 'editor', 'resource' => array('widget.mail_chimp'), 'privileges' => array('getList'), 'allow' => true), array('role' => 'guest', 'resource' => array('widget.mail_chimp'), 'privileges' => array('subscribe'), 'allow' => true), ); } if ($permissionsNotExists) { } Moto\Application\Acl\Adapter::addRules($permissions); if (Moto\Config::get('isDemoMode')) { $permissions = Moto\Config::get('__permissions__'); if (is_array($permissions)) { Moto\Application\Acl\Adapter::addRules($permissions); } } $acl = Moto\Application\Acl\Adapter::getInstance(); $acl->addResource('sitemap'); $acl->addResource('widgets'); $acl->addResource('content.taxonomy'); } public static function isCurrentUserAllow($resource = null, $action = null) { if (null === $action && null !== $resource) { if (strpos($resource, ':')) { list($resource, $action) = explode(':', $resource, 2); } } $acl = Moto\Application\Acl\Adapter::getInstance(); $user = Moto\Authentication\Service::getUser(); $userRole = (null === $user ? 'guest' : strtolower($user->role_name)); return $acl->isAllowed($userRole, $resource, $action); } protected static function _safeGet($obj, $key, $default = null) { if (is_array($obj)) { return (array_key_exists($key, $obj) ? $obj[$key] : $default); } if (is_object($obj)) { return (isset($obj->{$key}) ? $obj->{$key} : $default); } return $default; } public static function sendCookie($name, $value = null, $options = array()) { $expire = Moto\Util::getFrom($options, 'expire'); $path = Moto\Util::getFrom($options, 'path', '@website'); $path = static::getRelativeUrl($path); if (!empty($path)) { $path = trim($path); $path = '/' . ltrim($path, '/'); $path = rtrim($path, '/') . '/'; } $domain = Moto\Util::getFrom($options, 'domain'); if (!empty($domain) && !preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $domain)) { if ($domain[0] !== '.') { $domain = '.' . $domain; } if (strrpos($domain, '.') < 1) { $domain = null; } } $secure = Moto\Util::getFrom($options, 'secure'); if (!is_bool($secure)) { $secure = Moto\System\Request::isSSL(); } $httponly = Moto\Util::getFrom($options, 'httponly'); if (!is_bool($httponly)) { $httponly = true; } @setcookie($name, $value, $expire, $path, $domain, $secure, $httponly); } public static function _initPlugins() { static::_callInterceptor('plugins.initialing'); static::_loadPlugins('system'); static::_loadPlugins('admin'); static::_loadPlugins('website'); static::_callInterceptor('plugins.initialized'); } public static function _loadPlugins($type) { $items = Moto\Website\Settings::get('plugins', array()); if (is_string($items)) { $items = json_decode($items, true); } $items = !empty($items[$type]) ? $items[$type] : array(); if (!empty($items)) { foreach ($items as $item) { static::_loadPlugin($item); } } } protected static function _loadPlugin($item) { try { if (!is_array($item)) { return false; } if (!empty($item['file'])) { $file = static::getAbsolutePath($item['file']); if (file_exists($file)) { include_once $file; } else { if (static::isDevelopmentStage()) { Moto\System\Log::info(__CLASS__ . '::' . __FUNCTION__, array('file' => $file, 'result' => 'NOT EXISTS')); } } } if (!empty($item['callback']) && is_callable($item['callback'])) { call_user_func($item['callback']); } } catch (\Exception $e) { return false; } return true; } public static function removeInjector($file) { $systemInjectors = Moto\Website\Settings::get('plugins', array()); $founded = false; if (is_array($systemInjectors)) { foreach ($systemInjectors as $type => $injectors) { if (!is_array($injectors)) { continue; } foreach ($injectors as $index => $injector) { if (Moto\Util::getValue($injector, 'file') === $file) { unset($systemInjectors[$type][$index]); $founded = true; } } $systemInjectors[$type] = array_values($systemInjectors[$type]); } if ($founded) { Moto\Website\Settings::add('plugins', $systemInjectors, 'array'); } } return $founded; } public static function registerPlugin($plugin, $type = 'system') { if (empty($plugin['loader'])) { throw new Moto\System\Exception(Moto\System\Exception::ERROR_BAD_REQUEST_MESSAGE, Moto\System\Exception::ERROR_BAD_REQUEST_CODE, array( 'loader' => array('isEmpty'), )); } $loaderFile = Moto\System::getAbsolutePath($plugin['loader']); if (!file_exists($loaderFile)) { throw new Moto\System\Exception(Moto\System\Exception::ERROR_BAD_REQUEST_MESSAGE, Moto\System\Exception::ERROR_BAD_REQUEST_CODE, array( 'loader' => array('fileDoesNotExists'), 'file' => $plugin['loader'], )); } if ($plugin['loader'][0] !== '@') { throw new Moto\System\Exception(Moto\System\Exception::ERROR_BAD_REQUEST_MESSAGE, Moto\System\Exception::ERROR_BAD_REQUEST_CODE, array( 'loader' => array('illegalPath'), )); } $plugins = Moto\Website\Settings::get('plugins', array()); if (is_string($plugins)) { $plugins = json_decode($plugins, true); } if (!is_array($plugins)) { $plugins = array(); } if (empty($plugins[$type])) { $plugins[$type] = array(); } foreach ($plugins[$type] as $item) { if (!empty($item['file']) && $item['file'] === $plugin['loader']) { throw new Moto\System\Exception(Moto\System\Exception::ERROR_BAD_REQUEST_MESSAGE, Moto\System\Exception::ERROR_CONFLICT_CODE, array( 'loader' => array('recordFound'), )); } } $plugins[$type][] = array( 'file' => $plugin['loader'] ); Moto\Website\Settings::add('plugins', $plugins, 'array'); return true; } protected static $_cryptMachineVersion = 1; protected static $_cryptMachineKey; public static function setEncryptMachineKey($key) { if (static::$_cryptMachineKey === null) { static::$_cryptMachineKey = $key; } } public static function encrypt($value) { if (empty($value)) { return $value; } $key = static::$_cryptMachineKey; try { return Moto\System\Encryption::encrypt($value, Moto\System\Encryption::METHOD_SERIALIZE, $key); } catch (\Exception $e) { if (static::isDevelopmentStage()) { Moto\System\Log::warning(__CLASS__ . '::' . __FUNCTION__ . ' => ' . $e->getMessage()); } } return null; } public static function decrypt($value) { if (empty($value)) { return $value; } $key = static::$_cryptMachineKey; try { return Moto\System\Encryption::decrypt($value, Moto\System\Encryption::METHOD_SERIALIZE, $key); } catch (\Exception $e) { if (static::isDevelopmentStage()) { Moto\System\Log::warning(__CLASS__ . '::' . __FUNCTION__ . ' => ' . $e->getMessage()); } } return null; } public static function getUser() { return Moto\Authentication\Service::getUser(); } public static function initBrand() { Moto\System\Brand::init(); } protected static $_dbTablesClass = array( 'contentBlock' => 'Moto\Application\Content\Table\ContentBlocks', 'fonts' => 'Moto\Application\Fonts\FontsTable', 'languages' => 'Moto\Application\Languages\LanguagesTable', 'media_folders' => 'Moto\Application\MediaLibrary\MediaFoldersTable', 'media_items' => 'Moto\Application\MediaLibrary\MediaItemsTable', 'menus' => 'Moto\Application\Menus\MenusTable', 'menu_items' => 'Moto\Application\Menus\MenuItemsTable', 'pages' => 'Moto\Application\Pages\PagesTable', 'presets' => 'Moto\Application\Presets\PresetsTable', 'roles' => 'Moto\Application\Roles\RolesTable', 'settings' => 'Moto\Application\Settings\SettingsTable', 'styles' => 'Moto\Application\Styles\StylesTable', 'users' => 'Moto\Application\Users\UserTable', ); protected static $_dbTables = array(); protected static $_dbRecords = array(); public static function getDbTable($name) { if (array_key_exists($name, static::$_dbTables)) { return static::$_dbTables[$name]; } $class = static::getDbTableClass($name); if (!$class) { return false; } static::$_dbTables[$name] = new $class(); static::$_dbTables[$name]->useResultAsModel(true); return static::$_dbTables[$name]; } public static function getDbTableClass($name) { if (array_key_exists($name, static::$_dbTablesClass)) { return static::$_dbTablesClass[$name]; } return false; } public static function getDbRecord($tableName, $id, $reload = false) { if (!$reload && array_key_exists($tableName . '_' . $id, static::$_dbRecords)) { return static::$_dbRecords[$tableName . '_' . $id]; } $table = static::getDbTable($tableName); if (null == $table) { return false; } static::$_dbRecords[$tableName . '_' . $id] = $table->getById($id); return static::$_dbRecords[$tableName . '_' . $id]; } public static function processWebsiteMaintenancePage() { if (!static::isWebsiteEngine()) { return false; } if (Moto\Website\MaintenanceMode::isEnabled()) { Moto\Website\MaintenanceMode::showPage(); exit; } } public static function getDatabaseInformation() { $result = array(); try { $resource = static::getDatabaseAdapter()->getDriver()->getConnection()->getResource(); } catch (\Exception $e) { return false; } try { $result['clientVersion'] = $resource->getAttribute(\PDO::ATTR_CLIENT_VERSION); } catch (\Exception $e) { } try { $result['driverName'] = $resource->getAttribute(\PDO::ATTR_DRIVER_NAME); } catch (\Exception $e) { } try { $result['serverInfoFull'] = $resource->getAttribute(\PDO::ATTR_SERVER_INFO); } catch (\Exception $e) { } try { $result['serverVersionFull'] = trim($resource->getAttribute(\PDO::ATTR_SERVER_VERSION)); if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+)/', $result['serverVersionFull'], $match)) { $result['serverVersion'] = $match[1]; } } catch (\Exception $e) { } return $result; } } 