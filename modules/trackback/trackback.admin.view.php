<?php
/**
 * trackbackAdminView class
 * trackback module admin view class
 *
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @package /modules/trackback
 * @version 0.1
 */
class trackbackAdminView extends trackback
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Display output list (administrative)
	 * @return void
	 */
	function dispTrackbackAdminList()
	{
		// Wanted set
		$oModuleModel = &getModel('module');
		$config = $oModuleModel->getModuleConfig('trackback');
		Context::set('config',$config);

		// Options to get a list
		$args = new stdClass();
		$args->page = Context::get('page'); // / "Page
		$args->list_count = 30; // / "One page of posts to show the
		$args->page_count = 10; // / "Number of pages that appear in the page navigation

		$args->sort_index = 'list_order'; // / "Sorting values
		$args->module_srl = Context::get('module_srl');
		// Get a list
		$oTrackbackAdminModel = &getAdminModel('trackback');
		$output = $oTrackbackAdminModel->getTotalTrackbackList($args);

		// To write to a template parameter settings
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('trackback_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		//Security
		$security = new Security();
		$security->encodeHTML('config.');
		$security->encodeHTML('trackback_list..');

		// Set a template
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('trackback_list');
	}
}
/* End of file trackback.admin.view.php */
/* Location: ./modules/trackback/trackback.admin.view.php */
