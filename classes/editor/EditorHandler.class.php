<?php

/**
 * Superclass of the edit component.
 * Set up the component variables
 *
 * @class EditorHandler
 * @Original_author NHN
 * @Adaptor NURI Project (developer@nuricms.org)
 */
class EditorHandler extends Object
{

	/**
	 * set the xml and other information of the component
	 * @param object $info editor information
	 * @return void
	 * */
	function setInfo($info)
	{
		Context::set('component_info', $info);

		if(!$info->extra_vars)
		{
			return;
		}

		foreach($info->extra_vars as $key => $val)
		{
			$this->{$key} = trim($val->value);
		}
	}

}
/* End of file EditorHandler.class.php */
/* Location: ./classes/editor/EditorHandler.class.php */
