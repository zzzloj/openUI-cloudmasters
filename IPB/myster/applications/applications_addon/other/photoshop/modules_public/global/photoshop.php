<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_photoshop_global_photoshop extends ipsCommand
{					 
	public function doExecute( ipsRegistry $registry )
	{


		if ( in_array( $this->memberData['member_group_id'], explode( ",", $this->settings['ggt_photoshop_xgroups'] ) ) )
		{
			$this->registry->getClass('output')->showError( "You do not have permission to use this system.", 10001, false );
		}
		switch ( $this->request['do'] )
		{
			default:
				$this->show_page();
			break;
		}
		
		//-----------------------------------------
		// Set page title and send output
		//-----------------------------------------
		
		$this->registry->output->setTitle( $this->lang->words['ggt_photoshop_title'] );
 		$this->registry->output->addNavigation( $this->lang->words['ggt_photoshop_title'], 'app=photoshop' );
 		$this->registry->output->sendOutput();
	}

//-----------------------------------------
// Create Site
//-----------------------------------------
	private function show_page()
	{

		$this->registry->output->addContent( $this->registry->output->getTemplate('photoshop')->show_page( ) );


	}



}

?>