<?php

$GLOBALS['bsgGroupRoles']['bureaucrat']['accountmanager'] = true;
$GLOBALS['bsgGroupRoles']['*']['reader'] = false;
$GLOBALS['bsgGroupRoles']['bot']['bot'] = true;

$GLOBALS['bsgNamespaceRoleLockdown'][NS_USER]['reader'] = [ 'user' ];
$GLOBALS['bsgNamespaceRoleLockdown'][NS_MAIN]['user'] = [ '*' ];
