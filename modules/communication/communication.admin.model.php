<?php

/**
 * @class  communicationAdminModel
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief communication module of the admin model class
 */
class communicationAdminModel extends communication
{

	/**
	 * Initialization
	 */
	function init()
	{

	}

	/**
	 * the html to select colorset of the skin
	 * @return void
	 */
	function getCommunicationAdminColorset()
	{
		$skin = Context::get('skin');
		$type = Context::get('type') == 'P' ? 'P' : 'M';
		Context::set('type', $type);

		if($type == 'P')
		{
			$dir = 'skins';
		}
		else
		{
			$dir = 'm.skins';
		}

		if(!$skin)
		{
			$tpl = "";
		}
		else
		{
			$oModuleModel = getModel('module');
			$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin, $dir);
			Context::set('skin_info', $skin_info);

			$oModuleModel = getModel('module');
			$communication_config = $oModuleModel->getModuleConfig('communication');
			if(!$communication_config->colorset)
			{
				$communication_config->colorset = "white";
			}
			Context::set('communication_config', $communication_config);

			$security = new Security();
			$security->encodeHTML('skin_info.colorset..title', 'skin_info.colorset..name');
			$security->encodeHTML('skin_info.colorset..name');

			$oTemplate = TemplateHandler::getInstance();
			$tpl = $oTemplate->compile($this->module_path . 'tpl', 'colorset_list');
		}

		$this->add('tpl', $tpl);
		$this->add('type', $type);
	}

}
/* End of file communication.admin.model.php */
/* Location: ./modules/comment/communication.admin.model.php */
