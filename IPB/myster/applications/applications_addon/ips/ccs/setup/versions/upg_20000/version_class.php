<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * Upgrade Class
 *
 * Class to add options and notices for IP.Board upgrade
 * Last Updated: $Date: 2011-12-29 18:12:15 -0500 (Thu, 29 Dec 2011) $
 * </pre>
 * 
 * @author		Matt Mecham <matt@invisionpower.com>
 * @version		$Rev: 10076 $
 * @since		25th Feb 2010
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @link		http://www.invisionpower.com
 * @package		IP.Content
 */ 

class version_class_ccs_20000
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Add pre-upgrade options: Form
	 * 
	 * @access	public
	 * @return	string	 HTML block
	 */
	public function preInstallOptionsForm()
	{
		$_formHtml	= '';
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template'" ) );
		$outer	= $this->DB->execute();
		
		if( $this->DB->getTotalRows($outer) )
		{
$_formHtml	.= <<<EOF
	<br /><br />
	<h2>Database Template Containers</h2>
	<table width='100%'>
		<tr>
			<td width='40%'>
			<select name='database_template_containers[]' multiple='multiple' size='10'>
EOF;

while( $r = $this->DB->fetch($outer) )
{
	$_formHtml	.= <<<EOF
				<option value='{$r['container_id']}'>{$r['container_name']}</option>
EOF;
}

$_formHtml	.= <<<EOF
			</select>
			</td>
			<td valign='top' style='font-size: 12px; font-weight: bold;'>
				Select all containers that are used to hold database templates
			</td>
		</tr>
	</table>
EOF;
		}
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => "template_database > 0" ) );
		$outer	= $this->DB->execute();
		
		if( $this->DB->getTotalRows($outer) )
		{
$_formHtml	.= <<<EOF
	<br /><br />
	<h2>Database Templates</h2>
	<div>Select which kind of database template each template is</div>
	<table style='border-top: 1px solid #000;'>
EOF;

while( $r = $this->DB->fetch($outer) )
{
	$_formHtml	.= <<<EOF
		<tr style='border-bottom: 1px solid #000;'>
			<td width='40%'>
			<select name='db_template[{$r['template_id']}]'>
				<option value='1'>Category Display</option>
				<option value='2'>Listing Template</option>
				<option value='3'>Record Display</option>
			</select>
			</td>
			<td>
				<strong style='font-weight: bold; font-size: 12px;'>{$r['template_name']}</strong><br />{$r['template_desc']}
			</td>
		</tr>
EOF;
}

$_formHtml	.= <<<EOF
	</table>
EOF;
		}
		
		return $_formHtml;
	}
	
	/**
	 * Add pre-upgrade options: Save
	 *
	 * Data will be saved in saved data array as: appOptions[ app ][ versionLong ] = ( key => value );
	 * 
	 * @access	public
	 * @return	array	 Key / value pairs to save
	 */
	public function preInstallOptionsSave()
	{
		/* Return */
		$_return	= array(
							'containers'	=> implode( ',', ( $_REQUEST['database_template_containers'] AND is_array($_REQUEST['database_template_containers']) ) ? $_REQUEST['database_template_containers'] : array() ),
							'templates'		=> ( is_array($_REQUEST['db_template']) AND count($_REQUEST['db_template']) ) ? serialize($_REQUEST['db_template']) : serialize( array() ),
							);

		return $_return;
	}
	
	/**
	 * Return any post-installation notices
	 * 
	 * @access	public
	 * @return	array	 Array of notices
	 */
	public function postInstallNotices()
	{
		$notices   = array();

		return $notices;
	}
}
