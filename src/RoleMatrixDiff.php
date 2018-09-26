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

		$this->oldGlobal = $config->get( 'GroupRoles' );
		$this->oldNSLockdown = $config->get( 'NamespaceRolesLockdown' );

		foreach( $newNSLockdown as $key => $value ) {
			// Needs to be normalized first
			$key = (int) $key;
			$this->newNSLockdown[$key] = (array) $value;
		}

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
					// If group is removed and later added, net result is nothing changed
					// so remove it from both arrays
					if( isset( $positiveDiff[$ns][$role] ) ) {
						if( in_array( $group, $positiveDiff[$ns][$role] ) ) {
							$keyInNegative = array_search( $group, $positiveDiff[$ns][$role] );
							unset( $positiveDiff[$ns][$role][$keyInNegative] );
							continue;
						}
					}
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

	/**
	 * The arrays that get passed have this structure: 
	 *
	 * array(                   <= ASSOC
	 *   2 => array(            <= ASSOC
	 *     'editor' => array(   <= SEQUENTIAL
	 *        'user',
	 *        'sysop'
	 *     )
	 *   )
	 * )
	 *
	 * But the first array has numeric keys, which very well may be in order (ns ids). This
	 * makes it very difficult to know when array should be treated as seq and when as assoc.
	 * 
	 * Treating inner-most array (groups - which are seq) as assoc may lead to
	 * wrong results, as values may be in different order and hence have different keys.
	 *
	 * @param array $old
	 * @param array $new
	 * @return array
	 */
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
