<?php

/**
* Tracker 2.1.0
* 
* Abstract post class
* Last Updated: $Date: 2012-12-16 12:38:12 +0000 (Sun, 16 Dec 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Core
* @link			http://ipbtracker.com
* @version		$Revision: 1398 $
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tracker_core_post extends iptCommand
{
	protected $editLibrary;
	protected $newLibrary;
	protected $replyLibrary;

	/**
	 * Initial function.  Called by execute function in ipsCommand 
	 * following creation of this class
	 *
	 * @param ipsRegistry $registry the IPS Registry
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function doExecute( ipsRegistry $registry )
	{

	}
	
	public function editLibrary()
	{
		$out = NULL;

		if ( ! is_object( $this->editLibrary ) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/post_edit.php', 'tracker_core_post_edit', 'tracker' );

			$this->editLibrary = new $classToLoad();
			$this->editLibrary->execute( $this->registry );
		}

		$out = $this->editLibrary;

		return $out;
	}
	
	public function newLibrary()
	{
		$out = NULL;

		if ( ! is_object( $this->newLibrary ) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/post_new.php', 'tracker_core_post_new', 'tracker' );

			$this->newLibrary = new $classToLoad();
			$this->newLibrary->execute( $this->registry );
		}

		$out = $this->newLibrary;

		return $out;
	}
	
	public function replyLibrary()
	{
		$out = NULL;

		if ( ! is_object( $this->replyLibrary ) )
		{
			$classToLoad  = IPSLib::loadLibrary( IPSLib::getAppDir('tracker') . '/sources/classes/post_reply.php', 'tracker_core_post_reply', 'tracker' );

			$this->replyLibrary = new $classToLoad();
			$this->replyLibrary->execute( $this->registry );
		}

		$out = $this->replyLibrary;

		return $out;
	}
}

abstract class tracker_core_post_main extends iptCommand
{
	protected $email;
	protected $parser;
	protected $class_attach;

	public static $project    = array();
	public static $instance;

	public $output     = '';
	public $nav        = '';
	public $title      = '';
	public $issue      = array();
	public $post       = array();
	public $obj        = array();

	public $can_upload = 0;
	public $form_hash  = '';
	public $post_key   = '';
	
	/**
	 * Internal post array when editing
	 *
	 * @var		array
	 */
	protected $_originalPost = array();
	
	/**
	 * Internal __call array
	 *
	 * @var		array
	 */
	protected $_internalData = array();
	
	/**
	 * Allowed items to be saved in the get/set array
	 *
	 * @var		array
	 */
	protected $_allowedInternalData = array( 'Author',
										   'ProjectID',
										   'IssueID',
										   'IssueTitle',
										   'PostID',
										   'PostContent',
										   'PostContentPreFormatted',
										   'IssueState',
										   'Settings',
										   'IsPreview',
										   'ModOptions',
										   'IsAjax' );
	
	/**
	 * Forum Data
	 *
	 * @var 	array
	 */
	protected $_projectData = array();
	
	/**
	 * Topic Data
	 *
	 * @var 	array
	 */
	protected $_issueData = array();
	
	/**
	 * Post Data
	 *
	 * @var 	array
	 */
	protected $_postData = array();
	
	/**
	 * Bypass all permission checks
	 *
	 * Use with care, this will allow the class to be used within an API
	 * 
	 * @var		boolean
	 */
	protected $_bypassPermChecks = false;
	
	public abstract function showForm();
	public abstract function savePost();
	protected abstract function processPost();

	public function doExecute( ipsRegistry $registry )
	{
		$this->cache = $registry->cache();
	}
	
	public static function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/**
	 * Magic Call method
	 *
	 * @param	string	Method Name
	 * @param	mixed	Method arguments
	 * @return	mixed
	 * Exception codes:
	 */
	public function __call( $method, $arguments )
	{
		$firstBit = substr( $method, 0, 3 );
		$theRest  = substr( $method, 3 );
	
		if ( in_array( $theRest, $this->_allowedInternalData ) )
		{
			if ( $firstBit == 'set' )
			{
				if ( $theRest == 'Author' )
				{
					if ( is_array( $arguments[0] ) )
					{
						$this->_internalData[ $theRest ] = $arguments[0];
					}
					else
					{
						if( $arguments[0] )
						{
							/* Set up moderator stuff, too */
							$this->_internalData[ $theRest ] = IPSMember::setUpModerator( IPSMember::load( intval( $arguments[0] ), 'all' ) );

							/* And ignored users */
							$this->_internalData[ $theRest ]['ignored_users'] = array();
							
							$this->registry->DB()->build( array( 'select' => '*', 'from' => 'ignored_users', 'where' => "ignore_owner_id=" . intval( $arguments[0] ) ) );
							$this->registry->DB()->execute();
				
							while( $r = $this->registry->DB()->fetch() )
							{
								$this->_internalData[ $theRest ]['ignored_users'][] = $r['ignore_ignore_id'];
							}
						}
						else
						{
							$this->_internalData[ $theRest ] = IPSMember::setUpGuest();
						}
					}
					
					if( $this->_internalData['Author']['mgroup_others'] )
					{
						$_others	= explode( ',', IPSText::cleanPermString( $this->_internalData['Author']['mgroup_others'] ) );
						$_perms		= array();
						
						foreach( $_others as $_other )
						{
							$_perms[]	= $this->caches['group_cache'][ $_other ]['g_perm_id'];
						}
						
						if( count($_perms) )
						{
							$this->_internalData['Author']['g_perm_id']	= $this->_internalData['Author']['g_perm_id'] . ',' . implode( ',', $_perms );
						}
					}
				}
				else
				{
					$this->_internalData[ $theRest ] = $arguments[0];
					return TRUE;
				}
			}
			else
			{
				if ( ( $theRest == 'Author' OR $theRest == 'Settings' OR $theRest == 'ModOptions' ) AND isset( $arguments[0] ) )
				{
					return isset( $this->_internalData[ $theRest ][ $arguments[0] ] ) ? $this->_internalData[ $theRest ][ $arguments[0] ] : '';
				}
				else
				{
					return isset( $this->_internalData[ $theRest ] ) ? $this->_internalData[ $theRest ] : '';
				}
			}
		}
		else
		{
			switch( $method )
			{
				case 'setProjectData':
					$this->_projectData = $arguments[0];
				break;
				case 'setPostData':
					$this->_postData = $arguments[0];
				break;
				case 'setIssueData':
					$this->_issueData = $arguments[0];
				break;
				case 'getProjectData':
					if ( !empty($arguments[0]) )
					{
						return $this->_projectData[ $arguments[0] ];
					}
					else
					{
						return $this->_projectData;
					}
				break;
				case 'getPostData':
					if ( !empty($arguments[0]) )
					{
						return $this->_postData[ $arguments[0] ];
					}
					else
					{
						return $this->_postData;
					}
				break;
				case 'getIssueData':
					if ( !empty($arguments[0]) )
					{
						return $this->_issueData[ $arguments[0] ];
					}
					else
					{
						return $this->_issueData;
					}
				break;
				case 'getPostError':
					return isset($this->lang->words[ $this->_postErrors ]) ? $this->lang->words[ $this->_postErrors ]: $this->_postErrors;
				break;
			}
		}
	}

	/**
	 * Check out the tracker whacker
	 *
	 * @param	integer	$iid
	 * @return	@e void
	 */
	protected function addIssueToTracker( $iid=0 )
	{
		if ( ! $iid )
		{
			return;
		}
		
		if ( $this->getAuthor('member_id') AND $this->getSettings('enableTracker') == 1 )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );/*noLibHook*/
			$_like = classes_like::bootstrap( 'tracker','issues' );
			
			$_like->add( $iid, $this->getAuthor('member_id'), array( 'like_notify_do' => 1, 'like_notify_meta' => $iid, 'like_notify_freq' => $this->getAuthor('auto_track') ) );
		}
	}
	
	/**
	 * Set the bypass permission flag
	 *
	 * @param	boolean
	 * @return	@e void
	 */
	public function setBypassPermissionCheck( $bool=false )
	{
		$this->_bypassPermChecks = ( $bool === true ) ? true : false;
	}
	
	/**
	 * Set a post error remotely
	 *
	 * @param	string		Error
	 * @return	@e void
	 */
	public function setPostError( $error )
	{
		$this->_postErrors	= $error;
	}

	/**
	 * Sets the topic title.
	 * You *must* pass a raw GET or POST value. ie, a value that has not been cleaned by parseCleanValue
	 * as there are unicode checks to perform. This function will test those and clean the topic title for you
	 *
	 * @param	string		Topic Title
	 */
	public function setIssueTitle( $issueTitle )
	{ 
		if ( $issueTitle )
		{
			$this->_issueTitle = $issueTitle;

			/* Clean */
			if( $this->settings['etfilter_shout'] )
			{
				if( function_exists('mb_convert_case') )
				{
					if( in_array( strtolower( $this->settings['gb_char_set'] ), array_map( 'strtolower', mb_list_encodings() ) ) )
					{
						$this->_issueTitle = mb_convert_case( $this->_issueTitle, MB_CASE_TITLE, $this->settings['gb_char_set'] );
					}
					else
					{
						$this->_issueTitle = ucwords( strtolower($this->_issueTitle) );
					}
				}
				else
				{
					$this->_issueTitle = ucwords( strtolower($this->_issueTitle) );
				}
			}
			
			$this->_issueTitle = IPSText::parseCleanValue( $this->_issueTitle );
			//$this->_issueTitle = $this->cleanIssueTitle( $this->_issueTitle );
			$this->_issueTitle = IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->_issueTitle );
			
			/* Unicode test */
			if ( IPSText::mbstrlen( $issueTitle ) > $this->settings['topic_title_max_len'] )
			{
				$this->_postErrors = 'topic_title_long';
			}
		
			if ( (IPSText::mbstrlen( IPSText::stripslashes( $issueTitle ) ) < 2) or ( ! $this->_issueTitle )  )
			{
				$this->_postErrors = 'no_topic_title';
			}		
		}
	}

	public function register()
	{
		$classToLoad		= IPSLib::loadLibrary( IPS_ROOT_PATH . 'api/tracker/api_tracker_issues.php', 'apiTrackerIssues' );
		$this->api			= new $classToLoad();

		//-----------------------------------------
		// Load and config the post parser
		//-----------------------------------------
		IPSText::getTextClass( 'bbcode' )->allow_update_caches = 1;
		IPSText::getTextClass( 'bbcode' )->bypass_badwords = intval($this->memberData['g_bypass_badwords']);
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'tracker_issues';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberData['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];

		//-----------------------------------------
		// Load and config the std/rte editors
		//-----------------------------------------

		/* Load editor stuff */
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/editor/composite.php', 'classes_editor_composite' );
		$this->editor = new $classToLoad();

		/* Load tagging stuff */
		if ( ! $this->registry->isClassLoaded('trackerTags') )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/tags/bootstrap.php' );/*noLibHook*/
			$this->registry->setClass( 'trackerTags', classes_tags_bootstrap::run( 'tracker', 'issues' ) );
		}
		
		//-----------------------------------------
		// Compile the language file
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ) );
		$this->registry->class_localization->loadLanguageFile( array( 'public_post' ), 'forums' );
	}

	/*-------------------------------------------------------------------------*/
	// Build permissions
	/*-------------------------------------------------------------------------*/

	protected function buildPermissions()
	{
		$this->tracker->projects()->createPermShortcuts( self::$project['project_id'] );
		$this->tracker->moderators()->buildModerators( self::$project['project_id'] );

		/* FIELDS-NEW */
		$this->tracker->fields()->initialize( self::$project['project_id'] );

		//-----------------------------------------
		// Can we upload files?
		//-----------------------------------------

		if ( $this->tracker->projects()->checkPermission( 'upload', self::$project['project_id'] ) )
		{
			if ( $this->memberData['g_attach_max'] != -1 )
			{
				$this->can_upload = 1;
			}
		}
		
		// Mod perms
		$this->member->trackerProjectPerms = $this->member->tracker[self::$project['project_id']];
	}

	/*-------------------------------------------------------------------------*/
	// Show post preview
	/*-------------------------------------------------------------------------*/

	protected function showPostPreview( $t="", $post_key='' )
	{
		IPSText::getTextClass( 'bbcode' )->parse_html    = (intval($this->request['post_htmlstatus']) AND self::$project['use_html'] AND $this->memberData['g_dohtml']) ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br   = $this->request['post_htmlstatus'] == 2 ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_smilies = intval($this->request['enableemo']);
		IPSText::getTextClass( 'bbcode' )->parse_bbcode  = self::$project['use_ibc'];

		# Make sure we have the pre-display look
		$t = IPSText::getTextClass('bbcode')->preDisplayParse( $t );

		//-----------------------------------------
		// Attachments?
		//-----------------------------------------

		$attach_pids = array();

		preg_match_all( "#\[attachment=(\d+?)\:(?:[^\]]+?)\]#is", $t, $match );

		if ( is_array( $match[0] ) and count( $match[0] ) )
		{
			for ( $i = 0 ; $i < count( $match[0] ) ; $i++ )
			{
				if ( intval($match[1][$i]) == $match[1][$i] )
				{
					$attach_pids[ $match[1][$i] ] = $match[1][$i];
				}
			}
		}

		//-----------------------------------------
		// Got any?
		//-----------------------------------------

		if ( count( $attach_pids ) )
		{
			if ( ! is_object( $this->class_attach ) )
			{
				//-----------------------------------------
				// Grab render attach class
				//-----------------------------------------

				$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
				$this->class_attach	= new $classToLoad( $this->registry );
				$this->class_attach->attach_post_key =  $post_key;
			}

			$this->class_attach->type  = 'tracker';
			$this->class_attach->init();

			$t = $this->class_attach->renderAttachments( $t, $attach_pids);			
		}

		//-----------------------------------------
		// Looks hacky don't it, well it is but works for ipb
		//-----------------------------------------
		if ( is_array($t) )
		{
			return $t[0]['html'] . $t[0]['attachmentHtml'];	
		}
		
		return $t;
	}

	/*-------------------------------------------------------------------------*/
	// Get navigation
	/*-------------------------------------------------------------------------*/

	protected function showPostNavigation()
	{
		$this->tracker->projects()->createBreadcrumb( self::$project['project_id'] );

		/* FIELDS-NEW */
		$this->tracker->fields()->addNavigation( $this );

		/* Include Syntax Highlight */
		$this->tracker->output .= $this->registry->getClass('output')->getTemplate('global')->include_highlighter();
	}

	/**
	 * Format Post: Converts BBCode, smilies, etc
	 *
	 * @param	string	Raw Post
	 * @return	string	Formatted Post
	 * @author	MattMecham
	 */
	public function formatPost( $postContent )
	{
		/* Set HTML Flag for the editor. Bug #19796 */
		$this->editor->setAllowHtml( intval($this->request['post_htmlstatus']) AND self::$project['use_html'] AND $this->getAuthor('g_dohtml') ? 1 : 0 );

		$postContent = $this->editor->process( $postContent );

		//-----------------------------------------
		// Parse post
		//-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->parse_smilies    = $this->getSettings('enableEmoticons');
		IPSText::getTextClass( 'bbcode' )->parse_html    	= (intval($this->request['post_htmlstatus']) AND self::$project['use_html'] AND $this->getAuthor('g_dohtml')) ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br		= intval($this->request['post_htmlstatus']) == 2 ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode    	= self::$project['use_ibc'];
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'tracker';
		
		$postContent = IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent );
		
		# Make this available elsewhere without reparsing, etc
		$this->setPostContentPreFormatted( $postContent );
		
		return $postContent;
	}
	
	/*-------------------------------------------------------------------------*/
	// compile post
	// ------------------
	// Compiles all the incoming information into an array
	// which is returned to the accessor
	/*-------------------------------------------------------------------------*/

	protected function compilePost()
	{
		//-----------------------------------------
		// Sort out post content
		//-----------------------------------------
		
		if ( $this->getPostContentPreFormatted() )
		{
			$postContent = $this->getPostContentPreFormatted();
		}
		else
		{
			$postContent = $this->formatPost( $this->getPostContent() );
		}
		
		//-----------------------------------------
		// Need to format the post?
		//-----------------------------------------
		$post = array(
						'author_id'      => $this->getAuthor('member_id') ? $this->getAuthor('member_id') : 0,
						'use_sig'        => $this->getSettings('enableSignature'),
						'use_emo'        => $this->getSettings('enableEmoticons'),
						'ip_address'     => $this->member->ip_address,
						'post_date'      => IPS_UNIX_TIME_NOW,
						'post'           => $postContent,
						'author_name'    => $this->getAuthor('member_id') ? $this->getAuthor('members_display_name') : $this->request['UserName'],
						'issue_id'       => "",
						'queued'         => ( $this->getPublished() ) ? 0 : 1,
						'post_htmlstate' => $this->getSettings('post_htmlstatus'),
					 );
		
		//-----------------------------------------
		// If we had any errors, parse them back to this class
		// so we can track them later.
		//-----------------------------------------

		IPSText::getTextClass( 'bbcode' )->parse_smilies			= $post['use_emo'];
		IPSText::getTextClass( 'bbcode' )->parse_html				= ( self::$project['use_html'] and $this->getAuthor('g_dohtml') and $post['post_htmlstate'] ) ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $post['post_htmlstate'] == 2 ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= self::$project['use_html'] ? 1 : 0;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'tracker';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->getAuthor('member_group_id');
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->getAuthor('mgroup_others');
		
		$testParse	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $postContent );

		if ( IPSText::getTextClass( 'bbcode' )->error )
		{
			$this->_postErrors = IPSText::getTextClass( 'bbcode' )->error;
		}
		else if ( IPSText::getTextClass( 'bbcode' )->warning )
		{
			$this->_postErrors = IPSText::getTextClass( 'bbcode' )->warning;
		}

		return $post;
	}

	/*-------------------------------------------------------------------------*/
	// HTML: name fields.
	// ------------------
	// Returns the HTML for either text inputs or membername
	// depending if the member is a guest.
	/*-------------------------------------------------------------------------*/

	protected function htmlNameField()
	{
		$html = '';
		$data = array();
		
		$data['userName'] =  isset($this->request['UserName']) ? $this->request['UserName'] : '';

		if( $this->settings['guest_captcha'] AND ! $this->member->getProperty('member_id') )
		{
			$classToLoad  = IPSLib::loadLibrary( IPS_KERNEL_PATH . 'classCaptcha.php', 'classCaptcha' );
			$captchaClass = new $classToLoad( $this->registry, $this->settings['bot_antispam_type'] );
			$captchaHTML  = $captchaClass->getTemplate();
			
			return $captchaHTML;
		}
		
		return false;
	}

	/*-------------------------------------------------------------------------*/
	// HTML: Post body.
	// ------------------
	// Returns the HTML for post area, code buttons and
	// post icons
	/*-------------------------------------------------------------------------*/

	protected function htmlPostBody($autoSaveKey='', $edit=0 )
	{
		$postContent = $this->getPostContentPreFormatted() ? $this->getPostContentPreFormatted() : $this->getPostContent();
		
		//-----------------------------------------
		// Hmmmmm....
		//-----------------------------------------
		if ( $edit )
		{
			$postContent = $this->_afterPostCompile( $postContent, 'edit' );
		}
		
		// Set post content
		$this->editor->setContent( $postContent );

		return $this->editor->show( 'Post', array( 'autoSaveKey' => $autoSaveKey, 'height' => 350 ) );
	}

	/**
	 * After post compilation has taken place, we can manipulate it further
	 *
	 * @param	string	Post content
	 * @param	string	Form type (new/edit/reply)
	 * @author	MattMecham
	 */
	protected function _afterPostCompile( $postContent, $formType )
	{
		$postContent = $postContent ? $postContent : $this->_originalPost['post'];

		if ( $formType == 'edit' )
		{
			//-----------------------------------------
			// Unconvert the saved post if required
			//-----------------------------------------

			if ( isset($_POST['Post']) )
			{
				if ( IPSText::getTextClass('editor')->method != 'rte' && $this->request['_from'] != 'quickedit' )
				{
					$_POST['Post'] = str_replace( '&', '&amp;', $_POST['Post'] );
					$_POST['Post'] = str_replace( '&amp;#092;', '&#092;', $_POST['Post'] );
				}
				else
				{
					//-----------------------------------------
					// Coming from quick edit, need to fix the
					// HTML entities - we could even check
					// request['_from'] == 'quickedit' here if need be
					//-----------------------------------------

					$_POST['Post'] = str_replace( '&amp;', '&', $_POST['Post'] );
				
				}

				$postContent = IPSText::stripslashes( $_POST['Post'] );
			}
		}

		return $postContent;
	}
	
	/*-------------------------------------------------------------------------*/
	// HTML: Post Options.
	// ------------------
	// Returns the HTML for post options
	/*-------------------------------------------------------------------------*/

	protected function htmlPostOptions( $editOptions="")
	{
		$this->lang->words['the_max_length'] = $this->settings['tracker_max_post_length'] * 1024;

		return $editOptions;
	}

	/*-------------------------------------------------------------------------*/
	// HTML: checkboxes
	// ------------------
	// Returns the HTML for sig/emo/track boxes
	// $tid is the issue_id
	// $fid is the project_id
	/*-------------------------------------------------------------------------*/

	protected function htmlCheckboxes($type="", $issueID="", $fid="") 
	{
		$return = array(
		  'sig'  => 'checked="checked"',
  		  'emo'  => 'checked="checked"',
          'html' => array(),
  		  'tra'  => ( $this->getAuthor('auto_track') OR $this->getSettings('enableTrack') ) ? 'checked="checked"' : ''
        );

		if ( ! $this->getSettings('enableSignature') )
		{
			$return['sig'] = '';
		}
		
		if ( ! $this->getSettings('enableEmoticons') )
		{
			$return['emo'] = '';
		}

		if ( self::$project['use_html'] and $this->caches['group_cache'][ $this->memberData['member_group_id'] ]['g_dohtml'] )
		{
			$this->request['post_htmlstatus'] = isset($this->request['post_htmlstatus']) ? intval($this->request['post_htmlstatus']) : 0;

			$return['html'] = array( 0 => '', 1 => '', 2 => '' );
			$return['html'][ $this->request['post_htmlstatus'] ] = ' selected="selected"';
		}
		
		if ( $type == 'reply' )
		{
			if ( $issueID and $this->getAuthor('member_id') )
			{
				//-----------------------------------------
				// Like class
				//-----------------------------------------
				
				require_once( IPS_ROOT_PATH . 'sources/classes/like/composite.php' );
				$_like	= classes_like::bootstrap( 'tracker', 'issues' );
				$_track	= $_like->isLiked( $issueID, $this->getAuthor('member_id') );

				if ( $_track )
				{
					$return['tra'] = '-tracking-';
				}
			}
		}
		
		// Return it back to the post type class
		return $return;
	}

	/*-------------------------------------------------------------------------*/
	// HTML: Build Upload Area - yay
	/*-------------------------------------------------------------------------*/

	protected function htmlBuildUploads( $postKey="", $type="", $pid=0 )
	{

		//-----------------------------------------
		// Grab render attach class
		//-----------------------------------------

		$classToLoad		= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$classAttach		= new $classToLoad( $this->registry );
		$classAttach->type	= 'tracker';
		$classAttach->attach_post_key	= $postKey;
		$classAttach->init();
		$classAttach->getUploadFormSettings();

		$uploadField = $this->registry->output->getTemplate( 'post' )->uploadForm( $postKey, 'tracker', $classAttach->attach_stats, $pid, self::$project['project_id'] );

		return $uploadField;
	}

	/*-------------------------------------------------------------------------*/
	// perform: Check for new topic
	/*-------------------------------------------------------------------------*/

	protected function checkForNewIssue()
	{
		if ( ! $this->member->tracker['start_perms'] )
		{
			$this->registry->output->showError( 'You cannot start a new issue in this project', '10T104' );
		}
	}

	/*-------------------------------------------------------------------------*/
	// perform: Check for reply (LEGACY FUNCTIONS)
	/*-------------------------------------------------------------------------*/

	protected function checkForReply( $issue=array() )
	{
		$this->tracker->issues()->checkPermission( $issue, 'reply' );
	}

	/*-------------------------------------------------------------------------*/
	// perform: Check for edit (LEGACY FUNCTIONS)
	/*-------------------------------------------------------------------------*/

	protected function checkForEdit( $issue=array(), $pid=0 )
	{
		$this->request['pid'] = $pid;
		return $this->tracker->issues()->checkPermission( $issue, 'edit' );
	}

	/*-------------------------------------------------------------------------*/
	// Convert temp uploads into permanent ones! YAY
	// ------------------
	// ^^ proper chinese new year!
	/*-------------------------------------------------------------------------*/

	protected function makeAttachmentsPermanent( $post_key="", $rel_id="", $rel_module="", $args=array() )
	{
		$cnt = array( 'cnt' => 0 );

		//-----------------------------------------
		// Attachments: Re-affirm...
		//-----------------------------------------
		
		$classToLoad	= IPSLib::loadLibrary( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php', 'class_attach' );
		$class_attach	= new $classToLoad( $this->registry );
		$class_attach->type            =  $rel_module;
		$class_attach->attach_post_key =  $post_key;
		$class_attach->attach_rel_id   =  $rel_id;
		$class_attach->init();

		$return = $class_attach->postProcessUpload( $args );

		return intval( $return['count'] );
	}

	/*-------------------------------------------------------------------------*/
	// Recount how many attachments a topic has
	// ------------------
	//
	/*-------------------------------------------------------------------------*/

	protected function recountIssueAttachments($iid="")
	{
		if ( $iid == "" )
		{
			return;
		}

		//-----------------------------------------
		// GET PIDS
		//-----------------------------------------

		$pids  = array();
		$count = 0;

		$this->DB->build(
			array(
				'select'   => 'count(*) as cnt',
				'from'     => array( 'attachments' => 'a' ),
				'where'    => "p.issue_id={$iid}",
				'add_join' => array(
					0 => array(
						'from'  => array( 'tracker_posts' => 'p' ),
						'where' => "p.pid=a.attach_rel_id",
						'type'  => 'left'
					)
				)
			)
		);
		$this->DB->execute();

		$cnt = $this->DB->fetch();

		$count = intval( $cnt['cnt'] );

		$this->DB->build( array( 'update' => 'tracker_issues', 'set' => "hasattach=$count", 'where' => "issue_id={$iid}" ) );
		$this->DB->execute();
	}

	/*-------------------------------------------------------------------------*/
	// Clean topic title
	// ------------------
	// ^^ proper er... um
	/*-------------------------------------------------------------------------*/

	protected function cleanIssueTitle($title="")
	{
		$title = strip_tags( $title );
		$title = str_replace( "&#33;" , "!", $title );
		$title = str_replace( "&quot;", '"', $title );
		
		//Not sure why you added this code that breaks all the titles..
		//$title = IPSText::truncate( $title, 30, '' );
			
		if ($this->settings['etfilter_punct'])
		{
			$title	= preg_replace( "/\?{1,}/"      , "?"    , $title );		
			$title	= preg_replace( "/(&#33;){1,}/" , "&#33;", $title );
		}

		if ($this->settings['etfilter_shout'])
		{
			$title = ucwords(strtolower($title));
		}

		return $title;
	}

	/*-------------------------------------------------------------------------*/
	// QUOTIN' DA' POSTAY IN DO HoooD'
	/*-------------------------------------------------------------------------*/

	protected function checkMultiQuote()
	{
		$rawPost = '';

		if ( ! $this->request['qpid'] )
		{
			$this->request['qpid'] = IPSCookie::get('mqtids');

			if ($this->request['qpid'] == ",")
			{
				$this->request['qpid'] = "";
			}
		}
		else
		{
			//-----------------------------------------
			// Came from reply button
			//-----------------------------------------

			$this->request['parent_id'] = $this->request['qpid'];
		}

		$this->request['qpid'] = preg_replace( "/[^,\d]/", "", trim($this->request['qpid']) );

		if ( $this->request['qpid'] )
		{
			$this->quoted_pids = preg_split( '/,/', $this->request['qpid'], -1, PREG_SPLIT_NO_EMPTY );

			//-----------------------------------------
			// Get the posts from the DB and ensure we have
			// suitable read permissions to quote them
			//-----------------------------------------

			if ( count($this->quoted_pids) )
			{
				$this->DB->build(
					array(
						'select'   => 'p.*',
						'from'     => array( 'tracker_posts' => 'p' ),
						'where'    => 'p.pid IN ('.implode(",", $this->quoted_pids).')',
						'add_join' => array(
							array(
								'select' => 'i.project_id',
								'from'   => array( 'tracker_issues' => 'i' ),
								'where'  => 'i.issue_id=p.issue_id',
								'type'   => 'left'
							)
						)
					)
				);
				$this->DB->execute();

				while ( $tp = $this->DB->fetch() )
				{
					if ( $this->tracker->projects()->checkPermission( 'read', $tp['project_id'] ) )
					{
						$tempPost = $tp['post'];

						if ( $this->settings['strip_quotes'] )
						{
							$tempPost = IPSText::getTextClass( 'bbcode' )->stripQuotes( $tempPost );
						}

						/* Strip shared media in quotes */
						$tempPost = IPSText::getTextClass( 'bbcode' )->stripSharedMedia( $tempPost );

						if( $tempPost )
						{
							$_post		= preg_replace( '/(<br\s*\/?>\s*)+$/', "<br />", "<br />" . rtrim($tempPost) . "<br />" );
							$rawPost	.= "[quote name='" . IPSText::getTextClass( 'bbcode' )->makeQuoteSafe($tp['author_name']) . "' timestamp='" . $tp['post_date'] . "']{$_post}[/quote]<br /><br />";
						}
					}
				}
				$rawPost = trim($rawPost)."\n";
			}
		}

		//-----------------------------------------
		// Make raw POST safe for the text area
		//-----------------------------------------

		$rawPost .= IPSText::raw2form( isset($_POST['Post']) ? $_POST['Post'] : '' );

		return $rawPost;
	}

	/**
	* Cheap and probably nasty way of killing quotes
	*
	*/
	protected function killQuotes( $t )
	{
		if ( preg_match( "#\[QUOTE([^\]]+?)?\](.+?)\[/QUOTE\]#is", $t ) )
		{
			$t = preg_replace( "#\[QUOTE([^\]]+?)?\](.+?)\[/QUOTE\]#is", "", $t );
			$t = $this->killQuotes( $t );
		}

		# Remove any extra closing quote tags
		return preg_replace( "#\[/quote\]#si", "", $t );
	}

	protected function smilieAlphaSort($a, $b)
	{
		return strcmp( $a['typed'], $b['typed'] );
	}
}

?>