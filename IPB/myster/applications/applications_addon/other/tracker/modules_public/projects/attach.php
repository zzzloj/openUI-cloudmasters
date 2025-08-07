<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.1.4
 * View Topic Attachments
 * Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
 * </pre>
 *
 * @author 		$Author: stoo2000 $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 *
 * @package		IP.Board
 * @subpackage  Forums 
 * @link		http://www.invisionpower.com
 * @version		$Rev: 1363 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_tracker_projects_attach extends iptCommand
{
	/**
	* Class entry point
	*
	* @access	public
	* @param	object		Registry reference
	* @return	void		[Outputs to screen/redirects]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		/* INIT */
		$topic_id = intval( $this->request['issue_id'] );
				
		/* Make sure we have a topic */
		if ( ! $topic_id )
        {
        	$this->registry->getClass('output')->showError( 'attach_missing_tid', 10329, null, null, 404 );
        }
        
		/* Query the topic */
        $topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'tracker_issues', 'where' => 'issue_id=' . $topic_id ) );
        
        if ( ! $topic['hasattach'] )
        {
        	$this->registry->getClass('output')->showError( 'attach_no_attachments', 10330, null, null, 404 );
        }
        
		/* Check the forum */
        if ( ! $this->tracker->projects()->checkPermission( 'read', $topic['project_id'] ) )
		{
			$this->registry->getClass('output')->showError( 'attach_no_forum_perm', 2037, true, null, 403 );
		}
		
		/* Build the attachment display */
		$this->registry->getClass('output')->addContent( $this->getAttachments( $topic ) );
		
		/* Set the title */
		$this->registry->getClass('output')->setTitle($topic['title'] .' -> ' . $this->lang->words['attach_page_title']  . ' - ' . ipsRegistry::$settings['board_name']);
		
		/* Set the navigation */
		$this->tracker->projects()->createBreadcrumb( $topic['project_id'] );
		$this->registry->getClass('output')->addNavigation( $topic['title'], 'app=tracker&showissue=' . $topic_id, $topic['title_seo'], 'showissue' );	
		
		/* Send the output */
        $this->registry->getClass('output')->sendOutput();
	}

	/**
	* Get the actual output.
	* This is abstracted so that the AJAX routine can load and execute this function
	*
	* @access	public
	* @param	array 		Topic and attachment data
	* @return	string		HTML output
	*/
	public function getAttachments( $topic )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$attach_cache = ipsRegistry::cache()->getCache('attachtypes');
		
		//-----------------------------------------
		// Get forum skin and lang
		//-----------------------------------------
		
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'public_forums', 'public_topic' ), 'forums' );
		
		//-----------------------------------------
		// aight.....
		//-----------------------------------------
		
		$_st		= $this->request['st'] > 0 ? intval($this->request['st']) : 0;
		$_limit		= 50;
		$_pages		= '';
		$_count		= $this->DB->buildAndFetch( array(
													'select'	=> 'COUNT(*) as attachments', 
													'from'		=> array( 'tracker_posts' => 'p' ),
													'where'		=> 'a.attach_id IS NOT NULL AND p.issue_id='.$topic['issue_id'],
													'add_join'	=> array( array(
																				'from'   => array( 'attachments' => 'a' ),
																				'where'  => "a.attach_rel_id=p.pid AND a.attach_rel_module='tracker'",
																				'type'   => 'left' ) 
																				)
											)		);

		if( $_count['attachments'] > $_limit )
		{
			$_pages	= $this->registry->getClass('output')->generatePagination( array( 
																					'totalItems'		=> $_count['attachments'],
																					'itemsPerPage'		=> $_limit,
																					'currentStartValue'	=> $_st,
																					'baseUrl'			=> "app=tracker&amp;module=projects&amp;section=attach&amp;tid={$topic['issue_id']}",
																			)	);
		}

		$this->DB->build( array( 
										'select'	=> 'p.pid, p.issue_id',
										'from'		=> array( 'tracker_posts' => 'p' ),
										'where'		=> 'a.attach_id IS NOT NULL AND p.issue_id='.$topic['issue_id'],
										'order'		=> 'p.pid ASC, a.attach_id ASC',
										'limit'		=> array( $_st, $_limit ),
										'add_join'	=> array( array(
																	'select' => 'a.*',
																	'from'   => array( 'attachments' => 'a' ),
																	'where'  => "a.attach_rel_id=p.pid AND a.attach_rel_module='tracker'",
																	'type'   => 'left' ) 
																	)												
							)	);
										
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			if ( $this->tracker->projects()->checkPermission('read', $topic['project_id']) != TRUE )
			{
				continue;
			}
			
			if ( ! $row['attach_id'] )
			{
				continue;
			}
			
			$row['image']		= str_replace( 'folder_mime_types', 'mime_types', $attach_cache[ $row['attach_ext'] ]['atype_img'] );
			$row['short_name']	= IPSText::truncate( $row['attach_file'], 30 );
			$row['attach_date']	= $this->registry->getClass( 'class_localization')->getDate( $row['attach_date'], 'SHORT' );
			$row['real_size']	= IPSLib::sizeFormat( $row['attach_filesize'] );
			
			$rows[]	= $row;
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('tracker_projects')->attachments( $topic['title'], $rows, $_pages );
		
		return $this->output;
	}
}