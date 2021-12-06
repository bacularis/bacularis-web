<?php
/*
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

Prado::using('Application.:Web.Class.WebModule');

/**
 * Module responsible for providing information about job.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum Web
 */
class JobInfo extends WebModule {

	const RESOURCE_PATTERN = '/(?P<resource>\S+(?=:))?:?(\s+((?P<directive>\S+)=(?P<value>[\s\S]*?(?=\s\S+=.+|$))))/';

	public function parseResourceDirectives(array $show_out) {
		$result = [];
		$resource = [];
		$res = null;
		for ($i = 1; $i < count($show_out); $i++) {
			if (preg_match_all(self::RESOURCE_PATTERN, $show_out[$i], $match) > 0) {
				if (!empty($match['resource'][0])) {
					if (count($resource) == 1)  {
						/**
						 * Check key to not overwrite already existing resource
						 * because in some cases there can be for example two
						 * Autochanger resources: one from Pool and second from NextPool.
						 */
						if (!key_exists($res, $result))  {
							$result = array_merge($result, $resource);
						}
						$resource = [];
					}
					$res = strtolower($match['resource'][0]);
				}
				if (!key_exists($res, $resource)) {
					$resource[$res] = [];
				}
				for ($j = 0; $j < count($match['directive']); $j++) {
					$directive = strtolower($match['directive'][$j]);
					$value = $match['value'][$j];
					$resource[$res][$directive] = $value;
				}
			}
		}
		if (count($resource) == 1) {
			$result = array_merge($result, $resource);
		}
		return $result;
	}
}
?>
