<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Define data hook locations (Calendar)
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

	/* POSTING DATA LOCATIONS */
	array( 'calendarAddEvent', 'Add Calendar Event' ),
	array( 'calendarEditEvent', 'Edit Calendar Event' ),
	array( 'calendarAddComment', 'Add Event Comment' ),
	array( 'calendarEditComment', 'Edit Event Comment' ),
	array( 'calendarCommentAddPostSave', 'Add Event Comment (Post Save)' ),
	array( 'calendarCommentEditPostSave', 'Edit Event Comment (Post Save)' ),
	array( 'calendarCommentPostDelete', 'Comment Deletion' ),
	array( 'calendarCommentToggleVisibility', 'Comment Visibility Toggled' ),
);