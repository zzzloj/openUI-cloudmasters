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
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_testimonials_testemunhos_rss extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
	{
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_testemunhos', 'public_errors' ), 'tuts' );
				
		/* What to do? */
		switch( $this->request['do'] )
		{
			case 'rss':
			default:
				$this->rss();
			break;
		}
	}
	
	public function rss()
	{
		require_once( IPS_KERNEL_PATH . "classRss.php" );
		$rss = new classRss();
  
		$channel_id = $rss->createNewChannel( array( 'title'       => $this->lang->words['testemunhos_title'].' - '.$this->settings['board_name'],
  											  	 	 'link'        => "{$this->settings['base_url']}app=testimonials",
  											     	 'description' => $this->settings['testemunhos_rss_title'],
  											     	 'pubDate'     => $rss->formatDate( time() ),
  											     	 'webMaster'   => "{$this->ipsclass->vars['email_out']} ({$this->ipsclass->vars['board_name']})"
		) );
 
 
 		$this->DB->build( array ( 'select'	=> '*', 'from' => 'testemunhos', 'where' => 't_approved=1', 'order' => 't_date DESC', 'limit' => array( 0,$this->settings['testemunhos_rss_items'] ) ) );
		$this->DB->execute();

		if ( !$this->DB->getTotalRows() )
      	{
        	$this->registry->output->showError( $this->lang->words['rss_none'] );
		}
      	else
      	{
        	while ( $t = $this->DB->fetch() )
        	{
				$content  = substr( $t['content'], 0, 100 );
           		$content .= " [<a href='{$this->settings['base_url']}app=testimonials&showtestimonial={$t['t_id']}'>{$this->lang->words['rss_readmore']}</a>]";
           		$final_content = $content;
           		$real_content =  $t['t_content'];
           
           		$rss->addItemToChannel( $channel_id, array( 'title'       => $t['t_title'],
															'link'        => $this->settings['base_url'].'app=testimonials&amp;showtestimonial='.$t['t_id'],
															'description' => $t['t_content'],
															'content'     => $real_content,
															'pubDate'	   => $rss->formatDate( $t['t_date'] ) ) );
        	}
       }
     
		$rss->createRssDocument();
  
  		print $rss->rss_document;
	}
	
}