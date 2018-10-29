<?php

$IP = dirname(dirname(dirname(__DIR__)));

require_once( "$IP/maintenance/Maintenance.php" );

class comparePermissions extends \Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addOption( 'html', 'Outputs in HTML' );
	}

	public function execute() {
		// Read permission before roles
		$oldPermissions = $this->getConfig()->get( 'GroupPermissions' );

		// Initialize and apply roles
		$roleManager = \MediaWiki\MediaWikiServices::getInstance()->getService(
			'BSRoleManager'
		);
		if ( $roleManager->isRoleSystemEnabled() == false ) {
			$roleManager->enableRoleSystem();
			$roleManager->applyRoles();
		}

		// Read new permissions
		$newPermissions = $this->getConfig()->get( 'GroupPermissions' );

		$permissionRegistry = \MediaWiki\MediaWikiServices::getInstance()->getService(
			'BSPermissionRegistry'
		);

		// Compile results
		$result = [];
		$permissions = $permissionRegistry->getPermissions();
		foreach ( $permissions as $name => $object ) {
			$result[$name] = [
				'oldGroups' => $this->getGroups( $name, $oldPermissions ),
				'newGroups' => $this->getGroups( $name, $newPermissions ),
				'roles' => $object->getRoles(),
				'inRegistry' => 1
			];
		}

		foreach ( $this->getPermissionsWithNoRoles( $oldPermissions, $permissionRegistry ) as $missingPermission ) {
			$result[$missingPermission] = [
				'oldGroups' => $this->getGroups( $missingPermission, $oldPermissions ),
				'newGroups' => $this->getGroups( $missingPermission, $newPermissions ),
				'inRegistry' => 0
			];
		}

		$this->displayResult( $result );
	}

	/**
	 * Gets all the groups permission is granted to
	 *
	 * @param string $permToSearch
	 * @param array $groupPermissions
	 * @return array
	 */
	protected function getGroups( $permToSearch, $groupPermissions ) {
		$groups = [];
		foreach ( $groupPermissions as $group => $permissions ) {
			foreach ( $permissions as $permission => $granted ) {
				if ( !$granted ) {
					continue;
				}
				if ( $permission === $permToSearch ) {
					$groups[] = $group;
				}
			}
		}
		return $groups;
	}

	/**
	 * Gets all permissions that are not in the BS registry
	 * This means that these permission cannot be handled by
	 * the role system, until they are added to the registry
	 *
	 * @param array $permissions
	 * @param \BlueSpice\Permission\Registry $permissionRegistry
	 * @return array
	 */
	protected function getPermissionsWithNoRoles( $permissions, $permissionRegistry ) {
		$unique = [];
		$missing = [];
		foreach ( $permissions as $group => $permissions ) {
			foreach ( $permissions as $permission => $granted ) {
				if ( !$granted ){
					continue;
				}
				$unique[$permission] = true;
			}
		}
		$unique = array_keys( $unique );
		foreach ( $unique as $uniquePermission ) {
			if ( $permissionRegistry->getPermission( $uniquePermission ) === null ) {
				$missing[] = $uniquePermission;
			}
		}
		return $missing;
	}

	protected function displayResult( $result ) {
		$format = $this->hasOption( 'html' ) ? 'html' : 'csv';
		if ( $format === 'html' ) {
			$toDisplay = \Html::openElement( 'table' );
			$toDisplay .= $this->getHTMLHeader();
		} else {
			$toDisplay = $this->getCSVHeader();
		}
		foreach ( $result as $permission => $data ) {
			$toDisplay .= ( $format == 'html' ) ?
				$this->getHTMLRow( $permission, $data ) :
				$this->getCSVRow( $permission, $data );
		}
		if ( $format === 'html' ) {
			$toDisplay .= \Html::closeElement( 'table' );
		}
		print $toDisplay;
	}

	protected function getHTMLHeader() {
		$header = \Html::openElement( 'thead' );
		$header .= \Html::openElement( 'tr' );
		$header .= \Html::element( 'td', [], "Permission" );
		$header .= \Html::element( 'td', [], "Old groups" );
		$header .= \Html::element( 'td', [], "New groups" );
		$header .= \Html::element( 'td', [], "Roles" );
		$header .= \Html::element( 'td', [], "In registry" );
		$header .= \Html::closeElement( 'tr' );

		return $header;
	}

	protected function getCSVHeader() {
		$header = "Permission" . '|';
		$header .= "Old groups" . '|';
		$header .= "New groups" . '|';
		$header .= "Roles" . '|';
		$header .= "In registry" . "\n";

		return $header;
	}

	protected function getHTMLRow( $permission, $data ) {
		$row = \Html::openElement( 'tr' );
		$row .= \Html::element( 'td', [], $permission );
		$row .= \Html::element( 'td', [], implode( ',', $data['oldGroups'] ) );
		$row .= \Html::element( 'td', [], implode( ',', $data['newGroups'] ) );
		$row .= \Html::element( 'td', [], implode( ',', $data['roles'] ) );
		$row .= \Html::element( 'td', [], $data['inRegistry'] );
		$row .= \Html::closeElement( 'tr' );
		return $row;
	}

	protected function getCSVRow( $permission, $data ) {
		$row = $permission . '|';
		$row .=  implode( ',', $data['oldGroups'] ) . '|';
		$row .=  implode( ',', $data['newGroups'] ) . '|';
		$row .=  implode( ',', $data['roles'] ) . '|';
		$row .= $data['inRegistry'] . "\n";

		return $row;
	}
}

$maintClass = 'comparePermissions';
require_once( RUN_MAINTENANCE_IF_MAIN );
