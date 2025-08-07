<?php

/* Revision: 2314 */

class public_topicslist_list_forums extends ipsCommand
{
	
	private $forums; 
	
	/**
	 * Main execution point
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
    {
	
   	$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_home' ), 'topicslist' );

	$ok_view = FALSE;
	
	foreach( explode( ',' , $this->settings['tl_genabled'] ) as $g )
	{
		if( $g == $this->memberData['member_group_id'] ) {
			$ok_view = TRUE;
			break;
			}
		}
		
		if ( !$ok_view ) {
		$code = "21TL1";
		$this->registry->getClass('output')->showError( $this->lang->words['tl_noperms'], $code, false ); 
	}

		#	GRAB FORUMS
		$this->forums = $this->grabF( $this->settings['tl_fenabled']);
		
		# Setting Header Maintitle
		$this->registry->output->setTitle( $this->lang->words['tl_viewingf'] );
		
		$this->registry->output->addNavigation( $this->lang->words['tl_viewingf'], "" );	
		
		# Skin Output
		$template = $this->registry->output->getTemplate('list')->forums( $this->forums, $this->settings['tl_fdesc'], substr( $this->settings['tl_fenabled'], 1, strlen( $fids ) -1 ) );

		$this->registry->getClass('output')->addContent( $template );
		$this->registry->getClass('output')->sendOutput();
		
		#	END OF FUNCTION
	}
	
	
	public function grabF( $fids )
	{
		
		# Get ALL forums (Thanks Invision)
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/class_forums.php', 'class_forums', 'forums' );
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir( 'forums' ) .'/sources/classes/forums/admin_forum_functions.php', 'admin_forum_functions', 'forums' );
						
		$aff = new $classToLoad( $this->registry );
		$aff->forumsInit();
		$rows = $aff->adForumsForumList(1);		
		# Finish	
		
		$this->DB->build( array( 	'select'	=>	"id, sub_can_post, parent_id, f.topics" . (( $this->settings['tl_fdesc'] == 1 ) ? ( ", f.description" ) : ( "" ) ),
									'from'	=>	array( 'forums'	=> 'f' ),
									'where'	=> 	"id IN (" . substr( $fids, 1, strlen( $fids ) -2 ) . ")", ) );
		$this->DB->execute();
		
		$temps = array();
		
		while ( $r = $this->DB->fetch() )
			{
				$temp[] = $r;
			}
			
		foreach( $temp as $row )
		{
			
			$temps[ $row['id'] ] = array( 	'ntopics'	=>	$row['topics'],
											'iscat'		=>	(( $this->settings['tl_fascat'] And $row['sub_can_post'] == 0 ) ? (1) : (( $row['parent_id'] == -1 ) ? ( 1 ) : ( 0 ) ) ),
											'desc'		=>	(( $this->settings['tl_fdesc'] == 1 ) ? ( $row['description'] ) : ( "" ) ), );
			
		}
		
		$where = ",";
		$gp_id = $this->DB->buildAndFetch( array( 'select' => 'g_perm_id', 'from' => 'groups', 'where' => "g_id={$this->memberData['member_group_id']}") );
		$this->DB->build( array( 	'select' 	=> "perm_type_id, perm_2",
								'from'		=> 'permission_index',
								'where'		=> "(app = 'forums' AND perm_type = 'forum' ) AND ( perm_type_id IN (" . substr( $fids, 1, strlen( $fids ) -2 ) . ")) AND ((perm_view = '*' || perm_view LIKE '%,{$gp_id['g_perm_id']},%' ) AND ( perm_2 = '*' ||  perm_2 LIKE '%,{$gp_id['g_perm_id']},%' || perm_2 = '' ) )",
								'order'		=> 'perm_type_id',  ) );

		$this->DB->execute();
		
		
		while ( $r = $this->DB->fetch() )
			{
				$pti[ ] = $r['perm_type_id'];
				$perm2[ $r['perm_type_id'] ] = $r['perm_2'];
			}
			
		foreach( $rows as $r ) 
		{
		 
			$name = str_ireplace( '--' , '', $r['1'] );
									
			$arr_temp[] = array(	'id'			=>	$r['0'],
									'name'		=>	$name,
									'desc'		=>	$temps[ $r['0'] ]['desc'],
									'ntopics'	=>	$temps[ $r['0'] ]['ntopics'],
									'iscat'		=>	$temps[ $r['0'] ]['iscat'],
													
									);
		}									
			
		foreach( $arr_temp as $t ){
			
			$to_copy = FALSE;
				
				foreach( $pti as $p ){
				
					if( $p == $t['id'] ){
						$to_copy = TRUE;
						break;
						}			
				}
				
				if( $to_copy ){
				
					if( $t['iscat'] ) $final_arr[ ] = $t;
					
					else
					{						
						
						if( $perm2[ $t['id'] ] === "*" ) $final_arr[ ] = $t;
							
						else {
							
								$perm_view = (( $perm2 != '' ) ? ( explode( ',', $perm2[ $t['id'] ] ) ) : ( FALSE ) );
								if( !$perm_view === FALSE ){
									foreach( $perm_view as $ptemp ){
									
										if( $ptemp == $gp_id['g_perm_id'] ){
										$final_arr[ ] = $t;
										break;
										}						
									}					
								}					
							}	
									
					}
				}
		}		
		
		return $final_arr;

		# END OF FUNCTION
		}
		
		
}

#***************************************#
# END OF FILE							#
#***************************************#
