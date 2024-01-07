<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

use Bacularis\Web\Pages\Requirements as WebRequirements;

/**
 * Initialization file.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Initialization
 */

// Check requirements and if are some needed then show requirements page
$service_dir = __DIR__;
new WebRequirements(APPLICATION_WEBROOT, APPLICATION_PROTECTED, $service_dir);

$timezone = 'UTC';
if (!ini_get('date.timezone')) {
	date_default_timezone_set($timezone);
}

/**
 * Set time limit to 60 seconds.
 * Please note that async requests default times out after 30 seconds
 * if not set other value.
 */
set_time_limit(60);

/*
 * Support for web servers (for example Lighttpd) which do not provide direct
 * info about HTTP Basic auth to PHP superglobal $_SERVER array.
 */
if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW']) && isset($_SERVER['HTTP_AUTHORIZATION']) && strpos($_SERVER['HTTP_AUTHORIZATION'], 'Basic') === 0) {
	/*
	 * Substring 'Basic ' from  HTTP authorization header
	 * Example 'Basic YWRtaW46YWRtaW4=' becomes 'YWRtaW46YWRtaW4='
	 */
	$encoded_credentials = substr($_SERVER['HTTP_AUTHORIZATION'], 6);
	$decoded_credentials = base64_decode($encoded_credentials);

	// initialize required auth superglobal $_SERVER array
	[$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']] = explode(':', $decoded_credentials);
}
