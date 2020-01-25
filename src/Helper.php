<?php

namespace BlueSpice\PermissionManager;

use BlueSpice\Permission\RoleManager;
use BlueSpice\Services;

class Helper {
	/**
	 *
	 * @var Helper
	 */
	protected static $instance = null;

	/**
	 *
	 * @var array
	 */
	protected $implicitGroups;
	/**
	 *
	 * @var array
	 */
	protected $namespaceRolesLockdown;

	/**
	 *
	 * @var array
	 */
	protected $groups = [];
	/**
	 *
	 * @var array
	 */
	protected $builtInGroups = [
		'autoconfirmed', 'emailconfirmed', 'bot', 'sysop', 'bureaucrat', 'developer'
	];

	/**
	 *
	 * @var RoleManager
	 */
	protected $roleManager;

	/**
	 *
	 * @return Helper
	 */
	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = self::createInstance();
		}
		return self::$instance;
	}

	/**
	 *
	 * @return Helper
	 */
	protected static function createInstance() {
		$mainConfig = Services::getInstance()->getMainConfig();

		$roleManager = Services::getInstance()->getBSRoleManager();
		$config = Services::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		$namespaceRolesLockdown = $config->get( 'NamespaceRolesLockdown' );

		return new self( $mainConfig->get( 'ImplicitGroups' ), $namespaceRolesLockdown, $roleManager );
	}

	/**
	 *
	 * @param array $implicitGroups
	 * @param array $namespaceRolesLockdown
	 * @param RoleManager $roleManager
	 */
	protected function __construct( $implicitGroups, $namespaceRolesLockdown, $roleManager ) {
		$this->implicitGroups = $implicitGroups;
		$this->namespaceRolesLockdown = $namespaceRolesLockdown;
		$this->roleManager = $roleManager;
	}

	/**
	 *
	 * @return array
	 */
	public function getNamespaceRolesLockdown() {
		return $this->namespaceRolesLockdown;
	}

	/**
	 *
	 * @return array
	 */
	public function getRoleDependencyTree() {
		$roles = $this->roleManager->getRoleNames();
		$tree = [];
		foreach ( $roles as $roleName ) {
			$role = $this->roleManager->getRole( $roleName );
			if ( !$role instanceof \BlueSpice\Permission\IRole ) {
				continue;
			}
			if ( empty( $role->getRequiredPermissions() ) ) {
				continue;
			}
			$requiredPermissions = $role->getRequiredPermissions();
			$neededRoles = [];
			foreach ( $requiredPermissions as $permission ) {
				$rolesWithPermission = $this->roleManager->getRolesWithPermission( $permission );
				if ( in_array( $roleName, $rolesWithPermission ) ) {
					continue;
				}
				$neededRoles[$permission] = $rolesWithPermission;
			}
			$tree[$roleName] = $neededRoles;
		}
		return $tree;
	}

	public function setGroups() {
		$this->groups = [];

		$this->groups = [
			'text' => '*',
			'builtin' => true,
			'implicit' => true,
			'expanded' => true,
			'children' => [
				[
					'text' => 'user',
					'builtin' => true,
					'implicit' => true,
					'expanded' => true,
					'children' => [

					]
				]
			]
		];

		$this->addOtherGroups();
	}

	protected function addOtherGroups() {
		$explicitGroups = \BsGroupHelper::getAvailableGroups(
			[ 'blacklist' => $this->implicitGroups ]
		);

		sort( $explicitGroups );

		$explicitGroupNodes = [];
		foreach ( $explicitGroups as $explicitGroup ) {
			$explicitGroupNode = [
				'text' => $explicitGroup,
				'leaf' => true
			];

			if ( in_array( $explicitGroup, $this->builtInGroups ) ) {
				$explicitGroupNode[ 'builtin' ] = true;
				$explicitGroupNode[ 'iconCls' ] = 'icon-builtin-group';
			} else {
				$explicitGroupNode[ 'iconCls' ] = 'icon-custom-group';
			}

			$explicitGroupNodes[] = $explicitGroupNode;
		}

		$this->groups[ 'children' ][ 0 ][ 'children' ] = $explicitGroupNodes;
	}

	/**
	 *
	 * @return array
	 */
	public function getGroups() {
		if ( empty( $this->groups ) ) {
			$this->setGroups();
		}
		return $this->groups;
	}

	/**
	 *
	 * @return array
	 */
	public function buildNamespaceMetadata() {
		$lang = \RequestContext::getMain()->getLanguage();
		$namespaces = $lang->getNamespaces();
		ksort( $namespaces );

		$metadata = [];

		foreach ( $namespaces as $nsId => $localizedNSText ) {
			if ( $nsId < 0 ) {
				// Filter pseudo namespaces
				continue;
			}

			$nsText = str_replace( '_', ' ', $localizedNSText );
			if ( $nsId == NS_MAIN ) {
				$nsText = wfMessage( 'bs-ns_main' )->text();
			}

			$metadata[] = [
					'id' => $nsId,
					'name' => $nsText,
					'hideable' => $nsId !== NS_MAIN,
					'content' => \MWNamespace::isContent( $nsId ),
					'talk' => \MWNamespace::isTalk( $nsId )
			];
		}

		return $metadata;
	}

	/**
	 *
	 * @param array $rolesAndPermissions
	 * @return array
	 */
	public function formatPermissionsToHint( $rolesAndPermissions ) {
		$res = [];
		foreach ( $rolesAndPermissions as $roleAndPermissions ) {
			$permissionList = implode( ', ', $roleAndPermissions[ 'permissions' ] );
			$permissionCount = count( $roleAndPermissions[ 'permissions' ] );
			$hintText = wfMessage( 'bs-permissionmanager-hint', $permissionList, $permissionCount )->parse();
			$res[] = [
				'role' => $roleAndPermissions[ 'role' ],
				'hint' => $hintText
			];
		}
		return $res;
	}
}
