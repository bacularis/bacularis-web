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
 * Copyright (C) 2013-2021 Kern Sibbald
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

use Bacularis\Common\Modules\Logging;
use Prado\Prado;

/**
 * Module responsible for managing messages log.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class MessagesLog extends WebModule
{
	/**
	 * Messages log file path.
	 */
	public const LOG_FILE_PATH = 'Bacularis.Web.Logs.messages';

	/**
	 * Messages log file extension.
	 */
	public const LOG_FILE_EXT = '.log';

	/**
	 * Maximum number of lines to keep.
	 */
	public const MAX_LINES = 1000;

	/**
	 * Append messages to messages log.
	 * NOTE: Max. lines limit is taken into acocunt.
	 *
	 * @param array $logs log messages
	 * @return array logs stored in log file
	 */
	public function append(array $logs)
	{
		$logs_all = [];
		$f = Prado::getPathOfNamespace(self::LOG_FILE_PATH, self::LOG_FILE_EXT);
		$fp = fopen($f, 'c+');
		if (flock($fp, LOCK_EX)) {
			$fsize = filesize($f);
			$messages_file = $fsize > 0 ? fread($fp, $fsize) : '';
			$logs_file = explode(PHP_EOL, $messages_file);
			$logs_all = array_merge($logs_file, $logs);
			$all_len = count($logs_all);
			if ($all_len > self::MAX_LINES) {
				$len = $all_len - self::MAX_LINES;
				array_splice($logs_all, 0, $len);
			}
			$messages = implode(PHP_EOL, $logs_all);
			rewind($fp);
			ftruncate($fp, 0);
			fwrite($fp, $messages);
			fflush($fp);
			flock($fp, LOCK_UN);
		} else {
			$emsg = 'Could not get the exclusive lock: ' . $f;
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		fclose($fp);
		return $logs_all;
	}

	/**
	 * Truncate messages log.
	 *
	 */
	public function truncate()
	{
		$f = Prado::getPathOfNamespace(self::LOG_FILE_PATH, self::LOG_FILE_EXT);
		$fp = fopen($f, 'w');
		if (flock($fp, LOCK_EX)) {
			fflush($fp);
			flock($fp, LOCK_UN);
		} else {
			$emsg = 'Could not get the exclusive lock: ' . $f;
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		fclose($fp);
	}

	/**
	 * Save logs to file.
	 *
	 * @param array $logs log messages
	 */
	public function save(array $logs)
	{
		$f = Prado::getPathOfNamespace(self::LOG_FILE_PATH, self::LOG_FILE_EXT);
		$fp = fopen($f, 'a');
		if (flock($fp, LOCK_EX)) {
			$messages = implode(PHP_EOL, $logs);
			fwrite($fp, $messages);
			fflush($fp);
			flock($fp, LOCK_UN);
		} else {
			$emsg = 'Could not get the exclusive lock: ' . $f;
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		fclose($fp);
	}

	/**
	 * Read logs from file.
	 *
	 * @return array log messages
	 */
	public function read()
	{
		$logs = [];
		$f = Prado::getPathOfNamespace(self::LOG_FILE_PATH, self::LOG_FILE_EXT);
		if (!file_exists($f)) {
			return $logs;
		}
		$fp = fopen($f, 'r');
		if (flock($fp, LOCK_SH)) {
			$fsize = filesize($f);
			$messages = $fsize > 0 ? fread($fp, $fsize) : '';
			$logs = explode(PHP_EOL, $messages);
			flock($fp, LOCK_UN);
		} else {
			$emsg = 'Could not get the shared lock: ' . $f;
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		fclose($fp);
		return $logs;
	}
}
