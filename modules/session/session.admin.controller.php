<?php
/**
 * @class  sessionAdminController
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief The admin controller class of the session module
 */
class sessionAdminController extends session
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief The action to clean up the Derby session
	 */
	function procSessionAdminClear()
	{
		$oSessionController = &getController('session');
		$oSessionController->gc(0);

		$this->add('result',Context::getLang('session_cleared'));
	}
}
/* End of file session.admin.controller.php */
/* Location: ./modules/session/session.admin.controller.php */
