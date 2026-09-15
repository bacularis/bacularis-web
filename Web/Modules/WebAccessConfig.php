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
	 * Web access config lock file extension.
	 */
	private const CONFIG_LOCK_FILE_EXT = '.lock';

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

	private const WEB_ACCESS_OPTIONS = [
		'access_type' => 'req',
		'api_hosts' => 'req',
		'component_type' => 'req',
		'component_name' => 'req',
		'resource_type' => 'req',
		'resource_name' => 'req',
		'action' => 'req',
		'action_params' => 'req'
	];

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
		$result = false;
		$lock = $this->acquireConfigLock();
		if (is_resource($lock)) {
			try {
				$result = $this->setConfigUnlocked($config);
			} finally {
				$this->releaseConfigLock($lock);
			}
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
		$result = false;
		if ($this->validateOptions($settings)) {
			$modifier = function (array $config) use ($name, $settings): array {
				$config[$name] = $settings;
				return $config;
			};
			$result = $this->modifyConfig($modifier);
		}
		return $result;
	}

	/**
	 * Update single web access config.
	 *
	 * @param string $name web access config name
	 * @param array $settings selected web access config settings
	 * @return bool true on success, otherwise false
	 */
	public function updateWebAccessConfig(string $name, array $settings): bool
	{
		$modifier = function (array $config) use ($name, $settings) {
			if (!key_exists($name, $config)) {
				return null;
			}
			foreach ($settings as $key => $val) {
				if (is_array($val)) {
					foreach ($val as $k => $v) {
						$config[$name][$key][$k] = $v;
					}
				} else {
					$config[$name][$key] = $val;
				}
			}
			return $config;
		};
		return $this->modifyConfig($modifier);
	}

	/**
	 * Get single web access config.
	 *
	 * @param string $name web access config name
	 * @return bool true on success, otherwise false
	 */
	public function removeWebAccessConfig(string $name): bool
	{
		$modifier = function (array $config) use ($name) {
			if (!key_exists($name, $config)) {
				return null;
			}
			unset($config[$name]);
			return $config;
		};
		return $this->modifyConfig($modifier);
	}

	/**
	 * Remove web access configs.
	 *
	 * @param array $names web access config names
	 * @return bool true on success, otherwise false
	 */
	public function removeWebAccessConfigs(array $names): bool
	{
		$modifier = function (array $config) use ($names) {
			$modified = false;
			for ($i = 0; $i < count($names); $i++) {
				if (key_exists($names[$i], $config)) {
					unset($config[$names[$i]]);
					$modified = true;
				}
			}
			return $modified ? $config : null;
		};
		return $this->modifyConfig($modifier);
	}

	/**
	 * Atomically validate and reserve one WebAccess use.
	 * The reserved use is consumed regardless of the backend action result.
	 *
	 * @param string $name web access config name
	 * @param callable $validator web access config validation callback
	 * @return array reservation data with keys: reserved, config, validation
	 */
	public function reserveWebAccessUse(string $name, callable $validator): array
	{
		$reservation = [
			'reserved' => false,
			'config' => [],
			'validation' => null
		];
		$lock = $this->acquireConfigLock();
		if (!is_resource($lock)) {
			return $reservation;
		}

		try {
			$this->config = null;
			$config = $this->getConfig();
			$settings = key_exists($name, $config) ? $config[$name] : [];
			$reservation['config'] = $settings;
			$reservation['validation'] = $validator($settings);
			if (
				!is_array($reservation['validation']) ||
				!key_exists('error', $reservation['validation']) ||
				$reservation['validation']['error'] !== 0
			) {
				return $reservation;
			}
			if ($settings['usage_method'] === self::WEB_ACCESS_USAGE_METHOD_UNLIMITED) {
				$reservation['reserved'] = true;
				return $reservation;
			}

			$settings['access_time'] = time();
			if (
				$settings['usage_method'] === self::WEB_ACCESS_USAGE_METHOD_ONE_USE ||
				$settings['usage_method'] === self::WEB_ACCESS_USAGE_METHOD_NUMBER_USES
			) {
				$usage_left = (int) $settings['usage_left'];
				$settings['usage_left'] = --$usage_left;
			}
			$config[$name] = $settings;
			$reservation['config'] = $settings;
			$reservation['reserved'] = $this->setConfigUnlocked($config);
		} finally {
			$this->releaseConfigLock($lock);
		}
		return $reservation;
	}

	/**
	 * Modify the current WebAccess configuration under an exclusive lock.
	 * The modifier returns the complete changed configuration or null when
	 * no configuration should be written.
	 *
	 * @param callable $modifier configuration modifier callback
	 * @return bool true if changed configuration was saved, otherwise false
	 */
	private function modifyConfig(callable $modifier): bool
	{
		$result = false;
		$lock = $this->acquireConfigLock();
		if (!is_resource($lock)) {
			return $result;
		}

		try {
			$this->config = null;
			$config = $this->getConfig();
			$config = $modifier($config);
			if (is_array($config)) {
				$result = $this->setConfigUnlocked($config);
			}
		} finally {
			$this->releaseConfigLock($lock);
		}
		return $result;
	}

	/**
	 * Save the complete WebAccess configuration without acquiring a lock.
	 * The caller must hold the WebAccess configuration lock.
	 *
	 * @param array $config web access config
	 * @return bool true if config saved successfully, otherwise false
	 */
	private function setConfigUnlocked(array $config): bool
	{
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Acquire the exclusive WebAccess configuration lock.
	 *
	 * @return false|resource lock file handle on success, otherwise false
	 */
	private function acquireConfigLock()
	{
		$config_path = $this->getConfigRealPath(self::CONFIG_FILE_PATH);
		$lock_path = $config_path . self::CONFIG_LOCK_FILE_EXT;
		$lock = fopen($lock_path, 'c');
		if (!is_resource($lock)) {
			return false;
		}
		if (!flock($lock, LOCK_EX)) {
			fclose($lock);
			return false;
		}
		return $lock;
	}

	/**
	 * Release the WebAccess configuration lock.
	 *
	 * @param resource $lock lock file handle
	 */
	private function releaseConfigLock($lock): void
	{
		flock($lock, LOCK_UN);
		fclose($lock);
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

	/**
	 * Validate web access configuration options.
	 *
	 * @param array $config web access configuration
	 * @return bool true on success, otherwise false
	 */
	public static function validateOptions(array $config)
	{
		$valid = true;
		foreach (self::WEB_ACCESS_OPTIONS as $option => $type) {
			if ($type != 'req') {
				continue;
			}
			if (!key_exists($option, $config)) {
				$valid = false;
				break;
			}
		}
		return $valid;
	}
}
