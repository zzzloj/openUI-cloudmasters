<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.4.5
 * AJAX relational field look ahead
 * Last Updated: $Date: 2011-11-04 10:52:30 -0400 (Fri, 04 Nov 2011) $
 * </pre>
 *
 * @author 		$Author: ips_terabyte $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Content
 * @link		http://www.invisionpower.com
 * @since		13 January 2012
 * @version		$Revision: 9759 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_ccs_ajax_relational extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @param	object		Registry reference
	 * @return	@e void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
    	switch( $this->request['do'] )
    	{
			case 'getvalues':
			default:
    			$this->_getFieldValues();
    		break;
    	}
	}
	
	/**
	 * Returns possible matches for the string input
	 *
	 * @return	@e void		Outputs to screen
	 */
	protected function _getFieldValues()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$name = IPSText::convertUnicode( $this->convertAndMakeSafe( $this->request['value'], 0 ), true );
		$name = IPSText::convertCharsets( $name, 'utf-8', IPS_DOC_CHAR_SET );

		//-----------------------------------------
		// Check length
		//-----------------------------------------

		if ( IPSText::mbstrlen( $name ) < 3 )
		{
			$this->returnJsonError( 'requestTooShort' );
		}

		//-----------------------------------------
		// Which database?
		//-----------------------------------------

		$database	= 0;

		foreach( $this->cache->getCache('ccs_fields') as $database => $fields )
		{
			foreach( $fields as $field )
			{
				if( $field['field_id'] == $this->request['field'] )
				{
					$database	= $field['field_database_id'];
					break 2;
				}
			}
		}

		if( !$database )
		{
			$this->returnJsonError( 'requestTooShort1' );
		}

		$database	= $this->caches['ccs_databases'][ $database ];

		if( !$database['database_id'] )
		{
			$this->returnJsonError( 'requestTooShort2' );
		}

		//-----------------------------------------
		// Try query...
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> '*',
								 'from'	    => $database['database_database'],
								 'where'	=> "field_{$this->request['field']} LIKE '" . $this->DB->addSlashes( strtolower( $name ) ) . "%'",
								 'order'	=> $this->DB->buildLength( "field_{$this->request['field']}" ) . ' ASC',
								 'limit'	=> array( 0, 15 )
						)		);
		$this->DB->execute();

		//-----------------------------------------
		// Got any results?
		//-----------------------------------------

		if ( ! $this->DB->getTotalRows() )
		{
			$this->returnJsonArray( array( ) );
		}

		$return = array();

		while( $r = $this->DB->fetch() )
		{
			$return[ $r['primary_id_field'] ]	= array(
														'name'		=> $r['field_' . $this->request['field'] ],
														'showas'	=> $r['field_' . $this->request['field'] ],
														);
		}

		$this->returnJsonArray( $return );
	}
}