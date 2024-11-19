<?php

namespace BlueSpice\PermissionManager\Rest;

use BlueSpice\UtilityFactory;
use MediaWiki\Rest\SimpleHandler;
use MWStake\MediaWiki\Component\Utils\Utility\GroupHelper;
use Wikimedia\ParamValidator\ParamValidator;

class RetrieveGroups extends SimpleHandler {

	/**
	 * @var GroupHelper
	 */
	private GroupHelper $groupHelper;

	/**
	 * @param UtilityFactory $utilityFactory
	 */
	public function __construct( UtilityFactory $utilityFactory ) {
		$this->groupHelper = $utilityFactory->getGroupHelper();
	}

	public function execute() {
		$params = $this->getValidatedParams();
		$query = $params['query'] ?? null;
		$type = $this->getArray( $params['type'] ?? 'explicit' );
		$blacklist = $this->getArray( $params['blacklist'] ?? '' );

		$data = [];
		$conf = [
			'filter' => $type
		];
		if ( $blacklist ) {
			$conf['blacklist'] = $blacklist;
		}
		$groups = $this->groupHelper->getAvailableGroups( $conf );

		foreach ( $groups as $group ) {
			$display = $group;
			$labelMsg = "group-$group";
			if ( $group === '*' ) {
				$labelMsg = "group-anon";
			}
			if ( $group === 'user' ) {
				$labelMsg = "group-bs-user";
			}
			$labelMsg = wfMessage( $labelMsg );
			if ( $labelMsg->exists() ) {
				$display = $labelMsg->plain();
			}

			if ( $query && !$this->queryApplies( $query, [ $group, $display ] ) ) {
				continue;
			}

			$groupType = $this->groupHelper->getGroupType( $group );
			$data[] = [
				'group_name' => $group,
				'custom_group' => $groupType === 'custom',
				'group_type' => $groupType,
				'displayname' => $display,
			];
		}
		return $data;
	}

	/**
	 * @param string $query
	 * @param array $labels
	 * @return bool
	 */
	private function queryApplies( string $query, array $labels ): bool {
		$label = implode( ' ', array_unique( $labels ) );
		$label = mb_strtolower( $label );
		$query = mb_strtolower( $query );
		return str_contains( $label, $query );
	}

	/**
	 * @return array[]
	 */
	public function getParamSettings() {
		return [
			'query' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'type' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'blacklist' => [
				static::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
		];
	}

	/**
	 * @param string $param
	 * @return array
	 */
	private function getArray( string $param ): array {
		return explode( '|', $param );
	}
}
