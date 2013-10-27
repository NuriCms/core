<?php

/**
 * adminAdminView class
 * Admin view class of admin module
 *
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @package /modules/admin
 * @version 0.1
 */
class adminAdminView extends admin
{

	/**
	 * layout list
	 * @var array
	 */
	var $layout_list;

	/**
	 * easy install check file
	 * @var array
	 */
	var $easyinstallCheckFile = './files/env/easyinstall_last';

	/**
	 * Initilization
	 * @return void
	 */
	function init()
	{
		// change into administration layout
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setLayoutPath($this->getTemplatePath());
		$this->setLayoutFile('layout.html');

		$this->makeGnbUrl();

		// Retrieve the list of installed modules

		$db_info = Context::getDBInfo();

		Context::set('time_zone_list', $GLOBALS['time_zone']);
		Context::set('time_zone', $GLOBALS['_time_zone']);
		Context::set('use_rewrite', $db_info->use_rewrite == 'Y' ? 'Y' : 'N');
		Context::set('use_sso', $db_info->use_sso == 'Y' ? 'Y' : 'N');
		Context::set('use_html5', $db_info->use_html5 == 'Y' ? 'Y' : 'N');
		Context::set('use_spaceremover', $db_info->use_spaceremover ? $db_info->use_spaceremover : 'Y'); //not use
		Context::set('qmail_compatibility', $db_info->qmail_compatibility == 'Y' ? 'Y' : 'N');
		Context::set('use_db_session', $db_info->use_db_session == 'N' ? 'N' : 'Y');
		Context::set('use_mobile_view', $db_info->use_mobile_view == 'Y' ? 'Y' : 'N');
		Context::set('use_ssl', $db_info->use_ssl ? $db_info->use_ssl : "none");
		Context::set('use_cdn', $db_info->use_cdn ? $db_info->use_cdn : "none");
		if($db_info->http_port)
		{
			Context::set('http_port', $db_info->http_port);
		}
		if($db_info->https_port)
		{
			Context::set('https_port', $db_info->https_port);
		}

		$this->showSendEnv();
		$this->checkEasyinstall();
	}

	/**
	 * check easy install
	 * @return void
	 */
	function checkEasyinstall()
	{
		$lastTime = (int) FileHandler::readFile($this->easyinstallCheckFile);
		if($lastTime > time() - 60 * 60 * 24 * 30)
		{
			return;
		}

		$oAutoinstallModel = getModel('autoinstall');
		$params = array();
		$params["act"] = "getResourceapiLastupdate";
		$body = XmlGenerater::generate($params);
		$buff = FileHandler::getRemoteResource(_XE_DOWNLOAD_SERVER_, $body, 3, "POST", "application/xml");
		$xml_lUpdate = new XmlParser();
		$lUpdateDoc = $xml_lUpdate->parse($buff);
		$updateDate = $lUpdateDoc->response->updatedate->body;

		if(!$updateDate)
		{
			$this->_markingCheckEasyinstall();
			return;
		}

		$item = $oAutoinstallModel->getLatestPackage();
		if(!$item || $item->updatedate < $updateDate)
		{
			$oController = getAdminController('autoinstall');
			$oController->_updateinfo();
		}
		$this->_markingCheckEasyinstall();
	}

	/**
	 * update easy install file content
	 * @return void
	 */
	function _markingCheckEasyinstall()
	{
		$currentTime = time();
		FileHandler::writeFile($this->easyinstallCheckFile, $currentTime);
	}

	/**
	 * Include admin menu php file and make menu url
	 * Setting admin logo, newest news setting
	 * @return void
	 */
	function makeGnbUrl($module = 'admin')
	{
		global $lang;

		// Check is_shortcut column
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('menu_item', 'is_shortcut'))
		{
			return;
		}

		$oAdminAdminModel = getAdminModel('admin');
		$lang->menu_gnb_sub = $oAdminAdminModel->getAdminMenuLang();

		$result = $oAdminAdminModel->checkAdminMenu();
		if(!$result->php_file)
		{
			header('Location: ' . getNotEncodedUrl('', 'module', 'admin'));
			Context::close();
			exit;
		}
		include $result->php_file;

		$oModuleModel = getModel('module');
		$moduleActionInfo = $oModuleModel->getModuleActionXml($module);

		$currentAct = Context::get('act');
		$subMenuTitle = '';
		foreach((array) $moduleActionInfo->menu as $key => $value)
		{
			if(isset($value->acts) && is_array($value->acts) && in_array($currentAct, $value->acts))
			{
				$subMenuTitle = $value->title;
				break;
			}
		}

		$parentSrl = 0;
		$oMenuAdminConroller = getAdminController('menu');
		if(!$_SESSION['isMakeXml'])
		{
			foreach((array) $menu->list as $parentKey => $parentMenu)
			{
				if(!$parentMenu['text'])
				{
					$oMenuAdminConroller->makeXmlFile($result->menu_srl);
					$_SESSION['isMakeXml'] = true;
					header('Location: ' . getNotEncodedUrl('', 'module', 'admin'));
					Context::close();
					exit;
				}

				if(!is_array($parentMenu['list']) || !count($parentMenu['list']))
				{
					continue;
				}
				if($parentMenu['href'] == '#' && count($parentMenu['list']))
				{
					$firstChild = current($parentMenu['list']);
					$menu->list[$parentKey]['href'] = $firstChild['href'];
				}

				foreach($parentMenu['list'] as $childKey => $childMenu)
				{
					if(!$childMenu['text'])
					{
						$oMenuAdminConroller->makeXmlFile($result->menu_srl);
						$_SESSION['isMakeXml'] = true;
						header('Location: ' . getNotEncodedUrl('', 'module', 'admin'));
						Context::close();
						exit;
					}

					if($subMenuTitle == $childMenu['text'])
					{
						$parentSrl = $childMenu['parent_srl'];
						break;
					}
				}
			}
		}

		// Admin logo, title setup
		$objConfig = $oModuleModel->getModuleConfig('admin');
		$gnbTitleInfo = new stdClass();
		$gnbTitleInfo->adminTitle = $objConfig->adminTitle ? $objConfig->adminTitle : 'XE Admin';
		$gnbTitleInfo->adminLogo = $objConfig->adminLogo ? $objConfig->adminLogo : 'modules/admin/tpl/img/xe.h1.png';

		$browserTitle = ($subMenuTitle ? $subMenuTitle : 'Dashboard') . ' - ' . $gnbTitleInfo->adminTitle;

		// Get list of favorite
		$oAdminAdminModel = getAdminModel('admin');
		$output = $oAdminAdminModel->getFavoriteList(0, true);
		Context::set('favorite_list', $output->get('favoriteList'));

		// Retrieve recent news and set them into context,
		// move from index method, because use in admin footer
		$newest_news_url = sprintf("http://news.xpressengine.com/%s/news.php?version=%s&package=%s", _XE_LOCATION_, __XE_VERSION__, _XE_PACKAGE_);
		$cache_file = sprintf("%sfiles/cache/newest_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
		if(!file_exists($cache_file) || filemtime($cache_file) + 60 * 60 < time())
		{
			// Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing administration page
			// Ensure to access the administration page even though news cannot be displayed
			FileHandler::writeFile($cache_file, '');
			FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL' => getFullUrl('')));
		}

		if(file_exists($cache_file))
		{
			$oXml = new XmlParser();
			$buff = $oXml->parse(FileHandler::readFile($cache_file));

			$item = $buff->zbxe_news->item;
			if($item)
			{
				if(!is_array($item))
				{
					$item = array($item);
				}

				foreach($item as $key => $val)
				{
					$obj = new stdClass();
					$obj->title = $val->body;
					$obj->date = $val->attrs->date;
					$obj->url = $val->attrs->url;
					$news[] = $obj;
				}
				Context::set('news', $news);
				if(isset($news) && is_array($news))
				{
					Context::set('latestVersion', array_shift($news));
				}
			}
			Context::set('released_version', $buff->zbxe_news->attrs->released_version);
			Context::set('download_link', $buff->zbxe_news->attrs->download_link);
		}

		Context::set('subMenuTitle', $subMenuTitle);
		Context::set('gnbUrlList', $menu->list);
		Context::set('parentSrl', $parentSrl);
		Context::set('gnb_title_info', $gnbTitleInfo);
		Context::setBrowserTitle($browserTitle);
	}

	/**
	 * Display Super Admin Dashboard
	 * @return void
	 */
	function dispAdminIndex()
	{
		// Get statistics
		$args = new stdClass();
		$args->date = date("Ymd000000", time() - 60 * 60 * 24);
		$today = date("Ymd");

		// Member Status
		$oMemberAdminModel = getAdminModel('member');
		$status = new stdClass();
		$status->member = new stdClass();
		$status->member->todayCount = $oMemberAdminModel->getMemberCountByDate($today);
		$status->member->totalCount = $oMemberAdminModel->getMemberCountByDate();

		// Document Status
		$oDocumentAdminModel = getAdminModel('document');
		$statusList = array('PUBLIC', 'SECRET');
		$status->document = new stdClass();
		$status->document->todayCount = $oDocumentAdminModel->getDocumentCountByDate($today, array(), $statusList);
		$status->document->totalCount = $oDocumentAdminModel->getDocumentCountByDate('', array(), $statusList);

		Context::set('status', $status);

		// Latest Document
		$oDocumentModel = getModel('document');
		$columnList = array('document_srl', 'module_srl', 'category_srl', 'title', 'nick_name', 'member_srl');
		$args->list_count = 5;
		;
		$output = $oDocumentModel->getDocumentList($args, FALSE, FALSE, $columnList);
		Context::set('latestDocumentList', $output->data);
		unset($args, $output, $columnList);

		// Latest Comment
		$oCommentModel = getModel('comment');
		$columnList = array('comment_srl', 'module_srl', 'document_srl', 'content', 'nick_name', 'member_srl');
		$args = new stdClass();
		$args->list_count = 5;
		$output = $oCommentModel->getNewestCommentList($args, $columnList);
		if(is_array($output))
		{
			foreach($output AS $key => $value)
			{
				$value->content = strip_tags($value->content);
			}
		}
		Context::set('latestCommentList', $output);
		unset($args, $output, $columnList);

		// Get list of modules
		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList();
		if(is_array($module_list))
		{
			$needUpdate = FALSE;
			$addTables = FALSE;
			foreach($module_list AS $key => $value)
			{
				if($value->need_install)
				{
					$addTables = TRUE;
				}
				if($value->need_update)
				{
					$needUpdate = TRUE;
				}
			}
		}

		// Get need update from easy install
		$oAutoinstallAdminModel = getAdminModel('autoinstall');
		$needUpdateList = $oAutoinstallAdminModel->getNeedUpdateList();

		if(is_array($needUpdateList))
		{
			foreach($needUpdateList AS $key => $value)
			{
				$helpUrl = './admin/help/index.html#';
				switch($value->type)
				{
					case 'addon':
						$helpUrl .= 'UMAN_terminology_addon';
						break;
					case 'layout':
					case 'm.layout':
						$helpUrl .= 'UMAN_terminology_layout';
						break;
					case 'module':
						$helpUrl .= 'UMAN_terminology_module';
						break;
					case 'widget':
						$helpUrl .= 'UMAN_terminology_widget';
						break;
					case 'widgetstyle':
						$helpUrl .= 'UMAN_terminology_widgetstyle';
						break;
					default:
						$helpUrl = '';
				}
				$needUpdateList[$key]->helpUrl = $helpUrl;
			}
		}

		Context::set('module_list', $module_list);
		Context::set('needUpdate', $isUpdated);
		Context::set('addTables', $addTables);
		Context::set('needUpdate', $needUpdate);
		Context::set('newVersionList', $needUpdateList);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('module_list..', 'module_list..author..', 'newVersionList..');

		// gathering enviroment check
		$mainVersion = join('.', array_slice(explode('.', __XE_VERSION__), 0, 2));
		$path = FileHandler::getRealPath('./files/env/' . $mainVersion);
		$isEnviromentGatheringAgreement = FALSE;
		if(file_exists($path))
		{
			$isEnviromentGatheringAgreement = TRUE;
		}
		Context::set('isEnviromentGatheringAgreement', $isEnviromentGatheringAgreement);
		Context::set('layout', 'none');

		$this->setTemplateFile('index');
	}

	/**
	 * Display Configuration(settings) page
	 * @return void
	 */
	function dispAdminConfigGeneral()
	{
		Context::loadLang('modules/install/lang');

		$db_info = Context::getDBInfo();

		Context::set('selected_lang', $db_info->lang_type);

		Context::set('default_url', $db_info->default_url);
		Context::set('langs', Context::loadLangSupported());

		Context::set('lang_selected', Context::loadLangSelected());

		$admin_ip_list = preg_replace("/[,]+/", "\r\n", $db_info->admin_ip_list);
		Context::set('admin_ip_list', $admin_ip_list);

		$oAdminModel = getAdminModel('admin');
		$favicon_url = $oAdminModel->getFaviconUrl();
		$mobicon_url = $oAdminModel->getMobileIconUrl();
		Context::set('favicon_url', $favicon_url.'?'.time());
		Context::set('mobicon_url', $mobicon_url.'?'.time());

		$oDocumentModel = getModel('document');
		$config = $oDocumentModel->getDocumentConfig();
		Context::set('thumbnail_type', $config->thumbnail_type);

		Context::set('IP', $_SERVER['REMOTE_ADDR']);

		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('module');
		Context::set('siteTitle', $config->siteTitle);
		Context::set('htmlFooter', $config->htmlFooter);

		$columnList = array('modules.mid', 'modules.browser_title', 'sites.index_module_srl');
		$start_module = $oModuleModel->getSiteInfo(0, $columnList);
		Context::set('start_module', $start_module);

		Context::set('pwd', $pwd);
		$this->setTemplateFile('config_general');

		$security = new Security();
		$security->encodeHTML('news..', 'released_version', 'download_link', 'selected_lang', 'module_list..', 'module_list..author..', 'addon_list..', 'addon_list..author..', 'start_module.');
	}

	/**
	 * Display FTP Configuration(settings) page
	 * @return void
	 */
	function dispAdminConfigFtp()
	{
		Context::loadLang('modules/install/lang');

		$ftp_info = Context::getFTPInfo();
		Context::set('ftp_info', $ftp_info);
		Context::set('sftp_support', function_exists(ssh2_sftp));

		$this->setTemplateFile('config_ftp');

		//$security = new Security();
		//$security->encodeHTML('ftp_info..');
	}

	/**
	 * Display Admin Menu Configuration(settings) page
	 * @return void
	 */
	function dispAdminSetup()
	{
		$oModuleModel = getModel('module');
		$configObject = $oModuleModel->getModuleConfig('admin');

		$oAdmin = getClass('admin');
		$oMenuAdminModel = getAdminModel('menu');
		$output = $oMenuAdminModel->getMenuByTitle($oAdmin->getAdminMenuName());

		Context::set('menu_srl', $output->menu_srl);
		Context::set('menu_title', $output->title);
		Context::set('config_object', $configObject);
		$this->setTemplateFile('admin_setup');
	}

	/**
	 * Enviroment information send to XE collect server
	 * @return void
	 */
	function showSendEnv()
	{
		if(Context::getResponseMethod() != 'HTML')
		{
			return;
		}

		$server = 'http://collect.xpressengine.com/env/img.php?';
		$path = './files/env/';
		$install_env = $path . 'install';
		$mainVersion = join('.', array_slice(explode('.', __XE_VERSION__), 0, 2));

		if(file_exists(FileHandler::getRealPath($install_env)))
		{
			$oAdminAdminModel = getAdminModel('admin');
			$params = $oAdminAdminModel->getEnv('INSTALL');
			$img = sprintf('<img src="%s" alt="" style="height:0px;width:0px" />', $server . $params);
			Context::addHtmlFooter($img);

			FileHandler::removeDir($path);
			FileHandler::writeFile($path . $mainVersion, '1');
		}
		else if(isset($_SESSION['enviroment_gather']) && !file_exists(FileHandler::getRealPath($path . $mainVersion)))
		{
			if($_SESSION['enviroment_gather'] == 'Y')
			{
				$oAdminAdminModel = getAdminModel('admin');
				$params = $oAdminAdminModel->getEnv();
				$img = sprintf('<img src="%s" alt="" style="height:0px;width:0px" />', $server . $params);
				Context::addHtmlFooter($img);
			}

			FileHandler::removeDir($path);
			FileHandler::writeFile($path . $mainVersion, '1');
			unset($_SESSION['enviroment_gather']);
		}
	}

}
/* End of file admin.admin.view.php */
/* Location: ./modules/admin/admin.admin.view.php */
