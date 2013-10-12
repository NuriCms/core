<?php
/**
 * @class  installController
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief install module of the Controller class
 */
class installController extends install
{
	var $db_tmp_config_file = '';
	var $etc_tmp_config_file = '';

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Error occurs if already installed
		if(Context::isInstalled())
		{
			return new Object(-1, 'msg_already_installed');
		}

		$this->db_tmp_config_file = _XE_PATH_.'files/config/tmpDB.config.php';
		$this->etc_tmp_config_file = _XE_PATH_.'files/config/tmpEtc.config.php';
	}

	/**
	 * @brief cubrid db setting wrapper, becase Server Side Validator...
	 * Server Side Validatro can use only one proc, one ruleset
	 */
	function procCubridDBSetting()
	{
		return $this->_procDBSetting();
	}

	/**
	 * @brief firebird db setting wrapper, becase Server Side Validator...
	 * Server Side Validatro can use only one proc, one ruleset
	 */
	function procFirebirdDBSetting()
	{
		return $this->_procDBSetting();
	}

	/**
	 * @brief mssql db setting wrapper, becase Server Side Validator...
	 * Server Side Validatro can use only one proc, one ruleset
	 */
	function procMssqlDBSetting()
	{
		return $this->_procDBSetting();
	}

	/**
	 * @brief mysql db setting wrapper, becase Server Side Validator...
	 * Server Side Validatro can use only one proc, one ruleset
	 */
	function procMysqlDBSetting()
	{
		return $this->_procDBSetting();
	}

	/**
	 * @brief postgresql db setting wrapper, becase Server Side Validator...
	 * Server Side Validatro can use only one proc, one ruleset
	 */
	function procPostgresqlDBSetting()
	{
		return $this->_procDBSetting();
	}

	/**
	 * @brief sqlite db setting wrapper, becase Server Side Validator...
	 * Server Side Validatro can use only one proc, one ruleset
	 */
	function procSqliteDBSetting()
	{
		return $this->_procDBSetting();
	}

	/**
	 * @brief division install step... DB Config temp file create
	 */
	function _procDBSetting()
	{
		// Get DB-related variables
		$con_string = Context::gets('db_type','db_port','db_hostname','db_userid','db_password','db_database','db_table_prefix');

		$db_info->master_db = get_object_vars($con_string);
		$db_info->slave_db[] = get_object_vars($con_string);

		if(!$db_info->default_url) $db_info->default_url = Context::getRequestUri();
		$db_info->lang_type = Context::getLangType();
		$db_info->use_mobile_view = 'Y';

		// Set DB type and information
		Context::setDBInfo($db_info);
		// Create DB Instance
		$oDB = &DB::getInstance();
		// Check if available to connect to the DB
		$output = $oDB->getError();
		if(!$output->toBool()) return $output;
		if(!$oDB->isConnected()) return $oDB->getError();
		// When installing firebird DB, transaction will not be used
		if($db_info->db_type != "firebird") $oDB->begin();

		if($db_info->db_type != "firebird") $oDB->commit();
		// Create a db temp config file
		if(!$this->makeDBConfigFile()) return new Object(-1, 'msg_install_failed');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'act', 'dispInstallConfigForm');
			header('location:'.$returnUrl);
			return;
		}
	}

	/**
	 * @brief division install step... rewrite, time_zone Config temp file create
	 */
	function procConfigSetting()
	{
		// Get variables
		$config_info = Context::gets('use_rewrite','time_zone');
		if($config_info->use_rewrite!='Y') $config_info->use_rewrite = 'N';

		// Create a db temp config file
		if(!$this->makeEtcConfigFile($config_info)) return new Object(-1, 'msg_install_failed');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'act', 'dispInstallManagerForm');
			header('location:'.$returnUrl);
			return;
		}
	}

	/**
	 * @brief Install with received information
	 */
	function procInstall()
	{
		// Check if it is already installed
		if(Context::isInstalled()) return new Object(-1, 'msg_already_installed');
		// Assign a temporary administrator when installing
		$logged_info->is_admin = 'Y';
		Context::set('logged_info', $logged_info);

		// check install config
		if(Context::get('install_config'))
		{
			$db_info = $this->_makeDbInfoByInstallConfig();
		}
		// install by default XE UI
		else
		{
			include $this->db_tmp_config_file;
			include $this->etc_tmp_config_file;
		}

		// Set DB type and information
		Context::setDBInfo($db_info);
		// Create DB Instance
		$oDB = &DB::getInstance();
		// Check if available to connect to the DB
		if(!$oDB->isConnected()) return $oDB->getError();
		// When installing firebire DB, transaction will not be used
		if($db_info->db_type != "firebird") $oDB->begin();
		// Install all the modules
		$this->installDownloadedModule();

		if($db_info->db_type != "firebird") $oDB->commit();
		// Create a config file
		if(!$this->makeConfigFile()) return new Object(-1, 'msg_install_failed');

		// load script
		$scripts = FileHandler::readDir('./modules/install/script','/(\.php)$/');
		if(count($scripts)>0)
		{
			sort($scripts);
			foreach($scripts as $script)
			{
				$output = include(FileHandler::getRealPath('./modules/install/script/'.$script));
			}
		}

		// save selected lang info
		$oInstallAdminController = &getAdminController('install');
		$oInstallAdminController->saveLangSelected(array(Context::getLangType()));

		// Display a message that installation is completed
		$this->setMessage('msg_install_completed');

		unset($_SESSION['use_rewrite']);

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('');
			header('location:'.$returnUrl);
			return;
		}
	}

	/**
	 * @brief Make DB Information by Install Config
	 */
	function _makeDbInfoByInstallConfig()
	{
		$db_info->master_db['db_type'] = Context::get('db_type');
		$db_info->master_db['db_port'] = Context::get('db_port');
		$db_info->master_db['db_hostname'] = Context::get('db_hostname');
		$db_info->master_db['db_userid'] = Context::get('db_userid');
		$db_info->master_db['db_password'] = Context::get('db_password');
		$db_info->master_db['db_database'] = Context::get('db_database');
		$db_info->master_db['db_table_prefix'] = Context::get('db_table_prefix');
		$db_info->slave_db = array($db_info->master_db);
		$db_info->default_url = Context::getRequestUri();
		$db_info->lang_type = Context::getLangType();
		$db_info->use_rewrite = Context::get('use_rewrite');
		$db_info->time_zone = Context::get('time_zone');
		return $db_info;
	}

	/**
	 * @brief Set FTP Information
	 */
	function procInstallFTP()
	{
		if(Context::isInstalled()) return new Object(-1, 'msg_already_installed');
		$ftp_info = Context::gets('ftp_host', 'ftp_user','ftp_password','ftp_port','ftp_root_path');
		$ftp_info->ftp_port = (int)$ftp_info->ftp_port;
		if(!$ftp_info->ftp_port) $ftp_info->ftp_port = 21;
		if(!$ftp_info->ftp_host) $ftp_info->ftp_host = '127.0.0.1';
		if(!$ftp_info->ftp_root_path) $ftp_info->ftp_root_path = '/';

		$buff = '<?php if(!defined("__XE__")) exit();'."\n";
		foreach($ftp_info as $key => $val)
		{
			$buff .= sprintf("\$ftp_info->%s = '%s';\n", $key, str_replace("'","\\'",$val));
		}
		$buff .= "?".">";
		// If safe_mode
		if(ini_get('safe_mode'))
		{
			if(!$ftp_info->ftp_user || !$ftp_info->ftp_password) return new Object(-1,'msg_safe_mode_ftp_needed');

			require_once(_XE_PATH_.'libs/ftp.class.php');
			$oFtp = new ftp();
			if(!$oFtp->ftp_connect($ftp_info->ftp_host, $ftp_info->ftp_port)) return new Object(-1, sprintf(Context::getLang('msg_ftp_not_connected'), $ftp_info->ftp_host));

			if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password))
			{
				$oFtp->ftp_quit();
				return new Object(-1,'msg_ftp_invalid_auth_info');
			}

			if(!is_dir(_XE_PATH_.'files') && !$oFtp->ftp_mkdir($ftp_info->ftp_root_path.'files'))
			{
				$oFtp->ftp_quit();
				return new Object(-1,'msg_ftp_mkdir_fail');
			}

			if(!$oFtp->ftp_site("CHMOD 777 ".$ftp_info->ftp_root_path.'files'))
			{
				$oFtp->ftp_quit();
				return new Object(-1,'msg_ftp_chmod_fail');
			}

			if(!is_dir(_XE_PATH_.'files/config') && !$oFtp->ftp_mkdir($ftp_info->ftp_root_path.'files/config'))
			{
				$oFtp->ftp_quit();
				return new Object(-1,'msg_ftp_mkdir_fail');
			}

			if(!$oFtp->ftp_site("CHMOD 777 ".$ftp_info->ftp_root_path.'files/config'))
			{
				$oFtp->ftp_quit();
				return new Object(-1,'msg_ftp_chmod_fail');
			}

			$oFtp->ftp_quit();
		}

		$config_file = Context::getFTPConfigFile();
		FileHandler::WriteFile($config_file, $buff);
	}

	function procInstallCheckFtp()
	{
		$ftp_info = Context::gets('ftp_user','ftp_password','ftp_port','sftp');
		$ftp_info->ftp_port = (int)$ftp_info->ftp_port;
		if(!$ftp_info->ftp_port) $ftp_info->ftp_port = 21;
		if(!$ftp_info->sftp) $ftp_info->sftp = 'N';

		if(!$ftp_info->ftp_user || !$ftp_info->ftp_password) return new Object(-1,'msg_safe_mode_ftp_needed');

		if($ftp_info->sftp == 'Y')
		{
			$connection = ssh2_connect('localhost', $ftp_info->ftp_port);
			if(!ssh2_auth_password($connection, $ftp_info->ftp_user, $ftp_info->ftp_password))
			{
				return new Object(-1,'msg_ftp_invalid_auth_info');
			}
		}
		else
		{
			require_once(_XE_PATH_.'libs/ftp.class.php');
			$oFtp = new ftp();
			if(!$oFtp->ftp_connect('localhost', $ftp_info->ftp_port)) return new Object(-1, sprintf(Context::getLang('msg_ftp_not_connected'), 'localhost'));

			if(!$oFtp->ftp_login($ftp_info->ftp_user, $ftp_info->ftp_password))
			{
				$oFtp->ftp_quit();
				return new Object(-1,'msg_ftp_invalid_auth_info');
			}

			$oFtp->ftp_quit();
		}

		$this->setMessage('msg_ftp_connect_success');
	}

	/**
	 * @brief Result returned after checking the installation environment
	 */
	function checkInstallEnv()
	{
		// Check each item
		$checklist = array();
		// 0. check your version of php (5.2.2 is not supported)
		if(phpversion()=='5.2.2') $checklist['php_version'] = false;
		else $checklist['php_version'] = true;
		// 1. Check permission
		if(is_writable('./')||is_writable('./files')) $checklist['permission'] = true;
		else $checklist['permission'] = false;
		// 2. Check if xml_parser_create exists
		if(function_exists('xml_parser_create')) $checklist['xml'] = true;
		else $checklist['xml'] = false;
		// 3. Check if ini_get (session.auto_start) == 1
		if(ini_get(session.auto_start)!=1) $checklist['session'] = true;
		else $checklist['session'] = false;
		// 4. Check if iconv exists
		if(function_exists('iconv')) $checklist['iconv'] = true;
		else $checklist['iconv'] = false;
		// 5. Check gd(imagecreatefromgif function)
		if(function_exists('imagecreatefromgif')) $checklist['gd'] = true;
		else $checklist['gd'] = false;
		// 6. Check DB
		if(DB::getEnableList()) $checklist['db'] = true;
		else $checklist['db'] = false;

		if(!$checklist['php_version'] || !$checklist['permission'] || !$checklist['xml'] || !$checklist['session'] || !$checklist['db']) $install_enable = false;
		else $install_enable = true;

		// Save the checked result to the Context
		Context::set('checklist', $checklist);
		Context::set('install_enable', $install_enable);
		Context::set('phpversion', phpversion());

		return $install_enable;
	}

	/**
	 * @brief Create files and subdirectories
	 * Local evironment setting before installation by using DB information
	 */
	function makeDefaultDirectory()
	{
		$directory_list = array(
			'./files/config',
			'./files/cache/queries',
			'./files/cache/js_filter_compiled',
			'./files/cache/template_compiled',
		);

		foreach($directory_list as $dir)
		{
			FileHandler::makeDir($dir);
		}
	}

	/**
	 * @brief Install all the modules
	 *
	 * Create a table by using schema xml file in the shcema directory of each module
	 */
	function installDownloadedModule()
	{
		$oModuleModel = &getModel('module');
		// Create a table ny finding schemas/*.xml file in each module
		$module_list = FileHandler::readDir('./modules/', NULL, false, true);
		foreach($module_list as $module_path)
		{
			// Get module name
			$tmp_arr = explode('/',$module_path);
			$module = $tmp_arr[count($tmp_arr)-1];

			$xml_info = $oModuleModel->getModuleInfoXml($module);
			if(!$xml_info) continue;
			$modules[$xml_info->category][] = $module;
		}
		// Install "module" module in advance
		$this->installModule('module','./modules/module');
		$oModule = &getClass('module');
		if($oModule->checkUpdate()) $oModule->moduleUpdate();
		// Determine the order of module installation depending on category
		$install_step = array('system','content','member');
		// Install all the remaining modules
		foreach($install_step as $category)
		{
			if(count($modules[$category]))
			{
				foreach($modules[$category] as $module)
				{
					if($module == 'module') continue;
					$this->installModule($module, sprintf('./modules/%s', $module));

					$oModule = &getClass($module);
					if(is_object($oModule) && method_exists($oModule, 'checkUpdate'))
					{
						if($oModule->checkUpdate()) $oModule->moduleUpdate();
					}
				}
				unset($modules[$category]);
			}
		}
		// Install all the remaining modules
		if(count($modules))
		{
			foreach($modules as $category => $module_list)
			{
				if(count($module_list))
				{
					foreach($module_list as $module)
					{
						if($module == 'module') continue;
						$this->installModule($module, sprintf('./modules/%s', $module));

						$oModule = &getClass($module);
						if($oModule && method_exists($oModule, 'checkUpdate') && method_exists($oModule, 'moduleUpdate'))
						{
							if($oModule->checkUpdate()) $oModule->moduleUpdate();
						}
					}
				}
			}
		}

		return new Object();
	}

	/**
	 * @brief Install an each module
	 */
	function installModule($module, $module_path)
	{
		// create db instance
		$oDB = &DB::getInstance();
		// Create a table if the schema xml exists in the "schemas" directory of the module
		$schema_dir = sprintf('%s/schemas/', $module_path);
		$schema_files = FileHandler::readDir($schema_dir, NULL, false, true);

		$file_cnt = count($schema_files);
		for($i=0;$i<$file_cnt;$i++)
		{
			$file = trim($schema_files[$i]);
			if(!$file || substr($file,-4)!='.xml') continue;
			$output = $oDB->createTableByXmlFile($file);
		}
		// Create a table and module instance and then execute install() method
		unset($oModule);
		$oModule = &getClass($module);
		if(method_exists($oModule, 'moduleInstall')) $oModule->moduleInstall();
		return new Object();
	}

	function _getDbConnText($key, $val, $with_array = false){
		$buff = '';
		if($with_array)
			$buff .= "\$db_info->$key = array(";
		else
			$buff .= "\$db_info->$key = ";
		if(!$with_array) $val = array($val);
		foreach($val as $con_string)
		{
			$buff .= 'array(';
			foreach($con_string as $k => $v)
			{
				if(in_array($k, array('resource', 'is_connected'))) continue;
				if($k == 'db_table_prefix' && !empty($v))
				{
					if(substr($v,-1)!='_') $v .= '_';
				}
				$buff .= "'$k' => '$v',";
			}
			$buff = substr($buff, 0, -1);
			$buff .= '),';
		}
		$buff = substr($buff, 0, -1);
		if($with_array)
			$buff .= ');' . PHP_EOL;
		else
			$buff .= ';' . PHP_EOL;
		return $buff;
	}

	function _getDBConfigFileContents($db_info)
	{
		$buff = '<?php if(!defined("__XE__")) exit();'."\n";
		$db_info = get_object_vars($db_info);
		foreach($db_info as $key => $val)
		{
			if($key == 'master_db')
			{
				$tmpValue = $this->_getDbConnText($key, $val);
			}
			else if($key == 'slave_db')
			{
				$tmpValue = $this->_getDbConnText($key, $val, true);
			}
			else
			{
				if($key == 'default_url')
				{
					$tmpValue = sprintf("\$db_info->%s = '%s';" . PHP_EOL, $key, addslashes($val));
				}
				else
				{
					$tmpValue = sprintf("\$db_info->%s = '%s';" . PHP_EOL, $key, str_replace("'","\\'",$val));
				}
			}

			if(preg_match('/(<\?|<\?php|\?>|fputs|fopen|fwrite|fgets|fread|\/\*|\*\/|chr\()/xsm', preg_replace('/\s/', '', $tmpValue)))
			{
				throw new Exception('msg_invalid_request');
			}

			$buff .= $tmpValue;
		}
		$buff .= "?>";
		return $buff;
	}

	/**
	 * @brief Create DB temp config file
	 * Create the config file when all settings are completed
	 */
	function makeDBConfigFile()
	{
		$db_tmp_config_file = $this->db_tmp_config_file;

		$db_info = Context::getDbInfo();
		if(!$db_info) return;

		$buff = $this->_getDBConfigFileContents($db_info);

		FileHandler::writeFile($db_tmp_config_file, $buff);

		if(@file_exists($db_tmp_config_file)) return true;
		return false;
	}

	/**
	 * @brief Create etc config file
	 * Create the config file when all settings are completed
	 */
	function makeEtcConfigFile($config_info)
	{
		$etc_tmp_config_file = $this->etc_tmp_config_file;

		$buff = '<?php if(!defined("__XE__")) exit();'."\n";
		foreach($config_info as $key => $val)
		{
			$buff .= sprintf("\$db_info->%s = '%s';\n", $key, str_replace("'","\\'",$val));
		}
		$buff .= "?>";

		FileHandler::writeFile($etc_tmp_config_file, $buff);

		if(@file_exists($etc_tmp_config_file)) return true;
		return false;
	}

	/**
	 * @brief Create config file
	 * Create the config file when all settings are completed
	 */
	function makeConfigFile()
	{
		try {
			$config_file = Context::getConfigFile();
			//if(file_exists($config_file)) return;

			$db_info = Context::getDbInfo();
			if(!$db_info) return;

			$buff = $this->_getDBConfigFileContents($db_info);

			FileHandler::writeFile($config_file, $buff);

			if(@file_exists($config_file))
			{
				FileHandler::removeFile($this->db_tmp_config_file);
				FileHandler::removeFile($this->etc_tmp_config_file);
				return true;
			}
			return false;
		} catch (Exception $e) {
			return false;
		}
	}

	function installByConfig($install_config_file)
	{
		include $install_config_file;
		if(!is_array($auto_config)) return false;

		$auto_config['module'] = 'install';
		$auto_config['act'] = 'procInstall';

		$fstr = "<%s><![CDATA[%s]]></%s>\r\n";
		$fheader = "POST %s HTTP/1.1\r\nHost: %s\r\nContent-Type: application/xml\r\nContent-Length: %s\r\n\r\n%s\r\n";
		$body = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\r\n<methodCall>\r\n<params>\r\n";
		foreach($auto_config as $k => $v)
		{
			if(!in_array($k,array('host','port','path'))) $body .= sprintf($fstr,$k,$v,$k);
		}
		$body .= "</params>\r\n</methodCall>";

		$header = sprintf($fheader,$auto_config['path'],$auto_config['host'],strlen($body),$body);
		$fp = @fsockopen($auto_config['host'], $auto_config['port'], $errno, $errstr, 5);
		if($fp)
		{
			fputs($fp, $header);
			while(!feof($fp))
			{
				$line = trim(fgets($fp, 4096));
				if(preg_match("/^<error>/i",$line))
				{
					fclose($fp);
					return false;
				}
			}
			fclose($fp);
		}
		return true;

	}
}
/* End of file install.controller.php */
/* Location: ./modules/install/install.controller.php */
