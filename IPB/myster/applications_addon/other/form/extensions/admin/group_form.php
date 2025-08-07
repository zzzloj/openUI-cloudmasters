<?php
/*
+--------------------------------------------------------------------------
|   Form Manager v1.0.0
|   =============================================
|   by Michael
|   Copyright 2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
*/

class admin_group_form__form implements admin_group_form
{	
	public $tab_name = '';

	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_form_group_form', 'form');
		return array( 'tabs' => $this->html->acp_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}

	public function getForSave()
	{
		$return = array(
			'g_fs_view_offline'	  => intval(ipsRegistry::$request['g_fs_view_offline']),
			'g_fs_submit_wait'    => intval(ipsRegistry::$request['g_fs_submit_wait']),     
			'g_fs_bypass_captcha' => intval(ipsRegistry::$request['g_fs_bypass_captcha']),
 			'g_fs_allow_attach'   => intval(ipsRegistry::$request['g_fs_allow_attach']),           
			'g_fs_view_logs'      => intval(ipsRegistry::$request['g_fs_view_logs']),
            'g_fs_moderate_logs'  => intval(ipsRegistry::$request['g_fs_moderate_logs'])																																													
		);

		return $return;
	}
}

?>