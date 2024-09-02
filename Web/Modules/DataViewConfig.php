<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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
 * Manage data view configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class DataViewConfig extends ConfigFileModule
{
	/**
	 * Data view config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.dataview';

	/**
	 * Data view config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the view name.
	 */
	public const VIEW_PATTERN = '(?!^\d+$)[\p{L}\p{N}\p{Z}\-\'\\/\\(\\)\\{\\}:.#~_,+!$]{1,100}';

	/**
	 * Stores user data view config.
	 */
	private $config;

	/**
	 * Get data view config.
	 *
	 * @return array data view config
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		return $this->config;
	}

	/**
	 * Set data view config.
	 *
	 * @param array $config data view config
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
	 * Get user data view config.
	 *
	 * @param string $username
	 * @return array user data view config
	 */
	public function getDataViewConfig(string $username): array
	{
		$view_config = [];
		$config = $this->getConfig();
		if (key_exists($username, $config)) {
			$view_config = $config[$username];
		}
		if (is_array($view_config)) {
			foreach ($view_config as $view => $data) {
				foreach ($data as $name => $value) {
					parse_str($value, $result);
					$view_config[$view][$name] = $result;
				}
			}
		}
		return $view_config;
	}

	/**
	 * Set single user data view config.
	 *
	 * @param string $username user name
	 * @param array $view_config user data view configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setDataViewConfig(string $username, array $view_config): bool
	{
		$config = $this->getConfig();
		foreach ($view_config as $view => $data) {
			foreach ($data as $name => $value) {
				$vw = http_build_query($value);
				$view_config[$view][$name] = $vw;
			}
		}
		$config[$username] = $view_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single user data view config.
	 *
	 * @param string $username user name
	 * @param string $view view name
	 * @param string $item item name (tab name)
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeDataViewConfig(string $username, string $view, string $item): bool
	{
		$ret = false;
		$config = $this->getConfig();
		if (isset($config[$username][$view][$item])) {
			unset($config[$username][$view][$item]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}
}
