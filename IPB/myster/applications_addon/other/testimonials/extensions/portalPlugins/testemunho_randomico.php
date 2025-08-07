<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_testemunho_randomico extends public_portal_portal_portal 
{
	public function init()
 	{
 	}
 	
	public function testemunho_randomico()
	{
		/* Init */
		$return  = "";
		$cat_ids = array();
		$rows    = array();
		
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_testemunhos' ), 'testemunhos' );
		
		/* Got some categories? */
		if ( $this->memberData['sostestemunhos_view'] )
		{
			$this->DB->build( array( 'select'   => 't.t_id, t.t_title, t.t_title_seo, t.t_date, t_member_id, t_views',
									 'from'     => array( 'testemunhos' => 't' ),
									 'where'    => 't.t_approved=1',
									 'add_join' => array( 0 => array( 'select' => 'm.member_id, m.member_group_id, m.members_display_name, m.members_seo_name',
																	  'from'   => array( 'members' => 'm' ),
																	  'where'  => 'm.member_id=t.t_member_id',
																	  'type'   => 'left' ) ),
									 'order'    => 'RAND()',
									 'limit'    => array( 0, 1 ),
							)	   );
			$this->DB->execute();
			
			while ( $row = $this->DB->fetch() )
			{
				$rows[] = $row;
			}
			
			/* Add our skin template */
			$return .= $this->registry->output->getTemplate( 'testemunhos' )->portalBlock( $this->lang->words['testemunho_rand'], $rows );
		}
		//$this->lang->words['testemunhos_recentes']
		
		/* Return */
		return $return;
	}
}