<?php

namespace BlueSpice\PermissionManager;

use BlueSpice\ExtensionAttributeBasedRegistry;
use BlueSpice\Permission\IRole;
use BlueSpice\Permission\RoleManager;
use InvalidArgumentException;
use ManualLogEntry;
use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use Psr\Log\LoggerInterface;
use Throwable;
use UnexpectedValueException;

class PermissionManager {
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
	/** @var DynamicConfigManager */
	private DynamicConfigManager $dynamicConfigManager;

	/**
	 * @param RoleManager $roleManager
	 * @param MediaWikiServices $services
	 * @param Config $config
	 * @param LoggerInterface $logger
	 * @param HookContainer $hookContainer
	 * @param DynamicConfigManager $dynamicConfigManager
	 */
	public function __construct(
		RoleManager $roleManager,
		MediaWikiServices $services,
		Config $config,
		LoggerInterface $logger,
		HookContainer $hookContainer,
		DynamicConfigManager $dynamicConfigManager
	) {
		$this->roleManager = $roleManager;
		$this->config = $config;
		$this->logger = $logger;
		$this->hookContainer = $hookContainer;
		$this->services = $services;
		$this->dynamicConfigManager = $dynamicConfigManager;

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
		} catch ( Throwable $exception ) {
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
			throw new UnexpectedValueException(
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
	 * @param IRole|string $role
	 * @param bool $includeDesc
	 * @return array
	 */
	public function getRolePermissions( $role, $includeDesc = false ) {
		if ( $role instanceof IRole ) {
			$role = $role->getName();
		}
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
	 * @param array $data
	 * @return array
	 */
	public function saveRoles( $data ) {
		if ( !isset( $data ) || !isset( $data['groupRoles'] ) || !isset( $data['roleLockdown'] ) ) {
			throw new InvalidArgumentException();
		}

		$groupRoles = $data['groupRoles'];
		$roleLockdown = $data['roleLockdown'];

		$status = $this->hookContainer->run(
			'BsPermissionManager::beforeSaveRoles', [ &$groupRoles, &$roleLockdown ]
		);

		if ( !$status ) {
			throw new \RuntimeException( 'Hook aborted' );
		}

		return $this->persistRoles( $groupRoles, $roleLockdown );
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
	 * @param array $groupRoles
	 * @param array $roleLockdown
	 * @return array
	 */
	private function persistRoles( $groupRoles, $roleLockdown ) {
		if ( $this->services->getReadOnlyMode()->isReadOnly() ) {
			return [
				'success' => false,
				'message' => wfMessage( 'bs-readonly', $this->services->getReadOnlyMode()->getReason() )->plain()
			];
		}

		$roleMatrixDiff = new RoleMatrixDiff( $this->config, $groupRoles, $roleLockdown );
		$globalDiff = $roleMatrixDiff->getGlobalDiff();
		$nsDiff = $roleMatrixDiff->getNsDiff();

		$status = $this->dynamicConfigManager->storeConfig(
			$this->dynamicConfigManager->getConfigObject( 'bs-permissionmanager-roles' ),
			[ 'groupRoles' => $groupRoles, 'roleLockdown' => $roleLockdown ]
		);
		if ( $status ) {
			$this->doLog( $globalDiff, $nsDiff );
			return [ 'success' => true ];
		} else {
			return [
				'success' => false,
				'message' => Message::newFromKey(
					'bs-permissionmanager-write-config-file-error',
					'bs-permissionmanager-roles'
				)->plain()
			];
		}
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
