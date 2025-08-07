<?php

/**
* Tracker 2.1.0
*
* Post module
* Last Updated: $Date: 2013-02-16 15:36:07 +0000 (Sat, 16 Feb 2013) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	PublicModules
* @link			http://ipbtracker.com
* @version		$Revision: 1404 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_post_post extends ipsCommand
{
	/**
	* Main class entry point
	*
	* @access	public
	* @param	object		ipsRegistry reference
	* @return	void		[Outputs to screen]
	* @todo		We need to setup properly code for the errors, I have added them only with some copy paste for now (Terabyte)
	*/
	public function doExecute( ipsRegistry $registry )
	{
		$this->registry->tracker->addGlobalNavigation();

		//-----------------------------------------
		// Check the input
		//-----------------------------------------

		$this->request['pid'] = intval($this->request['pid']);
		$this->request['iid'] = intval($this->request['iid']);
		$this->request['p']   = intval($this->request['p']);
		$this->request['st']  = intval($this->request['st']) >=0 ? intval($this->request['st']) : 0;

		switch( $this->request['do'] )
		{
			case 'postnew':
				$this->class_post = $this->registry->tracker->post()->newLibrary();
				break;
			case 'postreply':
				$this->class_post = $this->registry->tracker->post()->replyLibrary();
				break;
			case 'postedit':
				$this->class_post = $this->registry->tracker->post()->editLibrary();
				break;
			default:
				$this->class_post = $this->registry->tracker->post()->newLibrary();
		}

		tracker_core_post_main::$project =  $this->registry->tracker->projects()->getProject( $this->request['pid'] );

		/* FIELDS-NEW */
		$this->registry->tracker->fields()->initialize( tracker_core_post_main::$project['project_id'] );

		//-----------------------------------------
		// Set up object array
		//-----------------------------------------

		$this->class_post->setIsPreview( isset($this->request['preview']) ? $this->request['preview'] : 0 );

		// Post settings
		if ( $this->class_post->getIsPreview() !== TRUE )
		{
			/* Showing form */
			$this->request['enablesig'] = ( isset( $this->request['enablesig'] ) ) ? $this->request['enablesig'] : 'yes';
			$this->request['enableemo'] = ( isset( $this->request['enableemo'] ) ) ? $this->request['enableemo'] : 'yes';
		}

		// Set up some stuff
		$this->class_post->setPostContent( isset( $_POST['Post'] ) ? $_POST['Post'] : '' );
		$this->class_post->setIssueID( $this->request['iid'] );
		$this->class_post->setProjectID( $this->request['p'] );
		$this->class_post->setPostID( $this->request['pid'] );
		$this->class_post->setAuthor( $this->memberData['member_id'] );
		$this->class_post->setSettings( array( 'enableSignature' => ( $this->request['enablesig']  == 'yes' ) ? 1 : 0,
											   'enableEmoticons' => ( $this->request['enableemo']  == 'yes' ) ? 1 : 0,
											   'enableTracker'	 => intval( $this->request['enabletrack'] ),
											   'post_htmlstatus' => intval( $this->request['post_htmlstatus'] ) ) );

		# Topic Title use _POST as it is cleaned in the function.
		# We wrap this because the title may not be set when showing a form and would
		# throw a length error
		if ( $_POST['TopicTitle'] )
		{
			$this->class_post->setIssueTitle( $_POST['TopicTitle'] );
		}

		//-----------------------------------------
		// Error out if we can not find the forum
		//-----------------------------------------

		if ( ! tracker_core_post_main::$project['project_id'] )
		{
			$this->registry->output->showError( 'We could not find the project you were trying to create a new issue in', '20T102' );
		}

		/* Build and check permissions */
		$this->registry->tracker->projects()->createPermShortcuts( tracker_core_post_main::$project['project_id'] );

		if ( ! $this->member->tracker['show_perms'] )
		{
			$this->registry->output->showError( 'You do not have permission to view this project', '10T100' );
		}

		if ( ! $this->member->tracker['read_perms'] )
		{
			$this->registry->output->showError( 'You are not allowed to read issues in this project', '10T100' );
		}

		//-----------------------------------------
		// Are we allowed to post at all?
		//-----------------------------------------

		if ( $this->memberData['member_id'] )
		{
        	if ( $this->memberData['restrict_post'] )
        	{
        		if ( $this->memberData['restrict_post'] == 1 )
        		{
        			$this->registry->getClass('output')->showError( 'posting_restricted', 103126, null, null, 403 );
        		}

        		$post_arr = IPSMember::processBanEntry( $this->memberData['restrict_post'] );

        		if ( IPS_UNIX_TIME_NOW >= $post_arr['date_end'] )
        		{
        			//-----------------------------------------
        			// Update this member's profile
        			//-----------------------------------------

					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
        		}
        		else
        		{
        			$this->registry->getClass('output')->showError( array( 'posting_off_susp', $this->registry->getClass( 'class_localization')->getDate( $post_arr['date_end'], 'LONG', 1 ) ), 103127, null, null, 403 );
        		}
        	}

			//-----------------------------------------
			// Flood check..
			//-----------------------------------------

			if ( $this->request['do'] != "postedit" &&
			     $this->settings['flood_control'] > 0 && $this->memberData['g_avoid_flood'] != 1 &&
			     IPS_UNIX_TIME_NOW - $this->memberData['last_post'] < $this->settings['flood_control'] )
			{
				$this->registry->output->showError( 'You are still within your flood control limit that the administrator has set', '10T113' );
			}
		}
		else if ( $this->member->is_not_human == 1 )
        {
        	$this->registry->getClass('output')->showError( 'posting_restricted', 103129, null, null, 403 );
        }

		//-----------------------------------------
		// Init class
		//-----------------------------------------

		$this->class_post->register( $this->registry );

		//-----------------------------------------
		// Show form or process?
		//-----------------------------------------

		if ( $this->request['whichaction'] == "save" )
		{
			//-----------------------------------------
        	// Make sure we have a valid auth key
        	//-----------------------------------------

        	if ( $this->request['auth_key'] != $this->member->form_hash )
			{
				$this->registry->getClass('output')->showError( 'posting_bad_auth_key', 20310, null, null, 403 );
			}

			//-----------------------------------------
			// Make sure we have a "Guest" Name..
			//-----------------------------------------

			$this->checkGuestName();
			$this->checkDoublePost();

			$this->class_post->processPost();
		}
		else
		{
			$this->class_post->showForm();
		}
	}

	/*-------------------------------------------------------------------------*/
	// Check for double post
	/*-------------------------------------------------------------------------*/

	private function checkDoublePost()
	{
		if ( $this->class_post->obj['preview_post'] == "" )
		{
			if ( preg_match( "/Post,.*,(01|03|07|11)$/", $this->ipsclass->location ) )
			{
				if ( IPS_UNIX_TIME_NOW - $this->ipsclass->lastclick < 2 )
				{
					if ( $this->request['do'] == 'postnew' )
					{
						//-----------------------------------------
						// Redirect to the newest topic in the forum
						//-----------------------------------------

						$this->DB->build(
							array(
								'select' => 'issue_id',
								'from'   => 'tracker_issues',
								'where'  => "project_id='".$this->class_post->project['project_id'],
								'order'  => 'last_post DESC',
								'limit'  => array( 0, 1 )
							)
						);
						$this->DB->execute();

						$issue = $this->DB->fetch();

						$this->registry->output->silentRedirect($this->tracker_std->base_url."showissue=".$issue['issue_id']);
						exit();
					}
					else
					{
						//-----------------------------------------
						// It's a reply, so simply show the topic...
						//-----------------------------------------

						$this->registry->output->silentRedirect($this->tracker_std->base_url."showissue=".$this->request['iid']."&amp;view=getlastpost");
						exit();
					}
				}
			}
		}
	}

	/**
	 * Check for guest's name
	 *
	 * @access	private
	 * @return	void		Adds guest prefix/suffix to $this->request['username']
	 */
	private function checkGuestName()
	{
		if ( !$this->memberData['member_id'] )
		{
			$this->request['UserName'] = trim($this->request['UserName']);
			$this->request['UserName'] = str_replace( "<br />", "", $this->request['UserName']);
			$this->request['UserName'] = $this->request['UserName'] ? $this->request['UserName'] : $this->lang->words['global_guestname'];

			if ( $this->request['UserName'] != $this->lang->words['global_guestname'] )
			{
				$check = $this->DB->buildAndFetch(
					array(
						'select'   => 'member_id',
						'from'     => 'members',
						'where'    => "members_l_username='".trim(strtolower($this->request['UserName']))."'"
					)
				);

				if ( $check['member_id'] )
				{
					$this->request['UserName'] = $this->settings['guest_name_pre'].$this->request['UserName'].$this->settings['guest_name_suf'];
				}
			}
		}
	}
}

?>