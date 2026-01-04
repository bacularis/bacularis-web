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
 * Manage constants configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class ConstantConfig extends ConfigFileModule
{
	/**
	 * Special character associated with constants.
	 * Each constant uses this character at the beginning of the name (ex: %myconst1).
	 * Use this character to invoke constant menu.
	 */
	public const SPECIAL_CHAR = '%';

	/**
	 * Constant config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.constants';

	/**
	 * Constant config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the constant name.
	 */
	public const NAME_PATTERN = '(?!^\d+$)\w{1,160}';

	/**
	 * Stores constant config.
	 */
	private $config;

	/**
	 * Get constant config.
	 *
	 * @return array constant config
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
	 * Set constant config.
	 *
	 * @param array $config constant config
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
	 * Get constant config.
	 *
	 * @param string $name constant name
	 * @return array constant config
	 */
	public function getConstantConfig(string $name): array
	{
		$constant_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$constant_config = $config[$name];
			$constant_config['name'] = $name;
		}
		return $constant_config;
	}

	/**
	 * Set single constant config.
	 *
	 * @param string $name name
	 * @param array $constant_config constant configuration
	 * @return bool true if constant saved successfully, otherwise false
	 */
	public function setConstantConfig(string $name, array $constant_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $constant_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single constant config.
	 *
	 * @param string $name constant name
	 * @return bool true if constant removed successfully, otherwise false
	 */
	public function removeConstantConfig(string $name): bool
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
	 * Check if constant config exists.
	 *
	 * @param string $name constant name
	 * @return bool true if constant config exists, otherwise false
	 */
	public function constantConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}

	/**
	 * Get constant name in the full notation with special character.
	 *
	 * @param string $var constant name
	 * @return string full constant name
	 */
	public static function getConstantName(string $var): string
	{
		return sprintf('%s{%s}', self::SPECIAL_CHAR, $var);
	}
}
