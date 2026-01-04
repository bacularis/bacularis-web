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
 * Manage variables configuration.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class VariableConfig extends ConfigFileModule
{
	/**
	 * Special character associated with variables.
	 * Each variable uses this character at the beginning of the name (ex: $myvar1).
	 * Use this character to invoke variable menu.
	 */
	public const SPECIAL_CHAR = '$';

	/**
	 * Variable config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.variables';

	/**
	 * Variable config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the variable name.
	 */
	public const NAME_PATTERN = '(?!^\d+$)\w{1,160}';

	/**
	 * Stores variable config.
	 */
	private $config;

	/**
	 * Get variable config.
	 *
	 * @return array variable config
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
	 * Set variable config.
	 *
	 * @param array $config variable config
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
	 * Get variable config.
	 *
	 * @param string $name variable name
	 * @return array variable config
	 */
	public function getVariableConfig(string $name): array
	{
		$variable_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$variable_config = $config[$name];
			$variable_config['name'] = $name;
		}
		return $variable_config;
	}

	/**
	 * Set single variable config.
	 *
	 * @param string $name name
	 * @param array $variable_config variable configuration
	 * @return bool true if variable saved successfully, otherwise false
	 */
	public function setVariableConfig(string $name, array $variable_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $variable_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single variable config.
	 *
	 * @param string $name variable name
	 * @return bool true if variable removed successfully, otherwise false
	 */
	public function removeVariableConfig(string $name): bool
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
	 * Check if variable config exists.
	 *
	 * @param string $name variable name
	 * @return bool true if variable config exists, otherwise false
	 */
	public function variableConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}

	/**
	 * Get variable name in the full notation with special character.
	 *
	 * @param string $var variable name
	 * @return string full variable name
	 */
	public static function getVariableName(string $var): string
	{
		return sprintf('%s{%s}', self::SPECIAL_CHAR, $var);
	}

	/**
	 * Find variables in configuration.
	 *
	 * @param array $config Bacula configuration
	 * @return array found variables
	 */
	public function findVariables(array $config): array
	{
		$variables = [];
		foreach ($config as $key => $value) {
			if (is_string($value) && strpos($value, self::SPECIAL_CHAR) !== false) {
				$vars = $this->getVariables($value);
				$variables = array_merge($variables, $vars);
			} elseif (is_array($value)) {
				$vars = $this->findVariables($value);
				$variables = array_merge($variables, $vars);
			}
		}
		return $variables;
	}

	/**
	 * Get variables from the directive value.
	 *
	 * @param string $value directive value
	 * @return array variable list or empty array if no variable found
	 */
	public function getVariables(string $value): array
	{
		$vars = [];
		$variables = $this->getConfig();
		foreach ($variables as $variable => $config) {
			$var = self::getVariableName($variable);
			if (strpos($value, $var) !== false) {
				$vars[$variable] = $config;
			}
		}
		return $vars;
	}

	/**
	 * Set variables in directive value.
	 *
	 * @param string $value directive value
	 * @param array $variables variables to set
	 * @return string directive value with solved variable names into values
	 */
	public function setVariables(string $value, array $variables): string
	{
		$vars = array_keys($variables);
		$vars = array_map(fn ($item) => self::getVariableName($item), $vars);
		$vals = array_values($variables);
		$value = str_replace($vars, $vals, $value);
		return $value;
	}

	/**
	 * Add variables to Bacula configration.
	 *
	 * @param array $config Bacula configuration
	 * @param array $variables variables to set
	 * @return array Bacula configuration with solved variable names into values
	 */
	public function addVariables(array $config, array $variables): array
	{
		foreach ($config as $directive_name => $directive_value) {
			if (is_string($directive_value)) {
				$config[$directive_name] = $this->setVariables($directive_value, $variables);
			} elseif (is_array($directive_value)) {
				$config[$directive_name] = $this->addVariables($directive_value, $variables);
			}
		}
		return $config;
	}
}
