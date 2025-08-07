<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v5.0.5
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @var		string
	 */
	private $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	@e string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		/* Set time out */
		@set_time_limit( 3600 );

		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
			case 'resetMemberAlbums':
				$this->resetMemberAlbums();
				break;
			case 'finish':
				$this->finish();
				break;
		}
		
		/* Workact is set in the function, so if it has not been set, then we're done. The last function should unset it. */
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Reset member albums without a parent and
	 * move them to a temporary one
	 * 
	 * @return	@e void
	 */
	public function resetMemberAlbums()
	{
		/* INIT */
		$options            = IPSSetUp::getSavedData('custom_options');
		$memberAlbumSelect	= $options['gallery'][42000]['membersAlbum'];
		$memberAlbumNew     = $options['gallery'][42000]['membersAlbumNew'];

		/* making a new one? */
		if ( !$memberAlbumSelect )
		{
			/* Update array */
			$save = array( 'album_name'				=> 'Temp global album for root member albums',
						   'album_name_seo'			=> IPSText::makeSeoTitle( 'Temp global album for root member albums' ),
						   'album_description'		=> "This is a temporary global album that holds the member albums that didn't have the proper parent album set. This album has NO permissions and is not visible from the public side, please move the albums in the proper location.",
						   'album_parent_id'		=> 0,
						   'album_is_public'		=> 0,
						   'album_is_global'		=> 1,
						   'album_g_container_only'	=> 0,
						   'album_allow_comments'	=> 0,
						   'album_g_approve_com'	=> 0,
						   'album_g_approve_img'	=> 0,
						   'album_sort_options'		=> serialize( array( 'key' => 'ASC', 'dir' => 'idate' ) ),
						   'album_detail_default'	=> 0,
						   'album_after_forum_id'	=> 0,
						   'album_watermark'		=> 0 );
			
			$this->DB->insert( 'gallery_albums_main', $save );
			$newAlbum = $this->DB->getInsertId();
			
			$memberAlbumSelect = $newAlbum['album_id'];
		}
		
		/* Now move the stuffs */
		if ( $memberAlbumSelect )
		{
			/* Tag this as member album for later update */
			$this->DB->update( 'gallery_albums_main', array( 'album_g_perms_thumbs' => 'member' ), 'album_id=' . intval( $memberAlbumSelect ) );
			
			/* Update albums and reset permissions/node tree.. */
			$this->DB->update( 'gallery_albums_main', array( 'album_parent_id' => $memberAlbumSelect ), 'album_parent_id=0 AND album_is_global=0' );
		}
		
		$this->registry->output->addMessage("Members Album set up correctly....");
		
		/* Continue with upgrade */
		$this->request['workact'] = 'finish';
	}
	
	/**
	 * Finish up conversion stuff
	 * 
	 * @return	@e void
	 */
	public function finish()
	{
		$this->registry->output->addMessage( "Upgrade completed");
		
		/* Last function, so unset workact */
		$this->request['workact'] = '';
	}
}