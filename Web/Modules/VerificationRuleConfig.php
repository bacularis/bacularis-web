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
 * Manage verification rule configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class VerificationRuleConfig extends ConfigFileModule
{
	/**
	 * Verification rule config file path.
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.verification_rules';

	/**
	 * Verification rule config file format.
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the verification rule name.
	 */
	public const NAME_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * This is special checker operator that means
	 * comparing with file property from the Bacula Catalog.
	 */
	public const EQUAL_CATALOG_VALUE = 'ECV';

	/**
	 * Stores verification rule config.
	 */
	private $config;

	/**
	 * Get verification rule config.
	 *
	 * @return array verification rule config
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			// Read config
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);

			// Prepare config to use
			if (is_array($this->config)) {
				foreach ($this->config as $key => $value) {
					$value['name'] = $key;
					if (key_exists('rules', $value) && is_array($value['rules'])) {
						foreach ($value['rules'] as $path => $props) {
							parse_str($props, $result);
							$value['rules'][$path] = $result;
						}
					}
					$this->config[$key] = $value;
				}
			}
		}
		return $this->config;
	}

	/**
	 * Set verification rule config.
	 *
	 * @param array $config verification rule config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		// Prepare config
		foreach ($config as $key => $value) {
			if (key_exists('rules', $value) && is_array($value['rules'])) {
				foreach ($value['rules'] as $path => $props) {
					$config[$key]['rules'][$path] = http_build_query($props);
				}
			}
		}

		// Write config
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Get single verification rule config.
	 *
	 * @param string $name verification rule name
	 * @return array verification rule config
	 */
	public function getVerificationRuleConfig(string $name): array
	{
		$verification_rule_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$verification_rule_config = $config[$name];
		}
		return $verification_rule_config;
	}

	/**
	 * Set single verification rule config.
	 *
	 * @param string $name verification rule name
	 * @param array $verification_rule_config verification rule configuration
	 * @return bool true if verification rule saved successfully, otherwise false
	 */
	public function setVerificationRuleConfig(string $name, array $verification_rule_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $verification_rule_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single verification rule config.
	 *
	 * @param string $name verification rule name
	 * @return bool true if verification rule removed successfully, otherwise false
	 */
	public function removeVerificationRuleConfig(string $name): bool
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
	 * Rule if verification rule config exists.
	 *
	 * @param string $name verification rule name
	 * @return bool true if verification rule config exists, otherwise false
	 */
	public function verificationRuleConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}
}
