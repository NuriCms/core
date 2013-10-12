<?php
/**
 * @class  pageAPI
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief View Action page for the module API processing
 */
class pageAPI extends page
{
	/**
	 * @brief Page information
	 */
	function dispPageIndex(&$oModule)
	{
		$page_content = Context::get('page_content');
		$oWidgetController = &getController('widget');

		$requestMethod = Context::getRequestMethod();
		Context::setResponseMethod('HTML');
		$oWidgetController->triggerWidgetCompile($page_content);
		Context::setResponseMethod($requestMethod);

		$oModule->add('page_content',$page_content);
	}
}
/* End of file page.api.php */
/* Location: ./modules/page/page.api.php */
