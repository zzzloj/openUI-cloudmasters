<?php

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_topicslist_list_overview extends ipsCommand 
{

	private $html;
	/**
	 * Main execution method
	 *
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_list' );
		
		
		switch( $this->request['do'] ){
			case 'overview' :
				$this->home();
				break;
			
			default:
				$this->home();
				break;
				}
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput( );
		#$this->$html = $this->registry->output->loadTemplate( 'cp_skin_list' );
		#$return = $html->test();
		
	}
	

	public function home(){
	
		
		$this->registry->output->html .= $this->html->listOverview(  );


	}
}