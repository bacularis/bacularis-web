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
use Prado\Prado;

/**
 * Plugin configuration module.
 * It manages all plugin config settings.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class PluginConfig extends ConfigFileModule
{
	/**
	 * Settings name pattern.
	 */
	public const SETTINGS_NAME_PATTERN = '(?!^\d+$)[\p{L}\p{N}\p{Z}\-\'\\/\\(\\)\\{\\}:.#~_,+!$]{1,100}';

	/**
	 * Plugin config file path
	 */
	private const CONFIG_FILE_PATH = 'Bacularis.Web.Config.plugins';

	/**
	 * Plugin script directory path
	 */
	private const PLUGIN_DIR_PATH = 'Bacularis.Web.Plugins';

	/**
	 * Plugin script file pattern.
	 */
	private const PLUGIN_FILE_PATTERN = '*.php';

	/**
	 * Plugin config file format.
	 */
	private const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Plugin types.
	 */
	public const PLUGIN_TYPE_NOTIFICATION = 'notification';

	/**
	 * Stores plugin config content.
	 */
	private $config;

	/**
	 * Stores plugin list.
	 */
	private $plugins;

	public function init($config)
	{
		if (is_null($this->plugins)) {
			$this->plugins = $this->getPlugins();
		}
	}

	/**
	 * Get plugins config.
	 *
	 * @param string $section specific config settings section
	 * @return array plugins config
	 */
	public function getConfig($section = null): array
	{
		$config = [];
		if (is_null($this->config)) {
			$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			$this->prepareConfigUse($config);
			$this->config = $config;
		}
		if (is_string($section)) {
			$config = key_exists($section, $this->config) ? $this->config[$section] : [];
		} else {
			$config = $this->config;
		}
		return $config;
	}

	/**
	 * Set plugins config.
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		$this->prepareConfigSave($config);
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Prepare settings config to use.
	 *
	 * @param int $config config reference
	 */
	private function prepareConfigUse(array &$config): void
	{
		foreach ($config as $name => &$settings) {
			$settings['name'] = $name;
			if (!key_exists($settings['plugin'], $this->plugins)) {
				// settings exist but plugin is not installed
				continue;
			}
			for ($i = 0; $i < count($this->plugins[$settings['plugin']]['parameters']); $i++) {
				foreach ($settings['parameters'] as $sparam => &$svalue) {
					$pprops = $this->plugins[$settings['plugin']]['parameters'][$i];
					if ($pprops['name'] != $sparam) {
						continue;
					}
					// Prepare data for specific field types
					if ($pprops['type'] == 'array_multiple') {
						$svalue = !empty($svalue) ? explode(',', $svalue) : [];
					} elseif ($pprops['type'] == 'string_long') {
						$svalue = str_replace(['\\n', '\\r'], ["\n", "\r"], $svalue);
					}
				}
			}
		}
	}

	/**
	 * Prepare settings config to save.
	 *
	 * @param int $config config reference
	 */
	private function prepareConfigSave(array &$config): void
	{
		foreach ($config as $name => &$settings) {
			if (!key_exists($settings['plugin'], $this->plugins)) {
				// settings exist but plugin is not installed
				continue;
			}
			for ($i = 0; $i < count($this->plugins[$settings['plugin']]['parameters']); $i++) {
				foreach ($settings['parameters'] as $sparam => &$svalue) {
					$pprops = $this->plugins[$settings['plugin']]['parameters'][$i];
					if ($pprops['name'] != $sparam) {
						continue;
					}
					// Prepare data for specific field types
					if ($pprops['type'] == 'array_multiple') {
						$svalue = implode(',', $svalue);
					} elseif ($pprops['type'] == 'string_long') {
						$svalue = str_replace(["\n", "\r"], ['\\n', '\\r'], $svalue);
					}
				}
			}
		}
	}

	/**
	 * Check if plugin settings exists.
	 *
	 * @param string $name settings name
	 * @return bool true if setting exists, otherwise false
	 */
	public function isPluginSettings(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}

	/**
	 * Set single web plugin settings.
	 *
	 * @param string $name settings name
	 * @param array $settings plugin settings
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setPluginSettings(string $name, array $settings): bool
	{
		$config = $this->getConfig();
		$config[$name] = $settings;
		$result = $this->setConfig($config);
		return $result;
	}

	/**
	 * Remove single web plugin settings.
	 *
	 * @param string $name settings name
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function removePluginSettings($name)
	{
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			unset($config[$name]);
		}
		$result = $this->setConfig($config);
		return $result;
	}

	/**
	 * Get installed plugin list with properties.
	 *
	 * @param string $ftype plugin type
	 * @return array plugin list
	 */
	public function getPlugins(?string $ftype = null): array
	{
		$plugins = [];
		$path = Prado::getPathOfNamespace(self::PLUGIN_DIR_PATH);
		$pattern = $path . DIRECTORY_SEPARATOR . self::PLUGIN_FILE_PATTERN;
		$iterator = new \GlobIterator($pattern);
		while ($iterator->valid()) {
			$plugin = $iterator->current()->getFilename();
			$ppath = $iterator->current()->getPathname();
			require_once($ppath);
			$cls = rtrim($plugin, '.php');
			$name = $cls::getName();
			$version = $cls::getVersion();
			$type = $cls::getType();
			$parameters = $cls::getParameters();
			if (is_string($ftype) && $type !== $ftype) {
				// filter by type
				continue;
			}
			$plugins[$cls] = [
				'cls' => $cls,
				'path' => $ppath,
				'name' => $name,
				'version' => $version,
				'type' => $type,
				'parameters' => $parameters
			];
			$iterator->next();
		}
		return $plugins;
	}
}
