<?php
/**
 * @ Application : 		ProMenu
 * @ File : 			mysql_updates.php
 * @ Last Updated : 	Jan 3, 2012 3:28:40 AM
 * @ Author :			Robert Simons
 * @ Copyright :		(c) 2011 Provisionists, LLC
 * @ Link	 :			http://www.provisionists.com/
 */

 $SQL[] = "ALTER TABLE promenu ADD promenu_view_menu text NULL";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_view_override int(1) NOT NULL DEFAULT 0";
 
 	
