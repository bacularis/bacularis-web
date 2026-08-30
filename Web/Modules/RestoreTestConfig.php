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

/**
 * Manage restore test configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class RestoreTestConfig extends ConfigFileModule
{
	/**
	 * Restore test config file path.
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.restore_tests';

	/**
	 * Restore test config file format.
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the restore test name.
	 */
	public const NAME_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * Restore test backup source types.
	 */
	public const SOURCE_TYPE_BACKUP_JOB = 'backup_job';
	public const SOURCE_TYPE_BACKUP_JOBID = 'backup_jobid';

	/**
	 * Restore test backup source selections.
	 */
	public const SOURCE_SELECTION_LATEST_SUCCESSFUL = 'latest_successful';
	public const SOURCE_SELECTION_LATEST_SUCCESSFUL_TIME_RANGE = 'latest_successful_time_range';
	public const SOURCE_SELECTION_JOB_LEVELS = 'job_levels';

	/**
	 * Restore test scope types.
	 */
	public const RESTORE_SCOPE_ENTIRE_BACKUP = 'entire_backup';
	public const RESTORE_SCOPE_VERIFICATION_RULES_PATHS = 'verification_rules_paths';
	public const RESTORE_SCOPE_RANDOM_SAMPLE = 'random_sample';
	public const RESTORE_SCOPE_SINGLE_BACKUP = 'single_backup';

	/**
	 * Restore verification method types.
	 */
	public const RESTORE_VERIFICATION_METHOD_RULES = 'rules';
	public const RESTORE_VERIFICATION_METHOD_VERIFY_JOB = 'verify_job';

	/**
	 * Stores restore test config.
	 */
	private $config;

	/**
	 * Get restore test config.
	 *
	 * @return array restore test config
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			if (is_array($this->config)) {
				foreach ($this->config as $key => $value) {
					$value['name'] = $key;
					$this->config[$key] = $value;
				}
			}
		}
		return $this->config;
	}

	/**
	 * Set restore test config.
	 *
	 * @param array $config restore test config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Get single restore test config.
	 *
	 * @param string $name restore test name
	 * @return array restore test config
	 */
	public function getRestoreTestConfig(string $name): array
	{
		$restore_test_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$restore_test_config = $config[$name];
			$restore_test_config['name'] = $name;
		}
		return $restore_test_config;
	}

	/**
	 * Set single restore test config.
	 *
	 * @param string $name restore test name
	 * @param array $restore_test_config restore test configuration
	 * @return bool true if restore test saved successfully, otherwise false
	 */
	public function setRestoreTestConfig(string $name, array $restore_test_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $restore_test_config;
		return $this->setConfig($config);
	}

	/**
	 * Update single restore test config.
	 *
	 * @param string $name restore test name
	 * @param array $restore_test_config restore test configuration
	 * @return bool true if restore test saved successfully, otherwise false
	 */
	public function updateRestoreTestConfig(string $name, array $restore_test_config): bool
	{
		$result = false;
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$config[$name] = array_merge(
				$config[$name],
				$restore_test_config
			);
			$result = $this->setConfig($config);
		}
		return $result;
	}

	/**
	 * Remove single restore test config.
	 *
	 * @param string $name restore test name
	 * @return bool true if restore test removed successfully, otherwise false
	 */
	public function removeRestoreTestConfig(string $name): bool
	{
		$ret = false;
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			// Remove dependencies first
			$web_access = $this->getModule('web_access_config');
			if (key_exists('web_admin_token', $config[$name])) {
				$web_access->removeWebAccessConfig($config[$name]['web_admin_token']);
			}
			if (key_exists('web_verify_token', $config[$name])) {
				$web_access->removeWebAccessConfig($config[$name]['web_verify_token']);
			}
			if (key_exists('web_job_token', $config[$name])) {
				$web_access->removeWebAccessConfig($config[$name]['web_job_token']);
			}
			unset($config[$name]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove restore tests config.
	 *
	 * @param array $names restore test names
	 * @return bool true if restore tests removed successfully, otherwise false
	 */
	public function removeRestoreTestsConfig(array $names): bool
	{
		$ret = true;
		for ($i = 0; $i < count($names); $i++) {
			$ret = $this->removeRestoreTestConfig($names[$i]);
			if (!$ret) {
				break;
			}
		}
		return $ret;
	}

	/**
	 * Check if restore test config exists.
	 *
	 * @param string $name restore test name
	 * @return bool true if restore test config exists, otherwise false
	 */
	public function restoreTestConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}
}
