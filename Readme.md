# BlueSpicePermissionManager
BlueSpicePermissionManager offers easy and user-friendy way to manage user permissions on the wiki.

## Features
* PermissionManager is for managing rights or permissions at a group and namespace level
* The 3rd version uses roles, that can be assigned to user groups. Display of permissions (inherited and blocked) for groups changed to the display of roles (inherited and blocked) for groups. Also roles instead of permissions are assigned on the wiki level.
* `Roles itself are a collection of permissions. The "Editor" has the permissions "edit", "delete", "createpage" ...etc and could be assigned to the group "user" i.e.`

## Configuration
Some aspects of this extension can be configured on Special:BlueSpiceConfigManager, under section "Permission manager". Here wiki administrators can configure:
  * Backup limit: Defines, how many backups, which are created everytime the permission settings are saved, are stored.
  * This is actually a config variable $bsgPermissionManagerMaxBackups for an integer. This can be set via LocalSettings. Default is 50.
The Extension [BlueSpiceConfigManager] allows to edit this kind of configurations, when it is active.

## Requirements
PermissionManager requires [BlueSpiceFoundation](https://en.wiki.bluespice.com/wiki/Reference:BlueSpiceFoundation)