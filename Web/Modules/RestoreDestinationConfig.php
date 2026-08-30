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
 * Manage restore destination configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class RestoreDestinationConfig extends ConfigFileModule
{
	/**
	 * Restore destination config file path.
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.restore_destinations';

	/**
	 * Restore destination config file format.
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the restore destination name.
	 */
	public const NAME_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * Restore destination modes.
	 */
	public const RESTORE_MODE_PREFIXED_PATH = 'prefixed_path';
	public const RESTORE_MODE_ORIGINAL_PATH = 'original_path';

	/**
	 * Stores restore destination config.
	 */
	private $config;

	/**
	 * Get restore destination config.
	 *
	 * @return array restore destination config
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
	 * Set restore destination config.
	 *
	 * @param array $config restore destination config
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
	 * Get single restore destination config.
	 *
	 * @param string $name restore destination name
	 * @return array restore destination config
	 */
	public function getRestoreDestinationConfig(string $name): array
	{
		$restore_destination_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$restore_destination_config = $config[$name];
			$restore_destination_config['name'] = $name;
		}
		return $restore_destination_config;
	}

	/**
	 * Set single restore destination config.
	 *
	 * @param string $name restore destination name
	 * @param array $restore_destination_config restore destination configuration
	 * @return bool true if restore destination saved successfully, otherwise false
	 */
	public function setRestoreDestinationConfig(string $name, array $restore_destination_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $restore_destination_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single restore destination config.
	 *
	 * @param string $name restore destination name
	 * @return bool true if restore destination removed successfully, otherwise false
	 */
	public function removeRestoreDestinationConfig(string $name): bool
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
	 * Remove restore destinations config.
	 *
	 * @param array $names restore destination names
	 * @return bool true if restore destinations removed successfully, otherwise false
	 */
	public function removeRestoreDestinationsConfig(array $names): bool
	{
		$ret = true;
		for ($i = 0; $i < count($names); $i++) {
			$ret = $this->removeRestoreDestinationConfig($names[$i]);
			if (!$ret) {
				break;
			}
		}
		return $ret;
	}

	/**
	 * Check if restore destination config exists.
	 *
	 * @param string $name restore destination name
	 * @return bool true if restore destination config exists, otherwise false
	 */
	public function restoreDestinationConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}
}
