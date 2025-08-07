<?php

/**
 * Permissions and Sessions
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

$_PERM_CONFIG = array( 'Membermap' );

class membermapPermMappingMembermap
{
    /**
    * Mapping of keys to columns
    *
    * @access	private
    * @var		array
    */
    private $mapping = array(
                            'view'		=> 'perm_view',
                            'add'		=> 'perm_2',
                            'edit'		=> 'perm_3',
                            'delete'	=> 'perm_4'
                            );

    /**
    * Mapping of keys to names
    *
    * @access	private
    * @var		array
    */
    private $perm_names = array(
                            'view'		=> 'View Map',
                            'add'		=> 'Add Location',
                            'edit'		=> 'Update Location',
                            'delete'	=> 'Delete Location'
                            );

    /**
    * Mapping of keys to background colors for the form
    *
    * @access	private
    * @var		array
    */
    private $perm_colors = array(
                            'view'		=> '#fff0f2',
                            'add'		=> '#effff6',
                            'edit'		=> '#fff0f2',
                            'delete'	=> '#edfaff'
                            );

    /**
    * Method to pull the key/column mapping
    *
    * @access	public
    * @return	array
    */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
    * Method to pull the key/name mapping
    *
    * @access	public
    * @return	array
    */
    public function getPermNames()
    {
        return $this->perm_names;
    }

    /**
    * Method to pull the key/color mapping
    *
    * @access	public
    * @return	array
    */
    public function getPermColors()
    {
        return $this->perm_colors;
    }

    /**
    * Method to set the permissions
    *
    * @access	public
    * @return	array
    */
    public function getPermItems()
    {
        ipsRegistry::DB()->build( array('select'	=> 'p.perm_view, p.perm_2, p.perm_3, p.perm_4',
                                        'from'		=> array( 'permission_index' => 'p' ),
                                        'where'		=> "app='membermap' AND perm_type='membermap'"));

        ipsRegistry::DB()->execute();

        $r = ipsRegistry::DB()->fetch();

        $return =  array(1 => array('title'		=> 'Member Map',
									'perm_view'	=> $r['perm_view'],
									'perm_2'	=> $r['perm_2'],
									'perm_4'	=> $r['perm_4'],
									'perm_3'	=> $r['perm_3']));

        return $return;
    }
}

/**
 * IP.Board Member Map - Public Sessions, for online list.
 * Thanks to Michael McCune (IPB.Dev) for contributing the following code
 *
 * @copyright   Copyright (C) 2013, Stuart Silvester
 * @author      Stuart Silvester
 * @package     Member Map
 * @version     1.0.8
 */

class publicSessions__membermap
{
    public function getSessionVariables()
    {
        /* Return */
        return array();
    }

    public function parseOnlineEntries( $rows )
    {
		ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_map' ), 'membermap' );
        /* Got rows? */
        if ( !is_array( $rows ) || !count( $rows ) )
        {
            return $rows;
        }
        /* Loopy loo */
        foreach ( $rows as $row )
        {
            /* In some other app? */
            if ( $row['current_appcomponent'] != 'membermap' )
            {
                $final[ $row['id'] ] = $row;
                continue;
            }
            /* Guess not... */
            $row['where_line'] = ipsRegistry::getClass( 'class_localization' )->words['membermap_viewing_app'];
            $row['where_link'] = 'app=membermap';
            /* Add to our array */
            $final[ $row['id'] ] = $row;
        }
        /* Return */
        return $final;
    }
}
?>