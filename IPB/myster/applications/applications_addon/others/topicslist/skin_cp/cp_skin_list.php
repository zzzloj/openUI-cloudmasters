<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.1.4
 * Portal skin file
 * Last Updated: $Date: 2010-05-19 21:06:53 -0400 (Wed, 19 May 2010) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/community/board/license.html
 * @package		IP.Board v3.1.4
 * @subpackage	Portal
* @Nulled.  Protection Removed. Nulled By CGT
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 6326 $
 */
 
class cp_skin_list extends output 
{

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}

/**
 * Portal tag details
 *
 * @access	public
 * @param	string		Page title
 * @param	array 		Available tags
 * @return	string		HTML
 */
public function listOverview(  ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF

<div class='section_title'>
	<h2>Wall Management</h2>
</div>

Hello, Thanks for buying Topics Wall.<br>
You can manage the settings in Settings > Topics Wall. Have Fun!<br><br>

<div class='section_title'>
	<h2>Faqs</h2>
</div>

Before starting, please don't enable this application in forums containing many topics, unless you have a powerful server, because loading times could be very high.<br><br>
<ul>
<li><strong>Sometimes, when clicking some letters, like 'E' or 'I', I also get letters starting with A or strange letters.</strong><br><br>
That's probably because you have UTF-8 as default Charset and those are accented characters. UTF-8 is fine for English alphabet, but not good for alphabets containing accented letters. You should set your Charset to ISO-8859. This will not fix it, but will prevent your database to contain those strange characters when putting accented letters to the database.</li><br>
<li><strong>What does the 'BBcode to Pull' function do?</strong><br><br>
We recommend you to use that function because it'll make the application look nicer and more professional. It'll also make appear only text that you want and not random text. <br>
It will take the first 250 characters within that BBcode and put it in the details. Note that you don't have to create your own BBcode to make it work, you can also insert QUOTE or CODE and it will take the first 250 characters of the first occurence of that BBcode. We recommend to do like this to avoid the pull of large words that may compromise the width of the table.</li><br>
<li><strong>What does the 'Check availability of every external image in the first post' function do?</strong><br><br>
This function will take <strong>every</strong> image in the first post of the topic. Only the first <strong>valid</strong> image will be put as preview image. This means that this function will check if those images are available on external servers, avoiding corrupted images. Note that this function will be run for every topic, so loading times could be very high. We recommend you to use this function if you just want to check if topics have valid images and then disable it as soon as you have 'repaired' those images.</li><br>
<li><strong>Why do you recommend me not to enable the 'View all topics' function?</strong><br><br>
We recommend you not to enable that function because it can be a highly resources intensive function on large forums. But you can still enable it in forums that you know that there will be a small number of topics.</li>
</ul>
EOF;
//--endhtml--//
return $IPBHTML;
}



}