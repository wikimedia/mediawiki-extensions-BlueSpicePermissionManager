-- Database definition for PermissionManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Sebastian Ulbricht <sebastian.ulbricht@gmx.de>

-- @package    BlueSpice_Extensions
-- @subpackage PermissionManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_permission_templates (
  `tpl_id` int(10) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `tpl_name` varchar(255) NOT NULL,
  `tpl_data` blob NOT NULL,
  `tpl_description` mediumblob NOT NULL
)/*$wgDBTableOptions*/;
