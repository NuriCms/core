<?php
/**
 * @class  tagAdminController
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief admin controller class of the tag module
 */
class tagAdminController extends tag
{
	/**
	 * @brief Delete all tags for a particular module
	 */
	function deleteModuleTags($module_srl)
	{
		$args = new stdClass();
		$args->module_srl = $module_srl;
		return executeQuery('tag.deleteModuleTags', $args);
	}
}
/* End of file tag.admin.controller.php */
/* Location: ./modules/tag/tag.admin.controller.php */
