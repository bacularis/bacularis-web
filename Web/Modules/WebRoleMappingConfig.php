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
 * Role mapping module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebRoleMappingConfig extends ConfigFileModule
{
	/**
	 * Role mapping config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.role_mapping';

	/**
	 * Role mapping config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for mapping identifier.
	 */
	public const MAPPING_ID_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	/**
	 * Encode selected options.
	 */
	private const ENCODED_OPTIONS = ['roles'];

	/**
	 * Stores role mapping config.
	 */
	private $config;

	/**
	 * Get role mapping config.
	 *
	 * @return array role mapping config
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			$this->config = $this->readConfig(
				self::CONFIG_FILE_PATH,
				self::CONFIG_FILE_FORMAT
			);
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
	 * Set role mapping config.
	 *
	 * @param array $config role mapping config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		$result = $this->writeConfig(
			$config,
			self::CONFIG_FILE_PATH,
			self::CONFIG_FILE_FORMAT
		);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	public function getMappingConfig(?string $mapping_id)
	{
		$mapping_config = [];
		$config = $this->getConfig();
		if (key_exists($mapping_id, $config)) {
			$cfg = $config[$mapping_id];
			foreach ($cfg as $option => $props) {
				if (!in_array($option, self::ENCODED_OPTIONS)) {
					continue;
				}
				foreach ($props as $name => $value) {
					parse_str($value, $result);
					$cfg[$option][$name] = $result;
				}
			}
			$mapping_config = $cfg;
		}
		return $mapping_config;
	}

	/**
	 * Set role mapping config.
	 *
	 * @param string $mapping_id role mapping identifier
	 * @param array $mapping_config user role mapping configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setMappingConfig(string $mapping_id, array $mapping_config): bool
	{
		$config = $this->getConfig();
		foreach ($mapping_config as $option => $props) {
			if (in_array($option, self::ENCODED_OPTIONS)) {
				foreach ($props as $name => $value) {
					$vw = http_build_query($value);
					$mapping_config[$option][$name] = $vw;
				}
			}
		}
		$config[$mapping_id] = $mapping_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single role mapping config.
	 *
	 * @param string $mapping_id role mapping identifier
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeMappingConfig(string $mapping_id): bool
	{
		$ret = false;
		$config = $this->getConfig();
		if (isset($config[$mapping_id])) {
			unset($config[$mapping_id]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}
}
