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

namespace Bacularis\Web\Modules;

/**
 * Module responsible for providing information about job.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class JobInfo extends WebModule
{
	public const RESOURCE_PATTERN = '/(?P<resource>\S+(?=:))?:?(\s+((?P<directive>\S+)=(?P<value>[\s\S]*?(?=\s\S+=.+|$))))/';
	public const JOB_TO_VERIFY_PATTERN = '/(?P<directive>JobToVerify)\s(?P<value>[\s\S]*\S)\s*(--\>[\s\S]+)$/';
	public const COMMAND_ACL_USED_BY_WEB = [
		'gui',
		'.api',
		'.jobs',
		'.ls',
		'.client',
		'.fileset',
		'.pool',
		'.status',
		'.storage',
		'.bvfs_get_jobids',
		'.bvfs_update',
		'.bvfs_lsdirs',
		'.bvfs_lsfiles',
		'.bvfs_versions',
		'.bvfs_restore',
		'.bvfs_cleanup',
		'restore',
		'show',
		'estimate',
		'run',
		'delete',
		'cancel',
		'reload'
	];

	public const DEFAULT_MAX_JOBS = 10000;

	public function parseResourceDirectives(array $show_out)
	{
		$result = [];
		$resource = [];
		$res = null;
		for ($i = 1; $i < count($show_out); $i++) {
			if (preg_match_all(self::RESOURCE_PATTERN, $show_out[$i], $match) > 0) {
				if (!empty($match['resource'][0])) {
					if (count($resource) == 1) {
						/**
						 * Check key to not overwrite already existing resource
						 * because in some cases there can be for example two
						 * Autochanger resources: one from Pool and second from NextPool.
						 */
						if (!key_exists($res, $result)) {
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
				if ($res === 'messages' && preg_match(self::JOB_TO_VERIFY_PATTERN, $show_out[$i], $match) === 1) {
					/**
					 * In the show job is displayed wrong show job output for verifyjob directive.
					 * In versions prior 11 verify job was not displayed at all, and in 11.0.x it is
					 * displayed but together with messages (missing EOL):
					 *
					 *   --> JobToVerify dokumenty  --> Messages: name=Standard
					 *
					 * From this reason there is separate pattern for verify job.
					 */
					$directive = strtolower($match['directive']);
					$result['job'][$directive] = $match['value'];
				}
			}
		}
		if (count($resource) == 1) {
			$result = array_merge($result, $resource);
		}
		return $result;
	}
}
