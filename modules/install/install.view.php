<?php
/**
 * @class  installView
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief View class of install module
 */
class installView extends install
{
	var $install_enable = false;

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Set browser title
		Context::setBrowserTitle(Context::getLang('introduce_title'));
		// Specify the template path
		$this->setTemplatePath($this->module_path.'tpl');
		// Error occurs if already installed
		if(Context::isInstalled()) return $this->stop('msg_already_installed');
		// Install a controller
		$oInstallController = &getController('install');
		$this->install_enable = $oInstallController->checkInstallEnv();
		// If the environment is installable, execute installController::makeDefaultDirectory()
		if($this->install_enable) $oInstallController->makeDefaultDirectory();
	}

	/**
	 * @brief Display license messages
	 */
	function dispInstallIntroduce()
	{
		$install_config_file = FileHandler::getRealPath('./config/install.config.php');
		if(file_exists($install_config_file))
		{
			include $install_config_file;
			if(is_array($install_config))
			{
				foreach($install_config as $k => $v)
				{
					$v = ($k == 'db_table_prefix') ? $v.'_' : $v;
					Context::set($k,$v,true);
				}
				unset($GLOBALS['__DB__']);
				Context::set('install_config', true, true);
				$oInstallController = &getController('install');
				$output = $oInstallController->procInstall();
				if (!$output->toBool()) return $output;
				header("location: ./");
				Context::close();
				exit;
			}
		}

		Context::set('l', Context::getLangType());
		$this->setTemplateFile('introduce');
	}

	/**
	 * @brief Display messages about installation environment
	 */
	function dispInstallCheckEnv()
	{
		$useRewrite = $this->useRewriteModule() ? 'Y' : 'N';
		$_SESSION['use_rewrite'] = $useRewrite;
		Context::set('use_rewrite', $useRewrite);

		$this->setTemplateFile('check_env');
	}

	/**
	 * @brief Choose a DB
	 */
	function dispInstallSelectDB()
	{
		// Display check_env if it is not installable
		if(!$this->install_enable) return $this->dispInstallCheckEnv();
		// Enter ftp information
		if(ini_get('safe_mode') && !Context::isFTPRegisted())
		{
			Context::set('progressMenu', '3');
			$this->setTemplateFile('ftp');
		}
		else
		{
			$defaultDatabase = 'mysqli';
			$disableList = DB::getDisableList();
			if(is_array($disableList))
			{
				foreach($disableList AS $key=>$value)
				{
					if($value->db_type == $defaultDatabase)
					{
						$defaultDatabase = 'mysql';
						break;
					}
				}
			}
			Context::set('defaultDatabase', $defaultDatabase);

			Context::set('progressMenu', '4');
			$this->setTemplateFile('select_db');
		}
	}

	/**
	 * @brief Display a screen to enter DB and administrator's information
	 */
	function dispInstallDBForm()
	{
		// Display check_env if not installable
		if(!$this->install_enable) return $this->dispInstallCheckEnv();
		// Return to the start-up screen if db_type is not specified
		if(!Context::get('db_type')) return $this->dispInstallSelectDB();

		// Output the file, disp_db_info_form.html
		$tpl_filename = sprintf('form.%s', Context::get('db_type'));

		$title = sprintf(Context::getLang('input_dbinfo_by_dbtype'), Context::get('db_type'));
		Context::set('title', $title);
		$this->setTemplateFile($tpl_filename);
	}

	/**
	 * @brief Display a screen to enter DB and administrator's information
	 */
	function dispInstallConfigForm()
	{
		// Display check_env if not installable
		if(!$this->install_enable) return $this->dispInstallCheckEnv();

		include _XE_PATH_.'files/config/tmpDB.config.php';

		Context::set('use_rewrite', $_SESSION['use_rewrite']);
		Context::set('time_zone', $GLOBALS['time_zone']);
		Context::set('db_type', $db_info->db_type);
		$this->setTemplateFile('config_form');
	}

	function useRewriteModule()
	{
		if(function_exists('apache_get_modules') && in_array('mod_rewrite',apache_get_modules()))
		{
			return true;
		}

		require_once(_XE_PATH_.'classes/httprequest/XEHttpRequest.class.php');
		$httpRequest = new XEHttpRequest($_SERVER['HTTP_HOST'], $_SERVER['SERVER_PORT']);
		$xeInstallPath = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], 'index.php', 1));
		$output = $httpRequest->send($xeInstallPath.'modules/install/conf/info.xml');

		return (strpos($output->body, '<?xml') !== 0);
	}

	/**
	 * @brief Display a screen to enter DB and administrator's information
	 */
	function dispInstallManagerForm()
	{
		// Display check_env if not installable
		if(!$this->install_enable)
		{
			return $this->dispInstallCheckEnv();
		}

		$this->setTemplateFile('admin_form');
	}
}
/* End of file install.view.php */
/* Location: ./modules/install/install.view.php */
