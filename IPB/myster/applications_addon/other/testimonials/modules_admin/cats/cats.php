<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_testimonials_cats_cats extends ipsCommand
{
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;
	
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{

		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_cats' );
		$this->form_code    = $this->html->form_code    = 'module=cats&amp;section=cats';
		$this->form_code_js = $this->html->form_code_js = 'module=cats&section=cats';

        //-------------------------------
        // Grab the settings controller, instantiate and set up shortcuts
        //-------------------------------
                
        $classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir('core') . '/modules_admin/settings/settings.php', 'admin_core_settings_settings' );
        $settings       = new $classToLoad();
        $settings->makeRegistryShortcuts( $this->registry );
                			
		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		$this->lang->loadLanguageFile( array( 'admin_testemunhos' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch($this->request['do'])
		{						
			case 'add':
				$this->add();
				break;
			case 'doAdd':
				$this->doAdd();
				break;
			case 'edit':
				$this->edit();
				break; 
			case 'doedit':
				$this->doedit();
				break;                               
			case 'catMove':
				$this->catMove();
				break;
			case 'delete':
				$this->delete();
				break;
			case 'manage':
				 default:
				$this->manage();
				break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	public function add()
	{
	    $form = array();
                      
        $formContent['c_name']    = $this->registry->output->formInput( 'c_name', '' );
        $formContent['c_desc']    = $this->registry->output->formTextarea('c_desc', '');
        
		$this->registry->output->html .= $this->html->add($formContent);
	}

	public function doAdd()
	{
		/* Parsing stuff */
		IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'testemunhos_comment';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
				
        $insert['c_desc'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $_POST['c_desc'] );
            	   
  		$insert['c_name'] = trim( IPSText::stripslashes( IPSText::htmlspecialchars( $_POST['c_name'] ) ) );
		
		$insert['c_name_seo'] = IPSText::makeSeoTitle( $_POST['c_name'] );
                      
        $this->DB->insert( 'testemunhos_cats', $insert ); 
            
        $message = "{$this->lang->words['sbBot_msgadded']}Category successfully added.";
		
        $this->registry->output->redirect( $this->settings['base_url'].$this->html->form_code. "&amp;st=" . $this->request['st'], $message );            
	}

    public function manage()
    {
		$start 		= intval( $this->request['st'] ) >= 0 ? intval( $this->request['st'] ) : 0;   
				
		$count 		= $this->DB->buildAndFetch( array( 'select'   => 'count(*) as cnt', 'from'  => 'testemunhos_cats' ) );
						 					 
        $pagination = $this->registry->output->generatePagination( array('totalItems'		    => $count['cnt'],
																		 'itemsPerPage'		    => 10,
																		 'currentStartValue'	=> $start,
																		 'baseUrl'			    => $this->settings['base_url'] . "&" . $this->html->form_code,));  
																														       
        $this->DB->build( array('select'   => '*',
								'from'     => 'testemunhos_cats',
                                'order'    => 'c_pos ASC',
                                'limit'    => array( $start, 10 ), ) );
		$this->DB->execute();				
		
		if ( $this->DB->getTotalRows() )
		{
		   while ( $row = $this->DB->fetch() )
		   {
				   
				$allCats[] = $row;
		   }		
		}

		/* Show page */				
		$this->registry->output->html .= $this->html->manage( $allCats, $pagination );	
    }

    public function edit()
    {        
        $this->registry->output->html .= $this->html->edit();	  
    }

    public function doedit()
    {
        $Id = intval( $this->request['id'] );
        /* Set BBCode parser */				
        $st	      = $this->request['st'];
		/* Parse post */
		IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'testemunhos_comment';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];
                                                                       			                                    	   
  		$insert['c_name']      = trim( IPSText::stripslashes( IPSText::htmlspecialchars( $this->request['c_name'] ) ) );

        $insert['c_desc'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $this->request['c_desc'] );      
        
        $insert['c_name_seo'] = IPSText::makeSeoTitle( $_POST['c_name'] ); 
		
        $this->DB->update( 'testemunhos_cats', $insert, 'c_id=' . $Id );
           	
		$message = "{$this->lang->words['sbBot_msgupdated']}";
		
	    $this->registry->output->redirect( $this->settings['base_url'].$this->html->form_code. "&amp;st=" . $this->request['st'], $message );                 
    }
        
	public function catMove()
	{
	    /* Get ajax class */
        $classToLoad = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classAjax.php', 'classAjax' );
        $ajax   = new $classToLoad();

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		$position = 1;
 		
 		if( is_array($this->request['testimonials']) AND count($this->request['testimonials']) )
 		{
 			foreach( $this->request['testimonials'] as $this_id )
 			{
 				$this->DB->update( 'testemunhos_cats', array( 'c_pos' => $position ), 'c_id=' . $this_id ); 				
 				$position++;
 			}
 		}

 		$ajax->returnString( 'OK' );
 		exit();
	}

    public function delete()
    {
        /* INIT */				
        $id = intval( $this->request['id'] );
		
        if ( ! $id )
        {
            $this->registry->output->showError( $this->lang->words['errorNoId'], 'MP001' );		
	    }
		
		/* Delete it :( */
		$this->DB->delete( 'testemunhos_cats', 'c_id='.$id );	
		
		/* Set message */			
		$message = "{$this->lang->words['delete_cat']}";
		
	    $this->registry->output->redirect( $this->settings['base_url'].$this->html->form_code. "&amp;st=" . $this->request['st'], $message );        
    }

}