<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v5.0.5
 * Define data hook locations (Gallery)
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
	array( 'galleryPreAddImage', 'Add Image (Pre DB)' ),
	array( 'galleryPostAddImage', 'Add Image (Post DB)' ),
	array( 'galleryEditImage', 'Edit Image' ),
	array( 'galleryRebuildStatsCache', 'Rebuild Gallery Statistics Cache' ),
	array( 'galleryAddImageComment', 'Add Image Comment' ),
	array( 'galleryEditImageComment', 'Edit Image Comment' ),
	array( 'galleryCommentAddPostSave', 'Add Image Comment (post save)' ),
	array( 'galleryCommentEditPostSave', 'Edit Image Comment (post save)' ),
	array( 'galleryCommentPostDelete', 'Delete Image Comment (post delete)' ),
	array( 'galleryCommentToggleVisibility', 'Toggle Image Comment Visibility (post delete)' ),
	
);