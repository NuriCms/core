<?php

/**
 * set the include of the class file and other environment configurations
 *
 * @file   config/config.inc.php
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 */
@error_reporting(E_ALL ^ E_STRICT ^ E_NOTICE);

if(!defined('__XE__'))
{
	exit();
}

/**
 * @deprecated __ZBXE__ will be removed. Use __XE__ instead.
 */
define('__ZBXE__', __XE__);

/**
 * Display XE's full version.
 * The version should be revised when releasing even if no change is made.

 * XE core's version name is designated development stage. Basically consist of 4 numbers. For example X.X.X.X.

 * First position number means 'major' update.
 * Second position number means 'minor' update.
 * Third position number '0, 1, 2, 3' means 'status' update.
 * Forth position number means 'patch' update.

 * Third position number for status display is consist of 0~3 numbers.
 * '0' means 'alpha' status.
 * '1' means 'beta' status.
 * '2' means 'release candidate' status.
 * '3' means 'final' status.
 */
define('__XE_VERSION__', '1.7.3.5');

/**
 * @deprecated __ZBXE_VERSION__ will be removed. Use __XE_VERSION__ instead.
 */
define('__ZBXE_VERSION__', __XE_VERSION__);

/**
 * The base path to where you installed zbXE Wanted
 */
define('_XE_PATH_', str_replace('config/config.inc.php', '', str_replace('\\', '/', __FILE__)));


// Set can use other method instead cookie to store session id(for file upload)
ini_set('session.use_only_cookies', 0);


if(file_exists(_XE_PATH_ . 'config/package.inc.php'))
{
	require _XE_PATH_ . 'config/package.inc.php';
}
else
{
	/**
	 * Package type
	 */
	define('_XE_PACKAGE_', 'XE');

	/**
	 * Location
	 */
	define('_XE_LOCATION_', 'en');

	/**
	 * Location site
	 */
	define('_XE_LOCATION_SITE_', 'http://www.xpressengine.org/');

	/**
	 * Download server
	 */
	define('_XE_DOWNLOAD_SERVER_', 'http://en.download.xpressengine.org/');
}

/*
 * user configuration files which override the default settings
 * save the following information into config/config.user.inc.php
 * <?php
 * define('__DEBUG__', 0);
 * define('__DEBUG_OUTPUT__', 0);
 * define('__DEBUG_PROTECT__', 1);
 * define('__DEBUG_PROTECT_IP__', '127.0.0.1');
 * define('__DEBUG_DB_OUTPUT__', 0);
 * define('__LOG_SLOW_QUERY__', 0);
 * define('__OB_GZHANDLER_ENABLE__', 1);
 * define('__ENABLE_PHPUNIT_TEST__', 0);
 * define('__PROXY_SERVER__', 'http://domain:port/path');
 * define('__XE_CDN_PREFIX__', 'http://yourCdnDomain.com/path/');
 * define('__XE_CDN_VERSION__', 'yourCdnVersion');
 */
if(file_exists(_XE_PATH_ . 'config/config.user.inc.php'))
{
	require _XE_PATH_ . 'config/config.user.inc.php';
}

if(!defined('__DEBUG__'))
{
	/**
	 * output debug message(bit value)
	 *
	 * <pre>
	 * 0: generate debug messages/not display
	 * 1: display messages through debugPrint() function
	 * 2: output execute time, Request/Response info
	 * 4: output DB query history
	 * </pre>
	 */
	define('__DEBUG__', 0);
}

if(!defined('__DEBUG_OUTPUT__'))
{
	/**
	 * output location of debug message
	 *
	 * <pre>
	 * 0: connect to the files/_debug_message.php and output
	 * 1: HTML output as a comment on the bottom (when response method is the HTML)
	 * 2: Firebug console output (PHP 4 & 5. Firebug/FirePHP plug-in required)
	 * </pre>
	 */
	define('__DEBUG_OUTPUT__', 0);
}

if(!defined('__DEBUG_PROTECT__'))
{
	/**
	 * output comments of the firePHP console and browser
	 *
	 * <pre>
	 * 0: No limit (not recommended)
	 * 1: Allow only specified IP addresses
	 * </pre>
	 */
	define('__DEBUG_PROTECT__', 1);
}

if(!defined('__DEBUG_PROTECT_IP__'))
{
	/**
	 * Set a ip address to allow debug
	 */
	define('__DEBUG_PROTECT_IP__', '127.0.0.1');
}

if(!defined('__DEBUG_DB_OUTPUT__'))
{
	/**
	 * DB error message definition
	 *
	 * <pre>
	 * 0: No output
	 * 1: files/_debug_db_query.php connected to the output
	 * </pre>
	 */
	define('__DEBUG_DB_OUTPUT__', 0);
}

if(!defined('__LOG_SLOW_QUERY__'))
{
	/**
	 * Query log for only timeout query among DB queries
	 *
	 * <pre>
	 * 0: Do not leave a log
	 * = 0: leave a log when the slow query takes over specified seconds
	 * Log file is saved as ./files/_db_slow_query.php file
	 * </pre>
	 */
	define('__LOG_SLOW_QUERY__', 0);
}

if(!defined('__DEBUG_QUERY__'))
{
	/**
	 * Leave DB query information
	 *
	 * <pre>
	 * 0: Do not add information to the query
	 * 1: Comment the XML Query ID
	 * </pre>
	 */
	define('__DEBUG_QUERY__', 0);
}

if(!defined('__OB_GZHANDLER_ENABLE__'))
{
	/**
	 * option to enable/disable a compression feature using ob_gzhandler
	 *
	 * <pre>
	 * 0: Not used
	 * 1: Enabled
	 * Only particular servers may have a problem in IE browser when sending a compression
	 * </pre>
	 */
	define('__OB_GZHANDLER_ENABLE__', 1);
}

if(!defined('__ENABLE_PHPUNIT_TEST__'))
{
	/**
	 * decide to use/not use the php unit test (Path/tests/index.php)
	 *
	 * <pre>
	 * 0: Not used
	 * 1: Enabled
	 * </pre>
	 */
	define('__ENABLE_PHPUNIT_TEST__', 0);
}

if(!defined('__PROXY_SERVER__'))
{
	/**
	 * __PROXY_SERVER__ has server information to request to the external through the target server
	 * FileHandler:: getRemoteResource uses the constant
	 */
	define('__PROXY_SERVER__', NULL);
}

if(!defined('__XE_CDN_PREFIX__'))
{
	/**
	 * CDN prefix
	 */
	define('__XE_CDN_PREFIX__', 'http://static.xpressengine.com/core/');
}

if(!defined('__XE_CDN_VERSION__'))
{
	/**
	 * CDN version
	 */
	define('__XE_CDN_VERSION__', '%__XE_CDN_VERSION__%');
}

// Require specific files when using Firebug console output
if((__DEBUG_OUTPUT__ == 2) && version_compare(PHP_VERSION, '6.0.0') === -1)
{
	require _XE_PATH_ . 'libs/FirePHPCore/FirePHP.class.php';
}

// Set Timezone as server time
if(version_compare(PHP_VERSION, '5.3.0') >= 0)
{
	date_default_timezone_set(@date_default_timezone_get());
}

if(!defined('__XE_LOADED_CLASS__'))
{
	// Require a function-defined-file for simple use
	require(_XE_PATH_ . 'config/func.inc.php');

	if(__DEBUG__)
		define('__StartTime__', getMicroTime());

	// include the class files
	//TODO When _autoload() can be used for PHP5 based applications, it will be removed.
	if(__DEBUG__)
		define('__ClassLoadStartTime__', getMicroTime());
	require(_XE_PATH_ . 'classes/object/Object.class.php');
	require(_XE_PATH_ . 'classes/extravar/Extravar.class.php');
	require(_XE_PATH_ . 'classes/handler/Handler.class.php');
	require(_XE_PATH_ . 'classes/xml/XmlParser.class.php');
	require(_XE_PATH_ . 'classes/xml/XmlGenerator.class.php');
	require(_XE_PATH_ . 'classes/xml/XmlJsFilter.class.php');
	require(_XE_PATH_ . 'classes/xml/XmlLangParser.class.php');
	require(_XE_PATH_ . 'classes/cache/CacheHandler.class.php');
	require(_XE_PATH_ . 'classes/context/Context.class.php');
	require(_XE_PATH_ . 'classes/db/DB.class.php');
	require(_XE_PATH_ . 'classes/file/FileHandler.class.php');
	require(_XE_PATH_ . 'classes/widget/WidgetHandler.class.php');
	require(_XE_PATH_ . 'classes/editor/EditorHandler.class.php');
	require(_XE_PATH_ . 'classes/module/ModuleObject.class.php');
	require(_XE_PATH_ . 'classes/module/ModuleHandler.class.php');
	require(_XE_PATH_ . 'classes/display/DisplayHandler.class.php');
	require(_XE_PATH_ . 'classes/template/TemplateHandler.class.php');
	require(_XE_PATH_ . 'classes/mail/Mail.class.php');
	require(_XE_PATH_ . 'classes/page/PageHandler.class.php');
	require(_XE_PATH_ . 'classes/mobile/Mobile.class.php');
	require(_XE_PATH_ . 'classes/validator/Validator.class.php');
	require(_XE_PATH_ . 'classes/frontendfile/FrontEndFileHandler.class.php');
	require(_XE_PATH_ . 'classes/security/Security.class.php');
	if(__DEBUG__)
		$GLOBALS['__elapsed_class_load__'] = getMicroTime() - __ClassLoadStartTime__;
}
/* End of file config.inc.php */
/* Location: ./config/config.inc.php */
