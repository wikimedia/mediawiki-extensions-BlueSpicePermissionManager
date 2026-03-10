<?php

namespace BlueSpice\PermissionManager;

use BlueSpice\PermissionManager\Logging\GroupManagerSpecialLogLogger;
use BlueSpice\UserManager\GroupManager as UserManagerGroupManager;
use BlueSpice\UserManager\Logging\GroupManagerSpecialLogLogger as LoggingGroupManagerSpecialLogLogger;
use MediaWiki\Config\Config;
use MediaWiki\HookContainer\HookContainer;
use MWStake\MediaWiki\Component\DynamicConfig\DynamicConfigManager;
use Psr\Log\LoggerInterface;
use Wikimedia\Rdbms\ILoadBalancer;

class GroupManager extends UserManagerGroupManager {

	/**
	 * @param DynamicConfigManager $configManager
	 * @param ILoadBalancer $lb
	 * @param Config $config Main config (wg)
	 * @param HookContainer $hookContainer
	 * @param LoggerInterface $logger
	 * @param GroupManagerSpecialLogLogger $spLogger
	 */
	public function __construct(
		DynamicConfigManager $configManager, ILoadBalancer $lb, Config $config,
		HookContainer $hookContainer, LoggerInterface $logger, GroupManagerSpecialLogLogger $spLogger
	) {
		$userManagerLogger = new LoggingGroupManagerSpecialLogLogger();
		parent::__construct( $configManager, $lb, $config, $hookContainer, $logger, $userManagerLogger );
	}
}
