<?php

/*
 * App: Custom BBCode Tables for IPB 3.4.x
 * Ver: 1.1.6
 * Web: http://www.ipbaccess.com
 * Author: Zafer BAHADIR - Oscar
 * customTables.php
 */

if( !class_exists('bbcode_parent_main_class') )
{
    require_once( IPS_ROOT_PATH . 'sources/classes/text/parser/bbcode/defaults.php' );
}

class bbcode_plugin_customTables extends bbcode_parent_main_class
{

	public function __construct( ipsRegistry $registry, $_parent=null )
	{
		$this->currentBbcode	= 'customTables';
		parent::__construct( $registry, $_parent );
	}

    protected function _replaceText($txt)
    {
        $_tags = $this->_retrieveTags();
        foreach($_tags as $_tag)
        {
            preg_match_all("#\[{$_tag}=(.*?)\]#i", $txt, $bbcodeids);
            foreach($bbcodeids[1] as $key => $id)
            {
                $bbcodeid = str_replace("'",'', $id);
                $desen = preg_quote($bbcodeids[0][$key],'|');
                $txt = preg_replace("/".$desen."/i", $this->getTable($bbcodeid), $txt);
            }
        }
        return $txt;
    }

	protected function getTable($bbcodeid)
	{

        require_once( IPSLib::getAppDir('bbcodeTables') . '/sources/classes/buildTable.php' );
        $this->table = new buildTable();
        $this->table->bbcodeid = $bbcodeid;
        $this->table->postid = $this->request['p'];
        $tableData = $this->table->getTableData();

        $satir = $tableData['rows'];
        $colon = $tableData['columns'];

        // Load the library
		$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('bbcodeTables') . '/sources/classes/library.php', 'bbcodeTables_library', 'bbcodeTables' );
		$library = new $classToLoad( $this->registry );

        $item = unserialize($tableData['content']);

        for($i=0 ;$i<$satir; $i++)
        {
            for($a=0; $a<$colon; $a++)
            {
                if($a=='0')
                    $tr .="<tr>";

                $data['title'] = $item[$i][$a];

                if(empty($tableData['headerpos']))
                $mode = "Top";
                $mode = $tableData['headerpos'];
                $template = $this->settings['bbcodeTables_usect'];

                $fData['pos']   = $tableData['table_cell_vpos'];
                $fData['title'] = $data['title'];
                $fData['sira']  = $i;
                $fData['mode']  = $mode;


                if($a=='0' and $mode == 'Left')
                    $fData['type']='th';
                else if($i==0 and $mode == 'Top')
                    $fData['type']='th';
                else if(($i==0 OR $a=='0') and $mode == 'Both')
                {
                    if($a==0 and $i==0)
                    $fData['type']='te';
                    else
                    $fData['type']='th';
                }
                else
                    $fData['type']='td';

                // Custom Template CSS Settings
                if($template)
                {
                    if($fData['type']=='th')
                    $tb = $this->settings['bbcodeTables_TH'];

                    if($fData['type']=='td')
                    $tb = $this->settings['bbcodeTables_TD'];

                    if($fData['type']=='te')
                    $tb = $this->settings['bbcodeTables_TE'];

                    $td = explode("\n",$tb);
                    foreach($td as $style)
                    {
                        $style = trim($style);
                        if(!strpos($style,";"))
                            $style = $style.";";
                        $td_tyle .= $style;
                    }

                    $fData['style'] = $td_tyle;

                    $tr .= $this->registry->output->getTemplate('bbcodeTables')->bbcode_cell_custom($fData);
                }
                else
                {

                    if($fData['type']=='th')
                    $tb = $this->settings['bbcodeTables_thcss_settings'];

                    if($fData['type']=='te')
                    $tb = $this->settings['bbcodeTables_tecss_settings'];

                    if($fData['type']=='td')
                    $tb = $this->settings['bbcodeTables_tdcss_settings'];

                    $td = explode("\n",$tb);
                    foreach($td as $style)
                    {
                        $style = trim($style);
                        if(!strpos($style,";"))
                            $style = $style.";";
                        $td_style .= $style;
                    }

                    $fData['style'] = $td_style;

                    // TD Reverse CSS Settings
                    $tb = $this->settings['bbcodeTables_tdcss_rsettings'];
                    $td = explode("\n",$tb);
                    foreach($td as $rstyle)
                    {
                        $rstyle = trim($rstyle);
                        if(!strpos($rstyle,";"))
                            $rstyle = $rstyle.";";
                        $td_rstyle .= $rstyle;
                    }


                    $fData['rstyle'] = $td_rstyle;
                    $tr .= $this->registry->output->getTemplate('bbcodeTables')->bbcode_cell($fData);
                }

                if($a==($colon-1))
                    $tr .="</tr>";

            }
        }

        $tableData['rowsBit'] =  $tr;

        // get template
        if($template)
        {

            $thtml = $this->settings['bbcodeTables_Tables_HTML'];
            $htmls = explode("\n",$thtml);
            foreach($htmls as $html)
            {
                $html = trim($html);
                $html_set .= " ".$html;
            }
            $tableData['html'] = $html_set;

            $tb = $this->settings['bbcodeTables_Tables'];
            $tables = explode("\n",$tb);
            foreach($tables as $style)
            {
                $style = trim($style);
                if(!strpos($style,";"))
                    $style = $style.";";
                $tables_style .= $style;
            }

            //set width
            $table_size = ($colon + 1) * 70;
            $tables_style .= "width:".$table_size."px";

            $tableData['style'] = $tables_style;
            return $this->registry->output->getTemplate('bbcodeTables')->bbcode_table_custom($tableData);
        }
        else
        {
            return $this->registry->output->getTemplate('bbcodeTables')->bbcode_table($tableData);
        }

    }


}


?>