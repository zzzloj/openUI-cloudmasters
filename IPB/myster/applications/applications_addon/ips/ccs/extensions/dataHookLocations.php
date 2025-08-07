<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Define data hook locations (Calendar)
 * Last Updated: $Date: 2011-01-26 19:04:21 -0500 (Wed, 26 Jan 2011) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @version		$Rev: 7653 $
 */

$dataHookLocations = array(

	/* Article and record hook points */
	array( 'ccsPreAdd', 'Add Record (Form Shown)' ),
	array( 'ccsPreSave', 'Add Record' ),
	array( 'ccsPostSave', 'Add Record (Post Save)' ),
	array( 'ccsPrePromote', 'Promote Article (Form Shown)' ),
	array( 'ccsPrePromoteSave', 'Promote Article' ),
	array( 'ccsPostPromoteSave', 'Promote Article (Post Save)' ),
	array( 'ccsPreEdit', 'Edit Record (Form Shown)' ),
	array( 'ccsPreEditSave', 'Edit Record' ),
	array( 'ccsPostEditSave', 'Edit Record (Post Save)' ),
	array( 'ccsPreDelete', 'Delete Record' ),
	array( 'ccsPostDelete', 'Delete Record (Post Delete)' ),
	array( 'ccsPreLock', 'Lock Record' ),
	array( 'ccsPostLock', 'Lock Record (Post Lock)' ),
	array( 'ccsPreUnlock', 'Unlock Record' ),
	array( 'ccsPostUnlock', 'Unlock Record (Post Unlock)' ),
	array( 'ccsPrePin', 'Pin Record' ),
	array( 'ccsPostPin', 'Pin Record (Post Pin)' ),
	array( 'ccsPreUnpin', 'Unpin Record' ),
	array( 'ccsPostUnpin', 'Unpin Record (Post Unpin)' ),
	array( 'ccsPreApprove', 'Approve/Unhide Record' ),
	array( 'ccsPostApprove', 'Approve/Unhide Record (Post Approve)' ),
	array( 'ccsPreUnapprove', 'Hide Record' ),
	array( 'ccsPostUnapprove', 'Hide Record (Post Unapprove)' ),

	/* Comment hook points */
	array( 'ccsAddComment', 'Add Comment' ),
	array( 'ccsEditComment', 'Edit Comment' ),
	array( 'ccsCommentAddPostSave', 'Add Comment (Post Save)' ),
	array( 'ccsCommentEditPostSave', 'Edit Comment (Post Save)' ),
	array( 'ccsCommentPostDelete', 'Comment Deletion' ),
	array( 'ccsCommentToggleVisibility', 'Comment Visibility Toggled' ),
);