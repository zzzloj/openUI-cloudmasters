<?php
/**
 * @ Application : 		ProMenu
 * @ File : 			mysql_updates.php
 * @ Last Updated : 	Jan 3, 2012 3:28:40 AM
 * @ Author :			Robert Simons
 * @ Copyright :		(c) 2011 Provisionists, LLC
 * @ Link	 :			http://www.provisionists.com/
 */

 $SQL[] = "DELETE FROM skin_templates WHERE template_group='skin_promenu';";

 $SQL[] = "ALTER TABLE promenu ADD promenu_left_open int(1) NOT NULL DEFAULT 0";
 
 $SQL[] = "ALTER TABLE promenu ADD link_to_app int(1) NOT NULL DEFAULT 0";

 $SQL[] = "ALTER TABLE promenu ADD promenu_disable_active int(1) NOT NULL DEFAULT 0";
 
 $SQL[] = "ALTER TABLE promenu ADD promenu_is_cat int(1) NOT NULL DEFAULT 0";
 
 $SQL[] = "ALTER TABLE promenu CHANGE promenu_description promenu_description varchar(255) NULL";
 
 $SQL[] = "ALTER TABLE promenu CHANGE promenu_parent_id promenu_parent_id int(10) NOT NULL DEFAULT 0";