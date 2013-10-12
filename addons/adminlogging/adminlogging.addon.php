<?php

if(!defined('__XE__'))
	exit();

/**
 * @file adminlogging.addon.php
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 * @brief Automatic link add-on
 */
$logged_info = Context::get('logged_info');
$act = Context::get('act');
$kind = strpos(strtolower($act), 'admin') !== false ? 'admin' : '';

if($called_position == 'before_module_proc' && $kind == 'admin' && $logged_info->is_admin == 'Y')
{
	$oAdminloggingController = getController('adminlogging');
	$oAdminloggingController->insertLog($this->module, $this->act);
}
/* End of file adminlogging.php */
/* Location: ./addons/adminlogging */
