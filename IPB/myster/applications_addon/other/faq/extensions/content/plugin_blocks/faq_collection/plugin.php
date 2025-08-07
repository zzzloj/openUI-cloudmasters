<?php

/*
+--------------------------------------------------------------------------
|   [HSC] FAQ System 1.2
|   =============================================
|   by Esther Eisner
|   Copyright 2012 HeadStand Consulting
|   esther@headstandconsulting.com
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class plugin_faq_collection implements pluginBlockInterface
{
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	protected $registry;
	protected $request;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->memberData	=& $registry->member()->fetchMemberData();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;
        
        $this->lang->loadLanguageFile(array('admin_faq'), 'faq');
	}
	
	/**
	 * Return the tag help for this block type
	 *
	 * @access	public
	 * @return	array
	 */
	public function getTags()
	{
		return array(
					$this->lang->words['block_plugin__generic'] => array( 
																		array( '&#36;content', $this->lang->words['block_plugin_mc_content'] ) ,
																		),
					);
	}
	
	/**
	 * Return the plugin meta data
	 *
	 * @access	public
	 * @return	array 			Plugin data (name, description, hasConfig)
	 */
	public function returnPluginInfo()
	{
		return array(
					'key'			=> 'faq_collection',
					'name'			=> $this->lang->words['faq_collection_block'],
					'description'	=> $this->lang->words['faq_collection_block_desc'],
					'hasConfig'		=> true,
					'templateBit'	=> 'block__faq_collection',
					);
	}
	
	/**
	 * Get plugin configuration data.  Returns form elements and data
	 *
	 * @access	public
	 * @param	array 			Session data
	 * @return	array 			Form data
	 */
	public function returnPluginConfig( $session )
	{
		$options	= array();
        
        $this->DB->build(array('select' => 'c.name, c.collection_key', 
                                'from' => array('faq_collections' => 'c'),
                                'group' => 'cq.collection_id',
                                'order' => 'c.name',
                                'add_join' => array(
                                    array('select' => 'count(cq.question_id) as total_questions',
                                            'from' => array('faq_collections_questions' => 'cq'),
                                            'where' => 'c.collection_id=cq.collection_id',
                                            'type' => 'left')
                                )));
        $query = $this->DB->execute();
        while($row = $this->DB->fetch($query))
        {
            if($row['total_questions'])
            {
                $options[] = array($row['collection_key'], $row['name']);
            }
        }
        $this->DB->freeResult($query);
        
        return array(
                    array(
                            'label' => $this->lang->words['plugin__faq_label1'],
                            'description' => $this->lang->words['plugin__faq_desc1'],
                            'field' => $this->registry->output->formDropdown('plugin__faq_collection', $options, $session['config_data']['custom_config']['collection'])
                        )
                    );
	}

	/**
	 * Check the plugin config data
	 *
	 * @access	public
	 * @param	array 			Submitted plugin data to check (usually $this->request)
	 * @return	array 			Array( (bool) Ok or not, (array) Plugin data to use )
	 */
	public function validatePluginConfig( $data )
	{
	    $collectionKey = '';
	    if(isset($this->caches['faq_collections'][$data['plugin__faq_collection']]))
        {
            return array(true, array('collection_key' => $data['plugin__faq_collection']));
        }
        
        return array(false, array());
	}
	
	/**
	 * Execute the plugin and return the HTML to show on the page.  
	 * Can be called from ACP or front end, so the plugin needs to setup any appropriate lang files, skin files, etc.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	string				Block HTML to display or cache
	 */
	public function executePlugin( $block )
	{
	   	$config	= unserialize($block['block_config']);
        
        $collectionKey = $config['custom']['collection_key'];
        $content = $this->registry->output->getTemplate('faq')->showCollection($collectionKey);
        
        $pluginConfig	= $this->returnPluginInfo();
        $templateBit	= $pluginConfig['templateBit'] . '_' . $block['block_id'];
        
		ob_start();
 		$_return	= $this->registry->output->getTemplate('ccs')->$templateBit($content);
        ob_end_clean();
 		return $_return;
	}
}