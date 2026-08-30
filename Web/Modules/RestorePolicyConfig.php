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
 * Manage restore policy configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class RestorePolicyConfig extends ConfigFileModule
{
	/**
	 * Restore policy config file path.
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.restore_policies';

	/**
	 * Restore policy config file format.
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the restore policy name.
	 */
	public const NAME_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * Restore policy run methods.
	 */
	public const RUN_METHOD_SCHEDULE = 'schedule';
	public const RUN_METHOD_AFTER_BACKUP = 'after_backup';
	public const RUN_METHOD_MANUALLY = 'manually';

	/**
	 * Stores restore policy config.
	 */
	private $config;

	/**
	 * Get restore policy config.
	 *
	 * @return array restore policy config
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
	 * Set restore policy config.
	 *
	 * @param array $config restore policy config
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
	 * Get single restore policy config.
	 *
	 * @param string $name restore policy name
	 * @return array restore policy config
	 */
	public function getRestorePolicyConfig(string $name): array
	{
		$restore_policy_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$restore_policy_config = $config[$name];
			$restore_policy_config['name'] = $name;
		}
		return $restore_policy_config;
	}

	/**
	 * Set single restore policy config.
	 *
	 * @param string $name restore policy name
	 * @param array $restore_policy_config restore policy configuration
	 * @return bool true if restore policy saved successfully, otherwise false
	 */
	public function setRestorePolicyConfig(string $name, array $restore_policy_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $restore_policy_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single restore policy config.
	 *
	 * @param string $name restore policy name
	 * @return bool true if restore policy removed successfully, otherwise false
	 */
	public function removeRestorePolicyConfig(string $name): bool
	{
		$ret = false;
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			unset($config[$name]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove restore policies config.
	 *
	 * @param array $names restore policy names
	 * @return bool true if restore policies removed successfully, otherwise false
	 */
	public function removeRestorePoliciesConfig(array $names): bool
	{
		$ret = true;
		for ($i = 0; $i < count($names); $i++) {
			$ret = $this->removeRestorePolicyConfig($names[$i]);
			if (!$ret) {
				break;
			}
		}
		return $ret;
	}

	/**
	 * Check if restore policy config exists.
	 *
	 * @param string $name restore policy name
	 * @return bool true if restore policy config exists, otherwise false
	 */
	public function restorePolicyConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}
}
