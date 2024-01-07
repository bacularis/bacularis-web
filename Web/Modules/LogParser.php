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
 * Copyright (C) 2013-2019 Kern Sibbald
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
 * Bacula logs parser module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class LogParser extends WebModule
{
	public const CLIENT_PATTERN = '/^\s+Client\:\s+"?(?P<client>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const RESTORE_CLIENT_PATTERN = '/^\s+Restore Client\:\s+"?(?P<restore_client>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const POOL_PATTERN = '/^\s+Pool\:\s+"?(?P<pool>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const READ_POOL_PATTERN = '/^\s+Read Pool\:\s+"?(?P<read_pool>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const WRITE_POOL_PATTERN = '/^\s+Write Pool\:\s+"?(?P<write_pool>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const STORAGE_PATTERN = '/^\s{0,2}Storage\:\s+"?(?P<storage>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const READ_STORAGE_PATTERN = '/^\s+Read Storage\:\s+"?(?P<read_storage>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const WRITE_STORAGE_PATTERN = '/^\s+Write Storage\:\s+"?(?P<write_storage>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const FILESET_PATTERN = '/^\s+FileSet\:\s+"?(?P<fileset>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const VERIFY_JOB_PATTERN = '/^\s+Verify Job\:\s+"?(?P<verify_job>[a-zA-Z0-9:.\-_ ]+)"?/i';
	public const JOB_PATTERN = '/^\s+Job\:\s+"?(?P<job>[a-zA-Z0-9:.\-_ ]+)"?\.\d{4}-\d{2}-\d{2}_\d{2}\.\d{2}\.\d{2}_\d{2}/i';
	public const VOLUME_PATTERN = '/^\s+Volume name\(s\)\:\s+"?(?P<volumes>[^\s]+[a-zA-Z0-9:.\-\|_ ]+[^\s]+)"?/i';

	public function parse(array $logs)
	{
		$out = [];
		for ($i = 0; $i < count($logs); $i++) {
			$lines = explode("\n", $logs[$i]);
			for ($j = 0; $j < count($lines); $j++) {
				$out[] = $this->parseLine($lines[$j]);
			}
		}
		return $out;
	}

	private function parseLine($log_line)
	{
		if (preg_match(self::CLIENT_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('client', $match['client']);
			$log_line = str_replace($match['client'], $link, $log_line);
		} elseif (preg_match(self::RESTORE_CLIENT_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('client', $match['restore_client']);
			$log_line = str_replace($match['restore_client'], $link, $log_line);
		} elseif (preg_match(self::POOL_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('pool', $match['pool']);
			$log_line = str_replace($match['pool'], $link, $log_line);
		} elseif (preg_match(self::READ_POOL_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('pool', $match['read_pool']);
			$log_line = str_replace($match['read_pool'], $link, $log_line);
		} elseif (preg_match(self::WRITE_POOL_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('pool', $match['write_pool']);
			$log_line = str_replace($match['write_pool'], $link, $log_line);
		} elseif (preg_match(self::STORAGE_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('storage', $match['storage']);
			$log_line = str_replace($match['storage'], $link, $log_line);
		} elseif (preg_match(self::READ_STORAGE_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('storage', $match['read_storage']);
			$log_line = str_replace($match['read_storage'], $link, $log_line);
		} elseif (preg_match(self::WRITE_STORAGE_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('storage', $match['write_storage']);
			$log_line = str_replace($match['write_storage'], $link, $log_line);
		} elseif (preg_match(self::FILESET_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('fileset', $match['fileset']);
			$log_line = str_replace($match['fileset'], $link, $log_line);
		} elseif (preg_match(self::VERIFY_JOB_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('job', $match['verify_job']);
			$log_line = str_replace($match['verify_job'], $link, $log_line);
		} elseif (preg_match(self::JOB_PATTERN, $log_line, $match) === 1) {
			$link = $this->getLink('job', $match['job']);
			$log_line = str_replace($match['job'], $link, $log_line);
		} elseif (preg_match(self::VOLUME_PATTERN, $log_line, $match) === 1) {
			$volumes = explode('|', $match['volumes']);
			$vol_count = count($volumes);
			for ($i = 0; $i < $vol_count; $i++) {
				$before = ($i > 0) ? '|' : '';
				$after = ($i > 0 && $i < $vol_count) ? '|' : '';
				$link = $before . $this->getLink('volume', $volumes[$i]) . $after;
				$vol_pattern = '/\|?' . $volumes[$i] . '\|?/';
				$log_line = preg_replace($vol_pattern, $link, $log_line);
			}
		}
		return $log_line;
	}

	private function getLink($type, $name)
	{
		return sprintf(
			'<a href="/web/%s/%s">%s</a>',
			$type,
			rawurlencode($name),
			$name
		);
	}
}
