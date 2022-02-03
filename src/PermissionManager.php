<?php

namespace BlueSpice\PermissionManager;

use BlueSpice\DynamicSettingsManager;
use BlueSpice\ExtensionAttributeBasedRegistry;
use BlueSpice\Permission\IRole;
use BlueSpice\Permission\PermissionRegistry;
use BlueSpice\Permission\RoleManager;
use Config;
use ManualLogEntry;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use Message;
use MWException;
use Psr\Log\LoggerInterface;
use RequestContext;
use SpecialPage;
use stdClass;

class PermissionManager {
	/** @var PermissionRegistry */
	private $permissionRegistry;
	/** @var RoleManager */
	private $roleManager;
	/** @var Config */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var HookContainer */
	private $hookContainer;
	/** @var MediaWikiServices */
	private $services;
	/** @var array */
	protected $groups = [];
	/** @var ExtensionAttributeBasedRegistry */
	protected $presets;

	/**
	 * @param PermissionRegistry $permissionRegistry
	 * @param RoleManager $roleManager
	 * @param MediaWikiServices $services
	 * @param Config $config
	 * @param LoggerInterface $logger
	 * @param HookContainer $hookContainer
	 */
	public function __construct(
		PermissionRegistry $permissionRegistry,
		RoleManager $roleManager,
		MediaWikiServices $services,
		Config $config,
		LoggerInterface $logger,
		HookContainer $hookContainer
	) {
		$this->permissionRegistry = $permissionRegistry;
		$this->roleManager = $roleManager;
		$this->config = $config;
		$this->logger = $logger;
		$this->hookContainer = $hookContainer;
		$this->services = $services;

		$this->presets = new ExtensionAttributeBasedRegistry(
			'BlueSpicePermissionManagerPermissionPresets'
		);
	}

	/**
	 * Enable role system
	 */
	public function enableRoleSystem() {
		$this->roleManager->enableRoleSystem();
	}

	/**
	 * Apply permission preset
	 */
	public function applyCurrentPreset() {
		try {
			$this->getActivePreset()->apply();
		} catch ( MWException $exception ) {
			$this->logger->critical(
				'Exception while applying preset: {error}',
				[
					'error' => $exception->getMessage()
				]
			);
		}
	}

	/**
	 * @return IPreset
	 */
	public function getActivePreset() {
		$activePreset = $this->config->get( 'PermissionManagerActivePreset' );
		$preset = $this->getPreset( $activePreset );
		if ( $preset === null ) {
			$this->logger->critical(
				'Attempted to apply unrecognized preset: {preset}',
				[
					'preset' => $activePreset
				]
			);
			throw new MWException(
				"Permission preset \"$activePreset\" is not registered or not allowed"
			);
		}

		return $preset;
	}

	/**
	 * Get names of available presets
	 *
	 * @return array
	 */
	public function getAvailablePresets() {
		return array_intersect(
			$this->presets->getAllKeys(),
			$this->config->get( 'PermissionManagerAllowedPresets' )
		);
	}

	/**
	 * Get preset instance
	 *
	 * @param string $name
	 * @return IPreset|null
	 */
	public function getPreset( $name ) {
		if ( !in_array( $name, $this->config->get( 'PermissionManagerAllowedPresets' ) ) ) {
			return null;
		}
		if ( !$this->presets->getValue( $name, null ) ) {
			return null;
		}

		$callable = $this->presets->getValue( $name );
		$preset = call_user_func( $callable );

		if ( !$preset instanceof IPreset ) {
			$this->logger->critical(
				'Preset expected to be an instance of {preset_class}, {actual} given',
				[
					'preset_class' => IPreset::class,
					'actual' => get_class( $preset )
				]
			);
			return null;
		}

		return $preset;
	}

	/**
	 * Get all permissions assigned to given role
	 *
	 * @param IRole $role
	 * @param bool $includeDesc
	 * @return array
	 */
	public function getRolePermissions( $role, $includeDesc = false ) {
		$role = $this->roleManager->getRole( $role );
		if ( $role instanceof IRole === false ) {
			return [];
		}

		$permissions = $role->getPermissions();
		if ( !$includeDesc ) {
			return $permissions;
		}

		$permissionsAndDescs = [];
		foreach ( $permissions as $permission ) {
			$permissionsAndDescs[ $permission ] =
				wfMessage( "right-$permission" )->plain();
		}

		return $permissionsAndDescs;
	}

	/**
	 * @return RoleManager
	 */
	public function getRoleManager() {
		return $this->roleManager;
	}

	/**
	 *
	 * @param stdClass $data
	 * @return array
	 */
	public function saveRoles( $data ) {
		if ( !isset( $data ) || !isset( $data->groupRoles ) || !isset( $data->roleLockdown ) ) {
			return [ 'success' => false ];
		}

		$groupRoles = (array)$data->groupRoles;
		$roleLockdown = (array)$data->roleLockdown;

		$status = $this->hookContainer->run(
			'BsPermissionManager::beforeSaveRoles', [ &$groupRoles, &$roleLockdown ]
		);

		if ( !$status ) {
			return [ 'success' => false ];
		}

		return $this->writeToFile( $groupRoles, $roleLockdown );
	}

	/**
	 *
	 * @return array
	 */
	public function getNamespaceRolesLockdown() {
		return $this->config->get( 'NamespaceRolesLockdown' );
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
		$lang = RequestContext::getMain()->getLanguage();
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

			$namespaceInfo = $this->services->getNamespaceInfo();
			$metadata[] = [
				'id' => $nsId,
				'name' => $nsText,
				'hideable' => $nsId !== NS_MAIN,
				'content' => $namespaceInfo->isContent( $nsId ),
				'talk' => $namespaceInfo->isTalk( $nsId ),
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
				'hint' => $hintText,
				'privilegeLevel' => $roleAndPermissions[ 'privilegeLevel' ]
			];
		}

		$privilegeColumn = array_column( $res, 'privilegeLevel' );
		$nameColumn = array_column( $res, 'role' );
		array_multisort( $privilegeColumn, SORT_ASC, $nameColumn, SORT_ASC, $res );

		return $res;
	}

	/**
	 *
	 * @param array $groupRoles
	 * @param array $roleLockdown
	 * @return array
	 */
	private function writeToFile( $groupRoles, $roleLockdown ) {
		if ( $this->services->getReadOnlyMode()->isReadOnly() ) {
			return [
				'success' => false,
				'message' => wfMessage( 'bs-readonly', $this->services->getReadOnlyMode()->getReason() )->plain()
			];
		}

		$roleMatrixDiff = new RoleMatrixDiff( $this->config, $groupRoles, $roleLockdown );
		$globalDiff = $roleMatrixDiff->getGlobalDiff();
		$nsDiff = $roleMatrixDiff->getNsDiff();

		$saveContent = "<?php\n";
		foreach ( $groupRoles as $group => $roleArray ) {
			foreach ( $roleArray as $role => $value ) {
				$val = $value ? 'true' : 'false';
				$saveContent .= "\$GLOBALS['bsgGroupRoles']['$group']['$role'] = $val;\n";
			}
		}

		foreach ( $roleLockdown as $nsId => $roles ) {
			$nsId = (int)$nsId;
			$namespaceInfo = $this->services->getNamespaceInfo();
			$nsCanonicalName = $namespaceInfo->getCanonicalName( $nsId );
			if ( $nsId == NS_MAIN ) {
				$nsCanonicalName = 'MAIN';
			}

			$nsConstant = "NS_" . strtoupper( $nsCanonicalName );
			if ( !defined( $nsConstant ) ) {
				$nsConstant = $nsId;
			}

			foreach ( $roles as $roleName => $groups ) {
				if ( empty( $groups ) ) {
					continue;
				}
				$saveContent .= "\$GLOBALS['bsgNamespaceRolesLockdown'][ $nsConstant ][ '$roleName' ]"
					. " = array(" . ( count( $groups ) ? "'" . implode( "','", $groups ) . "'" : '' ) . ");\n";
			}
		}

		$dynamicSettingsManager = DynamicSettingsManager::factory();
		$status = $dynamicSettingsManager->persist( 'PermissionManager', $saveContent );
		$res = $status->isGood();
		if ( $res ) {
			$this->doLog( $globalDiff, $nsDiff );
			return [ 'success' => true ];
		} else {
			return [
				'success' => false,
				'message' => Message::newFromKey(
					'bs-permissionmanager-write-config-file-error',
					'pm-settings.php'
				)->plain()
			];
		}
	}

	/**
	 * Set groups hierarchy
	 */
	private function setGroups() {
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

	/**
	 * Add custom groups
	 */
	private function addOtherGroups() {
		$groupHelper = $this->services->getService( 'BSUtilityFactory' )->getGroupHelper();
		$explicitGroups = $groupHelper->getAvailableGroups(
			[ 'filter' => [ 'explicit' ] ]
		);

		sort( $explicitGroups );

		$usableGroups = $groupHelper->getAvailableGroups();

		$explicitGroupNodes = [];
		foreach ( $explicitGroups as $explicitGroup ) {
			$explicitGroupNode = [
				'text' => $explicitGroup,
				'leaf' => true
			];

			if ( in_array( $explicitGroup, $usableGroups ) ) {
				$explicitGroupNode[ 'iconCls' ] = 'icon-custom-group';
			} else {
				$explicitGroupNode[ 'builtin' ] = true;
				$explicitGroupNode[ 'iconCls' ] = 'icon-builtin-group';
			}

			$explicitGroupNodes[] = $explicitGroupNode;
		}

		$this->groups[ 'children' ][ 0 ][ 'children' ] = $explicitGroupNodes;
	}

	/**
	 *
	 * @param array $globalDiff
	 * @param array $nsDiff
	 */
	private function doLog( $globalDiff, $nsDiff ) {
		foreach ( $globalDiff as $group => $roles ) {
			$addedRoles = [];
			$removedRoles = [];
			foreach ( $roles as $role => $added ) {
				if ( $added ) {
					$addedRoles[] = $role;
				} else {
					$removedRoles[] = $role;
				}
			}
			if ( !empty( $addedRoles ) ) {
				$this->insertLog( 'global-add', [
					'4::diffGroup' => $group,
					'5::diffRoles' => implode( ',', $addedRoles ),
					'6::roleCount' => count( $addedRoles )
				] );
			}
			if ( !empty( $removedRoles ) ) {
				$this->insertLog( 'global-remove', [
					'4::diffGroup' => $group,
					'5::diffRoles' => implode( ',', $removedRoles ),
					'6::roleCount' => count( $removedRoles )
				] );
			}
		}

		foreach ( $nsDiff as $group => $namespaces ) {
			foreach ( $namespaces as $ns => $roles ) {
				$namespaceInfo = $this->services->getNamespaceInfo();
				$nsCanonical = $namespaceInfo->getCanonicalName( $ns );
				if ( $ns === NS_MAIN ) {
					$nsCanonical = wfMessage( 'bs-ns_main' )->plain();
				}
				$addedRoles = [];
				$removedRoles = [];
				foreach ( $roles as $role => $added ) {
					if ( $added ) {
						$addedRoles[] = $role;
					} else {
						$removedRoles[] = $role;
					}
				}
				if ( !empty( $addedRoles ) ) {
					$this->insertLog( 'ns-add', [
						'4::diffGroup' => $group,
						'5::diffRoles' => implode( ',', $addedRoles ),
						'6::roleCount' => count( $addedRoles ),
						'7::ns' => $nsCanonical
					] );
				}
				if ( !empty( $removedRoles ) ) {
					$this->insertLog( 'ns-remove', [
						'4::diffGroup' => $group,
						'5::diffRoles' => implode( ',', $removedRoles ),
						'6::roleCount' => count( $removedRoles ),
						'7::ns' => $nsCanonical
					] );
				}
			}
		}
	}

	/**
	 *
	 * @param string $type
	 * @param array $params
	 */
	private function insertLog( $type, $params ) {
		$targetTitle = SpecialPage::getTitleFor( 'PermissionManager' );
		$user = RequestContext::getMain()->getUser();

		$logger = new ManualLogEntry( 'bs-permission-manager', $type );
		$logger->setPerformer( $user );
		$logger->setTarget( $targetTitle );
		$logger->setParameters( $params );
		$logger->insert( $this->services->getDBLoadBalancer()->getConnection( DB_PRIMARY ) );
	}
}
