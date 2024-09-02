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
 * Manage patterns configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class PatternConfig extends ConfigFileModule
{
	/**
	 * Pattern config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.pattern';

	/**
	 * Pattern config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the pattern name.
	 */
	public const NAME_PATTERN = '(?!^\d+$)[\p{L}\p{N}\p{Z}\-\'\\/\\(\\)\\{\\}:.#~_,+!$]{1,100}';

	/**
	 * Stores pattern config.
	 */
	private $config;

	/**
	 * Get pattern config.
	 *
	 * @return array pattern config
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
	 * Set pattern config.
	 *
	 * @param array $config pattern config
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
	 * Get pattern config.
	 *
	 * @param string $name pattern name
	 * @return array pattern config
	 */
	public function getPatternConfig(string $name): array
	{
		$pattern_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$pattern_config = $config[$name];
			$pattern_config['name'] = $name;
		}
		return $pattern_config;
	}

	/**
	 * Set single pattern config.
	 *
	 * @param string $name name
	 * @param array $pattern_config pattern configuration
	 * @return bool true if pattern saved successfully, otherwise false
	 */
	public function setPatternConfig(string $name, array $pattern_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $pattern_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single pattern config.
	 *
	 * @param string $name pattern name
	 * @return bool true if pattern removed successfully, otherwise false
	 */
	public function removePatternConfig(string $name): bool
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
	 * Check if pattern config exists.
	 *
	 * @param string $name pattern name
	 * @return bool true if pattern config exists, otherwise false
	 */
	public function patternConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}
}
