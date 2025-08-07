<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( !defined('IN_IPB') )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_testimonials_testemunhos_cats extends ipsCommand
{
	private $output;
	private $library;

	public function doExecute( ipsRegistry $registry )
	{

		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
        
		$this->library->checkPermissions();
      
		switch( $this->request['code'] )
		{
			case 'cats':
			default:
				$this->catsall();
				break;
		}

		$this->library->sendOutput();  
	}
	

    private function catsall()
    {     
        $this->registry->output->addNavigation( $this->lang->words['testemunhos_title'], 'app=testimonials' );
                   
        $count = $this->DB->buildAndFetch( array( 'select' => 'count(*) AS total',
                                                  'from'   => 'testemunhos_cats' ) );
		
        $this->DB->build( array( 'select'   => '*',
						         //'from'   => 'testemunhos_cats',
								 'from'     => 'testemunhos_cats',
						         'order'    => 'c_pos ASC' ));
                                                			
        $this->DB->execute();
             
        $cats =array();
            
        while( $r = $this->DB->fetch() )
        {	
	        $cats[] = $r;		
        }

		if ( !$cats )
		{
			$this->registry->output->showError( 'no_testimonialcats' );
		}
		
       $this->library->pageOutput .= $this->registry->output->getTemplate('testimonials')->testimonialCats($cats, $count, $this->library->getActiveUsers());             
    }
}	
?>