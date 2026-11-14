<?php
declare(strict_types=1);

/**
 * Default-deny first-time setup exposure.
 *
 * Web setup is only available if an operator deliberately enables it in
 * the server environment AND the first-time installation is not locked.
 * Never accept an HTTP parameter, cookie or request header as authority.
 */
function iworks_web_setup_allowed(bool $operatorOptIn, bool $setupLocked): bool
{
    return $operatorOptIn && !$setupLocked;
}
