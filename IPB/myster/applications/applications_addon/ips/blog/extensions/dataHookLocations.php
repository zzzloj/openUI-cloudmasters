<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.6.3
 * Define data hook locations (Blog)
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 10721 $
 */

$dataHookLocations = array(

	/* POSTING LIBRARY DATA LOCATIONS */
	array( 'blogAddEntry', 'Add Blog Entry: Entry' ),
	array( 'blogAddEntryPoll','Add Blog Entry: Poll' ),
	array( 'blogAddBlog', 'Add New Blog' ),
	array( 'blogEditEntryData', 'Edit Blog Entry: Entry Data' ),
	array( 'blogEditEntryAddPoll', 'Edit Blog Entry: Added Poll' ),
	array( 'blogEditEntryUpdatePoll', 'Edit Blog Entry: Updated Poll' ),
	array( 'blogPreAddComment', 'Before Blog Comment is Added'),
	array( 'blogPreEditComment', 'Before Blog Comment is Edited'),
	array( 'blogPostAddComment', 'After Blog Comment is Added'),
	array( 'blogPostEditComment', 'After Blog Comment is Edited'),
	array( 'blogPostDeleteComments', 'After Blog Comments are Deleted'),
	array( 'blogPostCommentVisibilityToggle', 'After Blog Comments Visibility is Toggled'),
);