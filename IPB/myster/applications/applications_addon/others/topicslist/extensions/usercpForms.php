<?php


if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class usercpForms_topicslist extends public_core_usercp_manualResolver implements interface_usercp
{

	public $tab_name = "Topics Wall";

	public $defaultAreaCode = 'viewmode';

	public $ok_message = '';

	public $hide_form_and_save_button = false;

 	public $version	= 32;

	public function init( )
	{
		$this->tab_name	= ipsRegistry::getClass('class_localization')->words['tl_tab'];

	}


	public function getLinks()
	{
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_home' ), 'topicslist' );

		$array = array();

		$array[] = array( 'url'   => 'area=viewmode',
						   'title' => ipsRegistry::instance()->getClass('class_localization')->words['tl_changemodetitle'],
						   'active' => $this->request['tab'] == 'topicslist' Or $this->request['area'] == 'viewmode' ? 1 : 0,
						  'area'  => 'viewmode'
						  );
		
		return $array;
	}

	public function runCustomEvent( $currentArea )
	{

		$html = '';		

		switch( $currentArea )
		{			
			default:
				$code = "21TL10";
				$this->registry->getClass('output')->showError( $this->lang->words['tl_nosect_ucp'], $code, true );
				break;
			case '' :
			case 'viewmode':
				$html = $this->_customEvent_viewingMode();
			break;			
		}

		return $html;
	}

	public function showForm( $current_area, $errors=array() )
	{
		switch( $current_area )
		{
			default:
			case 'viewmode':
				return $this->showViewingMenu();
			break;

		}
	}
	
		public function showViewingMenu()
	{
		
		$this->hide_form_and_save_button = !$this->settings['tl_userdecide'];
		
		switch( $this->settings['tl_detailed'] )
		{
			case 'detailed':
				$m = $this->lang->words['tl_detailed'];
				break;
			case 'fb':
				$m = $this->lang->words['tl_fb'];
				break;
			default:
				$m = $this->lang->words['tl_classic'];
				break;
		}
		
		if( $this->settings['tl_userdecide'] ){
		
			$cookie_ex = IPSCookie::get( 'tl_defaultview');
			
			if( !$cookie_ex ) $mode = $this->settings['tl_detailed'];
			else $mode = $cookie_ex;
		}
	
		else $mode = $this->settings['tl_detailed'];
		
		return $this->registry->getClass('output')->getTemplate('list')->userMenu(  $mode, $m );
	}
	
	public function saveForm( $current_area )
	{
		
		switch( $current_area )
		{
			default:
			case 'viewmode':
				return $this->saveMode( $_POST['mode']);
			break;
		}
	}

	public function saveMode( $view )
	{
 		
 		IPSCookie::set( 'tl_defaultview', $view, 1 );
		
		return TRUE;
	}
	
	
}
