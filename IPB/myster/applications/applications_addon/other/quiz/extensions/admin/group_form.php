<?php 
class admin_group_form__quiz implements admin_group_form
{  
	
	public $tab_name = "";
	public function getDisplayContent( $group=array(), $tabsUsed = 2 )
	{
		$this->html = ipsRegistry::getClass('output')->loadTemplate( 'cp_skin_quiz_group_form', 'quiz' );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_quiz' ), 'quiz' );
		$group['g_trac_settings'] = unserialize( $group['g_quiz_settings'] );
		return array( 'tabs' => $this->html->acp_trac_group_form_tabs( $group, ( $tabsUsed + 1 ) ), 'content' => $this->html->acp_trac_group_form_main( $group, ( $tabsUsed + 1 ) ), 'tabsUsed' => 1 );
	}
	
	public function getForSave()
	{
		// could do a foreach on the post data to dynamically assign these variables, it's quite tedious doing it manually.
		$return = array(
			'g_quiz_can_take_quiz'		=> ipsRegistry::$request['g_quiz_can_take_quiz'],
			'g_quiz_can_view_quiz'		=> ipsRegistry::$request['g_quiz_can_view_quiz'],
			'g_quiz_can_add_quiz'		=> ipsRegistry::$request['g_quiz_can_add_quiz'],
		);

		return $return;
	}
	
	
}

?>