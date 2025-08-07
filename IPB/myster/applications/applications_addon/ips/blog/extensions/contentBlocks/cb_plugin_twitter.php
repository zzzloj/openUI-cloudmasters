<?php
/**
* Twitter Content Block
*
* @package		IP.Blog
* @author		Joshua Williams
* @copyright	Invision Power Services, Inc.
* @version		1.4
*/
class cb_twitter extends contentBlocks implements iContentBlock
{

	protected $data;
	protected $configable;
	public $js_block;
	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;

	/**
	 * CONSTRUCTOR
	 *
	 * @param  array   $blog      Array of data from the current blog
	 * @param  object  $registry
	 * @return	@e void
	 */
	public function __construct( $blog, ipsRegistry $registry )	
	{
		$this->blog       = $blog;
		$this->js_block   = 1;
		$this->configable = 1;
		$this->data       = array();
		
		$this->registry   = $registry;
		$this->lang       = $registry->getClass( 'class_localization' );
		$this->member     = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();	
	}
	
	/**
	 * Returns the HTML for the twitter block
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getBlock( $cblock )
	{
		/* Check for config data */
		$config = unserialize( $cblock['cblock_config'] );
		
		if( ! $config['twitter_id'] && ( $cblock['member_id'] == $this->memberData['member_id'] ) )
		{
			return $this->getConfigForm( $cblock );
		}
		
		$return_html = '';
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['twitter'], $this->configable );

if( $config['twitter_id'] )
{
$return_html .= <<<HTML
<script charset="utf-8" src="http://widgets.twimg.com/j/2/widget.js"></script>
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: 4,
  interval: 30000,
  width: 'auto',
  height: 300,
  theme: {
    shell: {
      background: '#dbe2ec',
      color: '#000000'
    },
    tweets: {
      background: '#ffffff',
      color: '#000000',
      links: '#225985'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: false,
    behavior: 'all'
  }
}).render().setUser('{$config['twitter_id']}').start();
</script>
HTML;
}

		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
	
		return $return_html;
	}
	
	/**
	 * Configuration form for this plugin
	 *
	 * @param  array  $cblock  array of custom block data
	 * @return string
	 */
	public function getConfigForm( $cblock )
	{
		/* Check for config data */
		$config = unserialize( $cblock['cblock_config'] );
			
		$return_html = '';
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_header( $cblock, $this->lang->words['twitter_settings'] );
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->config_twitter( $config, $cblock );
		
		$return_html .= $this->registry->output->getTemplate( 'blog_cblocks' )->cblock_footer( array( 'cblock_id' => $cblock['cblock_id'], 'cblock_type' => '', 'allow_edit' => 0 ) );
		return $return_html;
	}
	
	/**
	 * Handles any extra processing needed on config data
	 *
	 * @param  array  $data  array of config data
	 * @return array
	 */	
	public function saveConfig( $data )
	{
		return $data;
	}		
}