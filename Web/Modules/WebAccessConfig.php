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
use Bacularis\Common\Modules\Miscellaneous;

/**
 * Manage web access configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class WebAccessConfig extends ConfigFileModule
{
	/**
	 * Web access config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.web_access';

	/**
	 * Web access config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Web access types.
	 */
	public const WEB_ACCESS_TYPE_RESOURCE = 'resource';

	/**
	 * Time access method types.
	 */
	public const WEB_ACCESS_TIME_METHOD_UNLIMITED = 'unlimited';
	public const WEB_ACCESS_TIME_METHOD_FOR_DAYS = 'days';
	public const WEB_ACCESS_TIME_METHOD_DATE_RANGE = 'date_range';

	/**
	 * Usage access method types.
	 */
	public const WEB_ACCESS_USAGE_METHOD_UNLIMITED = 'unlimited';
	public const WEB_ACCESS_USAGE_METHOD_ONE_USE = 'one_use';
	public const WEB_ACCESS_USAGE_METHOD_NUMBER_USES = 'number_uses';


	/**
	 * Source access method types.
	 */
	public const WEB_ACCESS_SOURCE_METHOD_NO_RESTRICTION = 'no_restriction';
	public const WEB_ACCESS_SOURCE_METHOD_IP_RESTRICTION = 'ip_restriction';

	/**
	 * Stores web access config.
	 */
	private $config;

	/**
	 * Get web access config.
	 *
	 * @param array $filters web config result filters
	 * @return array web access config
	 */
	public function getConfig($filters = []): array
	{
		if (is_null($this->config)) {
			$config = [];
			$result = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			foreach ($result as $token => $cfg) {
				foreach ($filters as $key => $value) {
					if (key_exists($key, $cfg) && $cfg[$key] !== $value) {
						// filter does not match, skip item
						continue 2;
					}
				}
				$cfg['token'] = $token;
				$config[$token] = $cfg;
			}
			$this->config = $config;
		}
		return $this->config;
	}

	/**
	 * Set web access config.
	 *
	 * @param array $config web access config
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
	 * Get single web access config.
	 *
	 * @param string $name web access config name
	 * @return array web access config or empty array if config not found
	 */
	public function getWebAccessConfig(string $name): array
	{
		$web_access = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$web_access = $config[$name];
		}
		return $web_access;
	}

	/**
	 * Set single web access config.
	 *
	 * @param string $name web access config name
	 * @param array $settings web access config settings
	 * @return bool true on success, otherwise false
	 */
	public function setWebAccessConfig(string $name, array $settings): bool
	{
		$config = $this->getConfig();
		$config[$name] = $settings;
		$result = $this->setConfig($config);
		return $result;
	}

	/**
	 * Get single web access config.
	 *
	 * @param string $name web access config name
	 * @return bool true on success, otherwise false
	 */
	public function removeWebAccessConfig(string $name): bool
	{
		$result = false;
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			unset($config[$name]);
			$result = $this->setConfig($config);
		}
		return $result;
	}

	/**
	 * Remove web access configs.
	 *
	 * @param array $names web access config names
	 * @return bool true on success, otherwise false
	 */
	public function removeWebAccessConfigs(array $names): bool
	{
		$result = false;
		$config = $this->getConfig();
		$mod = false;
		for ($i = 0; $i < count($names); $i++) {
			if (key_exists($names[$i], $config)) {
				unset($config[$names[$i]]);
				$mod = true;
			}
		}
		if ($mod) {
			$result = $this->setConfig($config);
		}
		return $result;
	}

	/**
	 * Check if given web config access exists.
	 *
	 * @param string $name web access config name
	 * @return bool true on success, otherwise false
	 */
	public function webAccessConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}

	/**
	 * Get web config token.
	 *
	 * @return string random token value
	 */
	public function generateWebConfigToken(): string
	{
		$rb_len = 42;
		$rb_bin = random_bytes($rb_len);
		$token = Miscellaneous::encodeBase64URL($rb_bin);
		return $token;
	}
}
