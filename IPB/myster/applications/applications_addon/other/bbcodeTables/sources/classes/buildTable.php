<?php

/*
 * App: Custom BBCode Tables for IPB 3.4.x
 * Ver: 1.1.6
 * Web: http://www.ipbaccess.com
 * Author: Zafer BAHADIR - Oscar
 * customTables.php
 */

class buildTable
{
	protected $registry = null;

	public function __construct()
	{
		$this->registry =   ipsRegistry::instance();
		$this->DB       =   ipsRegistry::DB();
		$this->settings =&  ipsRegistry::fetchSettings();
	}

	public function getTableData()
	{
        //update bbcode postid
        $update['postid'] = $this->postid;
        $this->DB->update( 'bbcodeTables', $update, "id='".$this->bbcodeid."'" );

        // get bbcode data
        $tableData = $this->DB->buildAndFetch( array(
            'select'    => '*',
            'from'      => 'bbcodeTables',
            'where'     => "id='".$this->bbcodeid."'") );
        return $tableData;
	}

}
?>
