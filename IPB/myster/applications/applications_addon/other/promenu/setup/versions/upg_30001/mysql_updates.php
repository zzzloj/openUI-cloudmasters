<?php
 $SQL[] = "ALTER TABLE  promenuplus_menus CHANGE  promenu_menus_group  promenu_menus_group VARCHAR( 255 ) NULL DEFAULT  'primary'";
  $SQL[] = "ALTER TABLE  promenuplus_menus CHANGE  promenu_menus_order  promenu_menus_order INT( 255 ) NULL DEFAULT  '0'";