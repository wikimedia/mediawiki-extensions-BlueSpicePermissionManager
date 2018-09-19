<?php

namespace BlueSpice\PermissionManager;

class RoleMatrixDiff {
	protected $config;
	protected $newGlobal;
	protected $newNSLockdown;

	protected $oldGlobal;
	protected $oldNSLockdown;

	public function __construct( $config, $newGlobal, $newNSLockdown ) {
		$this->config = $config;
		$this->newGlobal = $newGlobal;
		$this->newNSLockdown = $newNSLockdown;

		$this->oldGlobal = $config->get( 'GroupRoles' );
		$this->oldNSLockdown = $config->get( 'NamespaceRolesLockdown' );
	}

	public function getGlobalDiff() {
		return $this->globalDiff();
	}

	public function getNsDiff() {
		return $this->nsDiff();
	}

	protected function globalDiff() {
		$globalDiff = [];
		foreach( $this->newGlobal as $group => $roleArray ) {
			foreach ( $roleArray as $role => $value ) {
				if( !isset( $this->oldGlobal[ $group ][ $role ] ) ||
						$this->oldGlobal[ $group ][ $role ] !== $value ) {
					$globalDiff[ $group ][ $role ] = $value;
				}
			}
		}

		return $globalDiff;
	}

	protected function nsDiff() {
		$totalDiff = [];
		// Groups that do not have role lockdown anymore
		$negativeDiff = $this->arrayDiffDeep( $this->oldNSLockdown, $this->newNSLockdown );
		// Groups that now have role lockdown which they hadn't had before
		$positiveDiff = $this->arrayDiffDeep( $this->newNSLockdown, $this->oldNSLockdown );
		foreach( $negativeDiff as $ns => $roles ) {
			foreach( $roles as $role => $groups ) {
				foreach( $groups as $group ) {
					$totalDiff[ $group ][ $ns ][ $role ] = false;
				}
			}
		}
		foreach( $positiveDiff as $ns => $roles ) {
			foreach( $roles as $role => $groups ) {
				foreach( $groups as $group ) {
					$totalDiff[ $group ][ $ns ][ $role ] = true;
				}
			}
		}
		return $totalDiff;
	}

	protected function arrayDiffDeep( $old, $new ) {
		$old = (array) $old;
		$new = (array) $new;
		$return = [];

		foreach ( $old as $key => $value ) {
			if( $value instanceof \stdClass ) {
				$value = (array) $value;
			}
			if ( array_key_exists( $key, $new ) ) {
			if ( is_array( $value ) ) {
				$recursiveDiff = $this->arrayDiffDeep( $value, $new[ $key ] );
				if ( count( $recursiveDiff ) ) {
					$return[ $key ] = $recursiveDiff;
				}
			} else {
				if ( $value != $new[ $key ] ) {
					$return[ $key ] = $value;
				}
			}
			} else {
				$return[ $key ] = $value;
			}
		}

		return $return;
	}
}
