<?php

/**
 * @author -RAW-
 * @copyright 2012
 * @link http://rawcodes.net
 * @filesource Testimonials System
 * @version 1.2.0
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_testimonials_extras_rating extends ipsCommand
{
	/**
	 * Forum data
	 *
	 * @var		array
	 */
	public $forum		= array();
	
	/**
	 * Topic data
	 *
	 * @var		array
	 */
	public $topic		= array();
	
	/**
	* Class entry point
	*
	* @param	object		Registry reference
	* @return	@e void		[Outputs to screen/redirects]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		/* Security Check */
		if ( $this->request['secure_key'] != $this->member->form_hash )
		{
			$this->registry->output->showError( 'usercp_forums_bad_key', 102999, null, null, 403 );
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$topic_id	= intval($this->request['t']);
		$rating_id	= intval($this->request['rating']);

	
		$this->registry->class_localization->loadLanguageFile( array( 'public_testemunhos', 'testemunhos' ) );
        $this->registry->class_localization->loadLanguageFile( array( 'public_errors', 'testemunhos' ) );
        
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		if ( ! $topic_id )
		{
			$this->registry->output->showError( 'testimonial_no_tid', 10346, null, null, 404 );
		}

		$this->topic	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos', 'where' => 't_id=' . $topic_id ) );

		
		if ( ! $this->topic['t_id'] )
		{
			$this->registry->output->showError( 'testimonial_no_tid', 10347, null, null, 404 );
		}

		//-----------------------------------------
		// Locked topic?
		//-----------------------------------------

   		if ( $this->topic['t_open'] != '1' )
   		{
   			$this->registry->output->showError( 'testimonials_rate_locked', 10348, null, null, 403 );
   		}


			if ( $this->topic['t_approved'] != 1 )
			{
				$this->registry->output->showError( 'testimonials_not_approved', 103151, null, null, 403 );
			}
		


		//-----------------------------------------
		// Permissions check
		//-----------------------------------------
		
		if ( $this->memberData['member_id'] )
		{
			$_can_rate = intval( $this->memberData['g_topic_rate_setting'] );
		}
		else
		{
			$_can_rate = 0;
		}

		if ( ! $this->forum['forum_allow_rating'] )
		{
			$_can_rate = 0;
		}



		//-----------------------------------------
		// Sneaky members rating topic more than 5?
		//-----------------------------------------

   		if( $rating_id > 5 )
   		{
	   		$rating_id = 5;
   		}

   		if( $rating_id < 0 )
   		{
	   		$rating_id = 0;
   		}

   		//-----------------------------------------
   		// Have we rated before?
		//-----------------------------------------

		$rating = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'testemunhos_ratings', 'where' => "rating_tid={$this->topic['t_id']} and rating_member_id=" . $this->memberData['member_id'] ) );

		//-----------------------------------------
		// Already rated?
		//-----------------------------------------

		if ( $rating['rating_id'] )
		{
			//-----------------------------------------
			// Do we allow re-ratings?
			//-----------------------------------------

			if ( $this->memberData['g_topic_rate_setting'] == 2 )
			{
				if ( $rating_id != $rating['rating_value'] )
				{
					$new_rating = $rating_id - $rating['rating_value'];
					
					$this->DB->update( 'testemunhos_ratings', array( 'rating_value' => $rating_id ), 'rating_id=' . $rating['rating_id'] );
					
					$this->DB->update( 'testemunhos', array( 't_rating' => intval($this->topic['t_rating']) + $new_rating, 't_rating_hits'		=> intval($this->topic['t_rating_hits']) + 1 ), 't_id=' . $this->topic['t_id'] );
				}

				$this->registry->output->redirectScreen( $this->lang->words['testimonials_rating_changed'], $this->registry->getClass('output')->buildSEOUrl( "showtestimonial=".$this->topic['t_id'], 'publicWithApp', $this->topic['t_title_seo'], 'showtestimonial' ) );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['testimonials_rated_already'], $this->registry->getClass('output')->buildSEOUrl( "showtestimonial=".$this->topic['t_id'], 'publicWithApp', $this->topic['t_title_seo'], 'showtestimonial' ) );
			}
		}

		//-----------------------------------------
		// NEW RATING!
		//-----------------------------------------

		else
		{
			$this->DB->insert( 'testemunhos_ratings', array( 
															'rating_tid'		=> $this->topic['t_id'],
															'rating_member_id'	=> $this->memberData['member_id'],
															'rating_value'		=> $rating_id,
															'rating_ip_address'	=> $this->member->ip_address 
														) 
								);
								
								$this->DB->update( 'testemunhos', array( 
													't_rating_hits'		=> intval( $this->topic['t_rating_hits'] )  + 1,
													't_rating'	        => intval( $this->topic['t_rating'] ) + $rating_id 
												),  'id=' . $this->topic['t_id'] );
												
		}

        $this->registry->output->redirectScreen( $this->lang->words['testimonials_rating_done'], $this->registry->getClass('output')->buildSEOUrl( "showtestimonial=".$this->topic['t_id'], 'publicWithApp', $this->topic['t_title_seo'], 'showtestimonial' ) );
 	}
}
