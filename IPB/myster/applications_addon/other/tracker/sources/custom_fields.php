<?php

/**
* Tracker 2.1.0
* 
* Custom Fields Library
* Last Updated: $Date: 2012-05-27 15:41:13 +0100 (Sun, 27 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1369 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'init.php'.";
	exit();
}

class customFields
{
	/**
	* Registry object
	*
	* @access    protected
	* @var        object
	*/
	protected $registry;

	/**
	* Database object
	*
	* @access    protected
	* @var        object
	*/
	protected $DB;

	/**
	* Settings object
	*
	* @access    protected
	* @var        object
	*/
	protected $settings;

	/**
	* Request object
	*
	* @access    protected
	* @var        object
	*/
	protected $request;

	/**
	* Language object
	*
	* @access    protected
	* @var        object
	*/
	protected $lang;

	/**
	* Member object
	*
	* @access    protected
	* @var        object
	*/
	protected $member;

	/**
	* Cache object
	*
	* @access    protected
	* @var        object
	*/
	protected $cache;

	/**
	* IssueID object
	*
	* @access    public
	* @var        int
	*/
	public $issueID;

	/**
	* Init object
	*
	* @access    public
	* @var        boolean
	*/
	public $init         = 0;

	/**
	* Permissions built object
	*
	* @access    public
	* @var        boolean
	*/
	public $permsBuilt   = FALSE;

	/**
	* InFields object
	*
	* @access    public
	* @var        array
	*/
	public $inFields    = array();

	/**
	* OutFields object
	*
	* @access    public
	* @var        array
	*/
	public $outFields   = array();

	/**
	* OutChosen object
	*
	* @access    public
	* @var        array
	*/
	public $outChosen   = array();

	/**
	* TmpFields object
	*
	* @access    public
	* @var        array
	*/
	public $tmpFields   = array();

	/**
	* CacheData object
	*
	* @access    public
	* @var        array
	*/
	public $cacheData   = array();

	/**
	* IssueData object
	*
	* @access    public
	* @var        array
	*/
	public $issueData   = array();

	/**
	* FieldNames object
	*
	* @access    public
	* @var        array
	*/
	public $fieldNames  = array();

	/**
	* FieldDesc object
	*
	* @access    public
	* @var        array
	*/
	public $fieldDesc   = array();

	/**
	* KillHTML object
	*
	* @access    public
	* @var        int
	*/
	public $killHTML    = 1;

	/**
	* ErrorFields object
	*
	* @access    public
	* @var        array
	*/
	public $errorFields = array( 'toobig' => array(), 'empty' => array(), 'invalid' => array() );

	/**
	* CInput object
	*
	* @access    public
	* @var        array
	*/
	public $input        = array();

	/**
	* PermIDArray object<br>
	* Array is populated with perm_ids for the member's credentials:<br>
	* 1: Project Owner<br>
	* 2: Moderators<br>
	* 3: Issue Creator (new issue only)<br>
	* 4: Public (default)
	*
	* @access    public
	* @var        array
	*/
	public $permIDArray = array(4);

	/**
	* Constructor
	*
	* @access    public
	* @param     object        ipsRegistry reference
	* @return    void
	*/
	public function __construct( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   = $registry;
		$this->DB         = $this->registry->DB();
		$this->settings   = $this->registry->settings();
		$this->request    = $this->registry->request();
		$this->lang       = $this->registry->getClass('class_localization');
		$this->member     = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	/**
	* Sets up the library for use throughout Tracker
	*
	* @param 	null
	* @return 	null
	*/
	public function initData()
	{
		if ( ! $this->init )
		{
			/* Cache data... */
			if ( ! $this->permsBuilt && ! count( $this->cacheData ) > 0 )
			{
				$this->DB->build( array( 'select' => '*', 'from' => 'tracker_fields_data', 'order' => 'field_order' ) );
				$this->DB->execute();

				while ( $r = $this->DB->fetch() )
				{
					$this->cacheData[ $r['field_id'] ] = $r;
				}
			}

			/* Get names... */
			if ( is_array($this->cacheData) and count($this->cacheData) )
			{
				foreach( $this->cacheData as $id => $data )
				{
					$this->fieldNames[ $id ] = $data['field_title'];
					$this->fieldDesc[ $id ]  = $data['field_desc'];
				}
			}
		}

		/* Clean up on aisle #4 */
		$this->outFields = array();
		$this->tmpFields = array();
		$this->outChosen = array();

		/* Parse into in fields */
		if ( is_array($this->cacheData) and count( $this->cacheData ) )
		{
			foreach( $this->cacheData as $id => $data )
			{
				$this->inFields[ $id ] = isset( $this->issueData['field_' . $id ] ) ? $this->issueData['field_' . $id ] : NULL;
			}
		}

		$this->init = 1;
	}

	/**
	* Sets up the custom field for saving
	*
	* @param 	string	Field name
	* @return 	null
	*/
	public function parseToSave( $post='field_' )
	{
		if ( is_array( $this->cacheData ) and count( $this->cacheData ) )
		{
			foreach( $this->cacheData as $i => $row )
			{
				$this->tmpFields[ $i ] = $row;
			}
		}

		/* Grab editable fields... */
		if ( is_array( $this->tmpFields ) and count( $this->tmpFields ) )
		{
			foreach( $this->tmpFields as $i => $row )
			{
				/* Too big? */
				if ( $this->cacheData[ $i ]['field_max_input'] and strlen( $this->request[ $post . $i ] ) > $this->cacheData[ $i ]['field_max_input'] )
				{
					$this->errorFields['toobig'][] = $row;
				}

				/* Required and NULL? */
				if ( $this->cacheData[ $i ]['field_not_null'] and trim( $this->request[ $post . $i ] ) == '' )
				{
					$this->errorFields['empty'][] = $row;
				}

				/* Invalid format? */
				if ( trim( $this->cacheData[ $i ]['field_input_format'] ) and $this->request[ $post . $i ] )
				{
					$regex = str_replace( 'n', '\\d', preg_quote( $this->cacheData[ $i ]['field_input_format'], "#" ) );
					$regex = str_replace( 'a', '\\w', $regex );

					if ( ! preg_match( '#^' . $regex . '$#i', trim( $this->request[ $post . $i ] ) ) )
					{
						$this->errorFields['invalid'][] = $row;
					}
				}

				$this->outFields[ $post . $i ] = $this->formatTextToSave( $this->request[ $post . $i ] );
			}
		}
	}

	/**
	* Sets up the custom field for viewing
	*
	* @param 	integer		Check issue format
	* @return 	null
	*/
	public function parseToView( $checkIssueFormat=0 )
	{
		if ( is_array( $this->cacheData ) and count( $this->cacheData ) )
		{
			foreach( $this->cacheData as $i => $row )
			{
				$this->tmpFields[ $i ] = $row;
			}
		}

		$this->parseOutFields( 'view' );
	}

	/**
	* Sets up the custom field for editing
	*
	* @param 	null
	* @return 	null
	*/
	public function parseToEdit()
	{
		if ( is_array( $this->cacheData ) and count( $this->cacheData ) )
		{
			foreach( $this->cacheData as $i => $row )
			{
				$this->tmpFields[ $i ] = $row;
			}
		}

		$this->parseOutFields( 'edit' );
	}

	/**
	* Sets up the custom field for viewing
	*
	* @param 	string	Where are we (edit/view)
	* @return 	null
	*/
	public function parseOutFields( $type='view' )
	{
		foreach( $this->tmpFields as $i => $row )
		{
			if ( $row['field_type'] == 'drop' )
			{ 
				$carray = explode( "\n", trim( $row['field_content'] ) );

				if ( count( $carray ) == 0 )
				{
					$carray = explode( '|', trim( $row['field_content'] ) );
				}

				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );

					$ov = trim( $value[0] );
					$td = trim( $value[1] );

					if ( $type == 'view' )
					{
						if ( $this->inFields[ $row['field_id'] ] == $ov )
						{
							$this->outFields[ $row['field_id'] ] = $td;
							$this->outChosen[ $row['field_id'] ] = $ov;
						}
						else if ( $this->inFields[ $row['field_id'] ] == '' )
						{
							$this->outFields[ $row['field_id'] ] = '';
							$this->outChosen[ $row['field_id'] ] = '';
						}
					}
					else if ( $type == 'edit' )
					{
						if ( ( $this->inFields[ $row['field_id'] ] == $ov and $this->inFields[ $row['field_id'] ] ) OR isset( $this->input[ 'field_' . $row['field_id'] ] ) )
						{
							$this->outFields[ $row['field_id'] ] .= "<option value='$ov' selected='selected'>$td</option>\n";
						}
						else
						{
							$this->outFields[ $row['field_id'] ] .= "<option value='$ov'>$td</option>\n";
						}
					}
				}
			}
			else
			{
				if ( $type == 'view' )
				{
					$this->outFields[ $row['field_id'] ] = $this->makeSafeForView( $this->inFields[ $row['field_id'] ] );
				}
				else
				{
					if ( isset( $this->input[ 'field_' . $row['field_id'] ] ) )
					{
						$this->outFields[ $row['field_id'] ] = $this->makeSafeForForm( $this->input[ 'field_' . $row['field_id'] ] );
					}
					else
					{
						$this->outFields[ $row['field_id'] ] = $this->makeSafeForForm( $this->inFields[ $row['field_id'] ] );
					}
				}
			}
		}
	}

	/**
	* Formats the text for saving
	*
	* @param 	text	Custom field input		
	* @return 	text	Converted text
	*/
	public function formatTextToSave( $t )
	{
		$t = str_replace( '<br>'  , "\n", $t );
		$t = str_replace( '<br />', "\n", $t );
		$t = str_replace( '&#39;' , "'" , $t );

		if ( @get_magic_quotes_gpc() )
		{
			$t = stripslashes( $t );
		}

		return $t;
	}

	/**
	* Formats the text for editing
	*
	* @param 	text	Custom field input		
	* @return 	text	Converted text
	*/
	public function formatContentForEdit( $c )
	{
		return str_replace( '|', "\n", $c );
	}

	/**
	* Formats the text for display
	*
	* @param 	text	Field name	
	* @return 	text	Converted text
	*/
	public function formatFieldForIssueView( $i )
	{
		$out = $this->outFields[ $i ];

		$tmp = $this->cacheData[ $i ]['field_issue_format'];

		$tmp = str_replace( '{title}', $this->fieldNames[ $i ], $tmp );
		$tmp = str_replace( '{key}', isset( $this->outChosen[ $i ] ) ? $this->outChosen[ $i ] : '' , $tmp );
		$tmp = str_replace( '{content}', $out, $tmp );

		return $tmp;
	}

	/**
	* Formats the text for saving
	*
	* @param 	text	Custom field input	
	* @return 	text	Converted text
	*/
	public function formatContentForSave( $c )
	{
		$c = str_replace( "\r"   , "\n", $c );
		$c = str_replace( "&#39;", "'" , $c );
		return str_replace( "\n", '|', str_replace( "\n\n", "\n", trim( $c ) ) );
	}

	/**
	* Makes the text safe when viewing the form
	*
	* @param 	text	Text input	
	* @return 	text	Converted text
	*/
	public function makeSafeForForm( $t )
	{
		return str_replace( "'", "&#39;", $t );
	}

	/**
	* Makes the text safe when viewing other areas
	*
	* @param 	text	Text input	
	* @return 	text	Converted text
	*/
	public function makeSafeForView( $t )
	{
		if ( $this->kill_html )
		{
			$t = htmlspecialchars( $t );
			$t = preg_replace( "/&amp;#([0-9]+);/s", "&#\\1;", $t );
		}

		$t = nl2br( $t );

		return $t;
	}

	/**
	* Compares the custom field's permissions to the user's permissions
	* see if the user has credentials for the permission being checked
	*
	* @param 	text	Text array of permissions
	* @return 	boolean
	*/
	function checkPermissions($forumPerm="")
	{
		if ( ! is_array( $this->permIDArray ) )
		{
			return FALSE;
		}

		if ( $forumPerm == "" )
		{
			return FALSE;
		}
		else if ( $forumPerm == '*' )
		{
			return TRUE;
		}
		else
		{
			$forumPermArray = explode( ",", $forumPerm );

			foreach( $this->permIDArray as $uID )
			{
				if ( in_array( $uID, $forumPermArray ) )
				{
					return TRUE;
				}
			}

			/* Still here? Not a match then. */
			return FALSE;
		}
	}

	/**
	* Compares the custom field's permissions to the user's permissions
	* see if the user has credentials for the permission being checked
	*
	* @param 	text	Text array of permissions
	* @return 	boolean
	*/
	function checkPublicPermissions($forumPerm="")
	{
		if ( ! is_array( $this->permIDArray ) )
		{
			return FALSE;
		}

		if ( $forumPerm == "" )
		{
			return FALSE;
		}
		else if ( $forumPerm == '*' )
		{
			return TRUE;
		}
		else
		{
			$forumPermArray = explode( ",", $forumPerm );

			if ( in_array( 4, $forumPermArray ) )
			{
				return TRUE;
			}

			/* Still here? Not a match then. */
			return FALSE;
		}
	}

	/**
	* Find the key of a field's dropdown options in order to return
	* the key's value.
	*
	* @param 	int		ID number of the custom field
	* @param 	string	test of the key to find
	* @return 	string
	*/
	public function getDropdownValue( $id, $key )
	{
		if ( is_array( $this->cacheData[$id] ) && $this->cacheData[$id]['field_type'] == "drop" )
		{
			$carray = explode( "\n", trim( $this->cacheData[$id]['field_content'] ) );

			if ( count( $carray ) == 0 )
			{
				$carray = explode( '|', trim( $this->cacheData[$id]['field_content'] ) );
			}

			if ( count( $carray) > 0 )
			{
				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );
	
					$ov = trim( $value[0] );
					$td = trim( $value[1] );
	
					if ( $ov == $key )
					{
						return $td;
					}
				}
			}
		}

		return "";
	}

	/**
	* Makes sure the key value passed is valid
	*
	* @param 	int		ID number of the custom field
	* @param 	string	test of the key to find
	* @return 	string
	*/
	public function testDropdownKey( $id, $key )
	{
		if ( is_array( $this->cacheData[$id] ) && $this->cacheData[$id]['field_type'] == "drop" )
		{
			$carray = explode( "\n", trim( $this->cacheData[$id]['field_content'] ) );

			if ( count( $carray ) == 0 )
			{
				$carray = explode( '|', trim( $this->cacheData[$id]['field_content'] ) );
			}

			if ( count( $carray) > 0 )
			{
				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );
	
					$ov = trim( $value[0] );
					$td = trim( $value[1] );
	
					if ( $ov == $key )
					{
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	/**
	* Return a dropdown select for filtering
	* the key's value.
	*
	* @param 	int		ID number of the custom field
	* @param 	string 	Selected key or '' for all
	* @return 	string
	*/
	public function getFieldDropdown( $id, $selected="" )
	{
		$values = array();
		
		if ( is_array( $this->cacheData[$id] ) && $this->cacheData[$id]['field_type'] == "drop" )
		{
			$values['all'] = "<option value='all'" . ($selected == "" ? " selected='selected'>" : ">") . $this->lang->words['bt_cf_dd_all_pre'] . strtoupper($this->cacheData[$id]['field_title_plural']) . $this->lang->words['bt_cf_dd_all_post'] . "</option>";

			$carray = explode( "\n", trim( $this->cacheData[$id]['field_content'] ) );

			if ( count( $carray ) == 0 )
			{
				$carray = explode( '|', trim( $this->cacheData[$id]['field_content'] ) );
			}

			if ( count( $carray) > 0 )
			{
				foreach( $carray as $entry )
				{
					$value = explode( '=', $entry );
	
					$ov = trim( $value[0] );
					$td = trim( $value[1] );
	
					if ( $selected == $ov )
					{
						$values[ $ov ] = "<option value='$ov' selected='selected'>$td</option>\n";
					}
					else
					{
						$values[ $ov ] = "<option value='$ov'>$td</option>\n";
					}
				}
			}
		}

		return $values;
	}
}

?>