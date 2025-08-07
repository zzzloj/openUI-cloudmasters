<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v2.5.4
 * Define data hook locations (Downloads)
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
	array( 'downloadAddFile', 'Add Download' ),
	array( 'downloadEditFile', 'Edit Download' ),
	array( 'downloadUpdateCategoryInfo', 'Update Downloads Category Info' ),
	array( 'downloadRebuildStatsCache', 'Rebuild Downloads Statistics Cache' ),
	array( 'downloadAddFileComment', 'Add File Comment' ),
	array( 'downloadEditFileComment', 'Edit File Comment' ),
	array( 'downloadCommentAddPostSave', 'Add File Comment (post save)' ),
	array( 'downloadCommentEditPostSave', 'Edit File Comment (post save)' ),
	array( 'downloadCommentPostDelete', 'Delete File Comment (post delete)' ),
	array( 'downloadCommentToggleVisibility', 'Toggle File Comment Visibility (post delete)' ),
);