-- Database definition for PermissionManager
--
-- Part of BlueSpice MediaWiki
--
-- @author     Thomas Lorenz <lorenz@hallowelt.com>

-- @package    BlueSpice_Extensions
-- @subpackage PermissionManager
-- @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
-- @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
-- @filesource

CREATE TABLE /*$wgDBprefix*/bs_permission_templates
(
  tpl_id            bigserial NOT NULL PRIMARY KEY,
  tpl_name          character varying(100) NOT NULL,
  tpl_description   text NOT NULL,
  tpl_data          text NOT NULL
);
