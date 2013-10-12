<?php
/**
 * @class  editorAPI
 * @author NURI Project (developer@nuricms.org)
 * @brief
 */
class editorAPI extends editor
{
	function dispEditorSkinColorset(&$oModule)
	{
		$oModule->add('colorset', Context::get('colorset'));
	}
}
/* End of file editor.api.php */
/* Location: ./modules/editor/editor.api.php */
