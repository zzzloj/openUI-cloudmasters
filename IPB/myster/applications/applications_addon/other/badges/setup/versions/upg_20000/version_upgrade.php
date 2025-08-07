<?php
/**
 *
 * @class	version_upgrade
 * @brief	2.0.0 Upgrade Logic
 *
 */
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
	 * @return	string
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
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{	
			default:
			case 'convertToBadgesUrl':
				$this->convertToBadgesUrl();
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
	 * Convert to badges URL
	 */
	protected function convertToBadgesUrl()
	{
		$this->DB->build( array(
			'select'	=> '*',
			'from'		=> 'HQ_badges',
		));
		
		$q = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $q ) )
		{
			if ($r['ba_type'] == '1') {
				$this->DB->update( 'HQ_badges', array( 'ba_image' => '1.png' ), 'ba_id='.$r['ba_id'] );
			}
			if ($r['ba_type'] == '2') {
				$this->DB->update( 'HQ_badges', array( 'ba_image' => '2.png' ), 'ba_id='.$r['ba_id'] );
			}
			if ($r['ba_type'] == '3') {
				$this->DB->update( 'HQ_badges', array( 'ba_image' => '3.png' ), 'ba_id='.$r['ba_id'] );
			}
			if ($r['ba_type'] == '4') {
				$this->DB->update( 'HQ_badges', array( 'ba_image' => '4.png' ), 'ba_id='.$r['ba_id'] );
			}
			if ($r['ba_type'] == '5') {
				$this->DB->update( 'HQ_badges', array( 'ba_image' => '5.png' ), 'ba_id='.$r['ba_id'] );
			}
			$this->DB->update( 'HQ_badges', array( 'ba_type' => '1' ), 'ba_id='.$r['ba_id'] );
		}
		
		
		
		$this->registry->output->addMessage( "Converted badges..." );
		$this->request['workact'] = '';
	}
	
}