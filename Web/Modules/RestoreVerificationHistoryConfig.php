<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\ConfigFileModule;
use Bacularis\Common\Modules\Miscellaneous;

/**
 * Manage restore verification history configuration.
 *
 * Restore verification checker states are stored as JSON values in an INI
 * configuration file. History is grouped by restore test, logical path,
 * checker and checker configuration hash.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class RestoreVerificationHistoryConfig extends ConfigFileModule
{
	/**
	 * Restore verification history config file path.
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.restore_verification_history';

	/**
	 * Restore verification history config file format.
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * History config view name.
	 */
	private const HISTORY_VIEW_NAME = 'path';

	/**
	 * Lock file suffix.
	 */
	private const LOCK_FILE_SUFFIX = '.lock';

	/**
	 * JSON encode flags.
	 */
	private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/**
	 * Stores restore verification history config.
	 */
	private $config;

	/**
	 * Get restore verification history config.
	 *
	 * @return array restore verification history config
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			$lock = $this->lockConfig(LOCK_SH);
			if ($lock === false) {
				return [];
			}

			$this->config = $this->readConfig(
				self::CONFIG_FILE_PATH,
				self::CONFIG_FILE_FORMAT
			);
			$this->unlockConfig($lock);
		}
		return $this->config;
	}

	/**
	 * Set restore verification history config.
	 *
	 * @param array $config restore verification history config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		$lock = $this->lockConfig(LOCK_EX);
		if ($lock === false) {
			return false;
		}

		$result = $this->writeHistoryConfig($config);
		$this->unlockConfig($lock);
		return $result;
	}

	/**
	 * Get checker history.
	 *
	 * @param string $test_name restore test name
	 * @param string $path logical restore path
	 * @param string $checker checker class name
	 * @param string $config_hash checker configuration hash
	 * @return array checker history states
	 */
	public function getHistory(string $test_name, string $path, string $checker, string $config_hash = ''): array
	{
		$config = $this->getConfig();
		$test_key = self::encodeKey($test_name);
		$path_key = self::encodeKey($path);
		if (!isset($config[$test_key][self::HISTORY_VIEW_NAME][$path_key])) {
			return [];
		}

		$path_history = self::decodeHistory(
			$config[$test_key][self::HISTORY_VIEW_NAME][$path_key]
		);
		if (!isset($path_history[$checker]) || !is_array($path_history[$checker])) {
			return [];
		}

		if ($config_hash !== '') {
			return $path_history[$checker][$config_hash] ?? [];
		}
		return $path_history[$checker];
	}

	/**
	 * Append checker state to history.
	 *
	 * This method locks the complete read-modify-write operation to avoid
	 * losing history when multiple restore verification jobs finish at the
	 * same time.
	 *
	 * @param string $test_name restore test name
	 * @param string $path logical restore path
	 * @param string $checker checker class name
	 * @param string $config_hash checker configuration hash
	 * @param array $state checker state
	 * @param int $history_size maximum number of states to keep
	 * @return bool true if history saved successfully, otherwise false
	 */
	public function appendHistory(string $test_name, string $path, string $checker, string $config_hash, array $state, int $history_size): bool
	{
		if ($test_name === '' || $path === '' || $checker === '' || $config_hash === '') {
			return false;
		}

		$history_size = max(1, $history_size);
		$lock = $this->lockConfig(LOCK_EX);
		if ($lock === false) {
			return false;
		}

		$config = $this->readConfig(
			self::CONFIG_FILE_PATH,
			self::CONFIG_FILE_FORMAT
		);

		$test_key = self::encodeKey($test_name);
		$path_key = self::encodeKey($path);
		$path_history = [];
		if (isset($config[$test_key][self::HISTORY_VIEW_NAME][$path_key])) {
			$path_history = self::decodeHistory(
				$config[$test_key][self::HISTORY_VIEW_NAME][$path_key]
			);
		}

		if (!isset($path_history[$checker]) || !is_array($path_history[$checker])) {
			$path_history[$checker] = [];
		}
		if (!isset($path_history[$checker][$config_hash]) || !is_array($path_history[$checker][$config_hash])) {
			$path_history[$checker][$config_hash] = [];
		}

		$path_history[$checker][$config_hash][] = $state;
		$path_history[$checker][$config_hash] = array_slice(
			$path_history[$checker][$config_hash],
			-$history_size
		);

		$json = json_encode($path_history, self::JSON_FLAGS);
		if ($json === false) {
			$this->unlockConfig($lock);
			return false;
		}

		if (!isset($config[$test_key])) {
			$config[$test_key] = [];
		}
		if (!isset($config[$test_key][self::HISTORY_VIEW_NAME])) {
			$config[$test_key][self::HISTORY_VIEW_NAME] = [];
		}
		$config[$test_key][self::HISTORY_VIEW_NAME][$path_key] = $json;

		$result = $this->writeHistoryConfig($config);
		$this->unlockConfig($lock);
		return $result;
	}

	/**
	 * Remove restore test history.
	 *
	 * @param string $test_name restore test name
	 * @return bool true if history removed successfully, otherwise false
	 */
	public function removeTestHistory(string $test_name): bool
	{
		$lock = $this->lockConfig(LOCK_EX);
		if ($lock === false) {
			return false;
		}

		$config = $this->readConfig(
			self::CONFIG_FILE_PATH,
			self::CONFIG_FILE_FORMAT
		);
		$test_key = self::encodeKey($test_name);

		if (!isset($config[$test_key])) {
			$this->unlockConfig($lock);
			return true;
		}

		unset($config[$test_key]);
		$result = $this->writeHistoryConfig($config);
		$this->unlockConfig($lock);
		return $result;
	}

	/**
	 * Remove checker history for path.
	 *
	 * @param string $test_name restore test name
	 * @param string $path logical restore path
	 * @param string $checker checker class name
	 * @return bool true if history removed successfully, otherwise false
	 */
	public function removeCheckerHistory(string $test_name, string $path, string $checker): bool
	{
		$lock = $this->lockConfig(LOCK_EX);
		if ($lock === false) {
			return false;
		}

		$config = $this->readConfig(
			self::CONFIG_FILE_PATH,
			self::CONFIG_FILE_FORMAT
		);

		$test_key = self::encodeKey($test_name);
		$path_key = self::encodeKey($path);
		if (!isset($config[$test_key][self::HISTORY_VIEW_NAME][$path_key])) {
			$this->unlockConfig($lock);
			return true;
		}

		$path_history = self::decodeHistory(
			$config[$test_key][self::HISTORY_VIEW_NAME][$path_key]
		);
		if (!isset($path_history[$checker])) {
			$this->unlockConfig($lock);
			return true;
		}

		unset($path_history[$checker]);

		if ($path_history) {
			$json = json_encode($path_history, self::JSON_FLAGS);
			if ($json === false) {
				$this->unlockConfig($lock);
				return false;
			}
			$config[$test_key][self::HISTORY_VIEW_NAME][$path_key] = $json;
		} else {
			unset($config[$test_key][self::HISTORY_VIEW_NAME][$path_key]);
		}

		if (empty($config[$test_key][self::HISTORY_VIEW_NAME])) {
			unset($config[$test_key][self::HISTORY_VIEW_NAME]);
		}
		if (empty($config[$test_key])) {
			unset($config[$test_key]);
		}

		$result = $this->writeHistoryConfig($config);
		$this->unlockConfig($lock);
		return $result;
	}

	/**
	 * Decode checker history JSON.
	 *
	 * @param mixed $value JSON history value
	 * @return array checker history
	 */
	private static function decodeHistory($value): array
	{
		if (!is_string($value) || $value === '') {
			return [];
		}

		$value = Miscellaneous::stripQuotes($value);
		$history = json_decode($value, true);
		return is_array($history) ? $history : [];
	}

	/**
	 * Encode config key.
	 *
	 * INI sections and option names have characters with special meaning.
	 * Percent-encoding keeps keys safe without removing information or
	 * creating collisions by stripping characters from paths or names.
	 *
	 * @param string $key config key
	 * @return string encoded config key
	 */
	private static function encodeKey(string $key): string
	{
		return rawurlencode($key);
	}

	/**
	 * Write restore verification history config.
	 *
	 * The caller is responsible for acquiring an exclusive config lock.
	 *
	 * @param array $config restore verification history config
	 * @return bool true if config saved successfully, otherwise false
	 */
	private function writeHistoryConfig(array $config): bool
	{
		$result = $this->writeConfig(
			$config,
			self::CONFIG_FILE_PATH,
			self::CONFIG_FILE_FORMAT
		);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Lock restore verification history config.
	 *
	 * A separate lock file is used so the lock remains valid while the
	 * configuration file is being rewritten.
	 *
	 * @param int $operation lock operation
	 * @return false|resource lock file handle or false on error
	 */
	private function lockConfig(int $operation)
	{
		$config_path = $this->getConfigRealPath(self::CONFIG_FILE_PATH);
		$lock = @fopen($config_path . self::LOCK_FILE_SUFFIX, 'c');
		if ($lock === false) {
			return false;
		}

		if (!flock($lock, $operation)) {
			fclose($lock);
			return false;
		}
		return $lock;
	}

	/**
	 * Unlock restore verification history config.
	 *
	 * @param resource $lock lock file handle
	 */
	private function unlockConfig($lock): void
	{
		flock($lock, LOCK_UN);
		fclose($lock);
	}
}
