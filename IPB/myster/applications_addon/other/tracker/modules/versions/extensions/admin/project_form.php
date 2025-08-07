<?php

/**
* Tracker 2.1.0
* 
* Project Plugin module
* Last Updated: $Date: 2012-05-08 15:44:16 +0100 (Tue, 08 May 2012) $
*
* @author		$Author: stoo2000 $
* @copyright	2001 - 2013 Invision Power Services, Inc.
*
* @package		Tracker
* @subpackage	Module-Versions
* @link			http://ipbtracker.com
* @version		$Revision: 1363 $
*/

class tracker_admin_project_form__versions_field_version extends tracker_admin_project_form_main implements tracker_admin_project_form
{
	/**
	 * Returns HTML data in an array structure for popup display
	 *
	 * @return array the tab data
	 * @access public
	 * @since 2.0.0
	 */
	public function getContent()
	{
		$this->maxID    = 0;
		$this->versions = array();

		/* Load Skin Template */
		$this->html = $this->tracker->modules()->loadTemplate( 'cp_skin_module_versions_project_form', 'versions' );

		/* Get versions from database */
		$this->DB->build(
			array(
				'select'	=> '*',
				'from'		=> 'tracker_module_version',
				'where'		=> 'project_id=' . intval($this->request['project_id']),
				'order'		=> 'position ASC'
			)
		);
		$this->DB->execute();

		if ( $this->DB->getTotalRows() )
		{
			while( $row = $this->DB->fetch() )
			{
				if ( $row['version_id'] > $this->maxID )
				{
					$this->maxID = $row['version_id'];
				}

				$this->versions[] = $row;
			}
		}

		return array(
			1 => array(
				'key'     => 'versions',
				'content' => $this->html->versionPopup( $this->versions )
			)
		);
	}

	/**
	 * Returns javascript for inclusion
	 *
	 * @return string JS data for include
	 * @access public
	 * @since 2.0.0
	 */
	public function getJavascript()
	{
		$remove   = $this->tracker->parseJavascriptTemplate( $this->html->versionRemove( $this->versions ) );
		$template = $this->tracker->parseJavascriptTemplate( $this->html->versionItem() );

		return <<<EOF
	ipb.tracker.templates['version_li']     = new Template("{$template}");
	ipb.tracker.templates['version_remove'] = new Template("{$remove}");
	ipb.tracker.liCount                     = {$this->maxID};
EOF;
	}

	/**
	 * Returns array containing tab information
	 *
	 * @return array the extra tabs
	 * @access public
	 * @since 2.0.0
	 */
	public function getTabs()
	{
		return array(
			1 => array(
				'title'   => 'Project Versions',
				'key'     => 'versions'
			),
			2 => array(
				'title'      => 'Add Version',
				'button'     => true,
				'button_id'  => 'add_version',
				'show_for'   => 'versions'
			)
		);
	}

	/**
	 * Runs any save commands on the data presented in the additional tabs
	 *
	 * @param array $data array of data for saving
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function save( $data=array() )
	{
		$versions	= array();
		$saveArray	= array();

		if ( is_array( $data['versions'] ) )
		{
			foreach( $data['versions'] as $k => $v )
			{
				$keys = explode( '[', $k );

				foreach( $keys as $a => $b )
				{
					$b = str_replace( ']', '', $b );
					$keys[$a] = $b;
				}

				if ( !isset( $keys[2]) )
				{
					continue;
				}

				/* Add it to versions array */
				$versions[ $keys[1] ][ $keys[2] ] = $v;
			}
		}

		$count = 0;

		foreach( $versions as $k => $v )
		{
			$count++;

			$saveArray[$k] = array(
				'human'				=> $v['human'],
				'project_id'		=> intval($this->request['project_id']),
				'permissions'		=> $v['type'],
				'report_default'	=> intval($v['default']),
				'locked'			=> ( $v['type'] == 'locked' ? 1 : 0 ),
				'position'			=> $count
			);
			
			// Did we get any data?
			if ( trim($v['human']) == '' )
			{
				continue;
			}

			if ( $v['save_type'] == 'new' )
			{
				$this->DB->insert( 'tracker_module_version', $saveArray[$k] );
			}
			else if ( $v['save_type'] == 'save' )
			{
				$this->DB->update( 'tracker_module_version', $saveArray[$k], 'version_id=' . $k );
			}
			else if ( $v['save_type'] == 'delete' )
			{
				if ( ! $v['move_to'] )
				{
					continue;
				}

				/* No version assigned */
				if ( $v['move_to'] == 'nva' )
				{
					$v['move_to'] = '';
				}

				$this->DB->update( 'tracker_issues', array( 'module_versions_reported_id' => intval( $v['move_to'] ) ), 'module_versions_reported_id=' . $k );
				$this->DB->delete( 'tracker_module_version', 'version_id=' . $k );
			}
		}

		$this->tracker->cache('versions', 'versions')->rebuild();
	}
}

class tracker_admin_project_form__versions_field_fixed_in extends tracker_admin_project_form_main implements tracker_admin_project_form
{
	/**
	 * Returns HTML data in an array structure for popup display
	 *
	 * @return array the tab data
	 * @access public
	 * @since 2.0.0
	 */
	public function getContent() { return array(); }
	/**
	 * Returns javascript for inclusion
	 *
	 * @return string JS data for include
	 * @access public
	 * @since 2.0.0
	 */
	public function getJavascript() { return NULL; }
	/**
	 * Returns array containing tab information
	 *
	 * @return array the extra tabs
	 * @access public
	 * @since 2.0.0
	 */
	public function getTabs() { return array(); }
	/**
	 * Runs any save commands on the data presented in the additional tabs
	 *
	 * @param array $data array of data for saving
	 * @return void
	 * @access public
	 * @since 2.0.0
	 */
	public function save( $data=array() ) {}
}

?>