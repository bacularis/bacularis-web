<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
 * Manage configs configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class ConfigConfig extends ConfigFileModule
{
	/**
	 * Config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.config';

	/**
	 * Config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the config name.
	 */
	public const NAME_PATTERN = '(?!^\d+$)[\p{L}\p{N}\p{Z}\-\'\\\/\\(\\)\\{\\}\\\\%:.#~_,+!$]{1,160}';

	/**
	 * Stores config.
	 */
	private $config;

	/**
	 * Get config.
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
					if (key_exists('config', $value)) {
						$value['config'] = json_decode($value['config'], true);
					}
					$this->config[$key] = $value;
				}
			}
		}
		return $this->config;
	}

	/**
	 * Set config.
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		if (is_array($config)) {
			foreach ($config as $key => $value) {
				if (key_exists('config', $value)) {
					$value['config'] = self::escapeCharacters($value['config']);
					$value['config'] = json_encode($value['config'], JSON_HEX_QUOT);
				}
				$config[$key] = $value;
			}
		}
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Get config.
	 *
	 * @param string $name config name
	 * @param bool $array_config set true if config should be array
	 * @return array config
	 */
	public function getConfConfig(string $name): array
	{
		$conf_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$conf_config = $config[$name];
		}
		return $conf_config;
	}

	/**
	 * Set single config.
	 *
	 * @param string $name name
	 * @param array $conf_config configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfConfig(string $name, array $conf_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $conf_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single config.
	 *
	 * @param string $name config name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeConfConfig(string $name): bool
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
	 * Check if config exists.
	 *
	 * @param string $name config name
	 * @return bool true if config exists, otherwise false
	 */
	public function confConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}

	/**
	 * Escape special characters in config directives.
	 * This is for characters that escaping is not fully supported in json_encode().
	 * It fixes these lacks in json_encode().
	 * NOTE: Recursion calls used.
	 *
	 * @param array $config config content
	 * @return array config content with escaped characters ready to save
	 */
	private static function escapeCharacters(array $config)
	{
		$from = ['\\', '\\|'];
		$to = ['\\\\', '\\\\u007C'];
		foreach ($config as $key => $value) {
			if (is_array($value)) {
				$config[$key] = self::escapeCharacters($value);
			} elseif (is_string($value)) {
				$config[$key] = str_replace($from, $to, $value);
			}
		}
		return $config;
	}
}
