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

class public_testimonials_testemunhos_findpost extends ipsCommand
{
	public function doExecute( ipsRegistry $registry )
    {
		$this->output  = $this->registry->output;
		$this->library = $this->registry->getClass('testemunhosLibrary');
		
		/* Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_display' ) );

		/* Find me the comment */
		$pid = intval( $this->request['id'] );
		
		if ( !$pid )
		{
			$this->registry->getClass('output')->showError( 'missing_cid_find' );
		}
		
		$post = $this->DB->buildAndFetch( array( 'select' => 'tid', 'from' => 'testemunhos_comments', 'where' => 'cid=' . $pid ) );
		
		if ( !$post['tid'] )
		{
			$this->registry->getClass('output')->showError( 'missing_cid_find' );
		}
		
		$testemunho = $this->DB->buildAndFetch( array( 'select' => 't_title_seo', 'from' => 'testemunhos', 'where' => 't_id=' . $post['tid'] ) );
		
		$cposts = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as posts', 'from' => 'testemunhos_comments', 'where' => "tid={$post['tid']} AND cid <= {$pid}" ) );							
		
		if ( $cposts['posts'] % $this->settings['testemunhos_comentariospp'] == 0 )
		{
			$pages = $cposts['posts'] / $this->settings['testemunhos_comentariospp'];
		}
		else
		{
			$pages = ceil( $cposts['posts'] / $this->settings['testemunhos_comentariospp'] );
		}
		
		$st = ( $pages - 1 ) * $this->settings['testemunhos_comentariospp'];
		
		/* Comment system maybe is disabled? */
		$comment = $this->settings['testemunhos_systemon'] ? "&st={$st}#comment" . $pid : "";
		
		/* Redirect */
		$this->registry->getClass('output')->silentRedirect( $this->registry->getClass('output')->buildSEOUrl( "showtestimonial=" . $post['tid'] . $comment, 'publicWithApp', $testemunho['t_title_seo'] ) );
 	}
}