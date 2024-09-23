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

use Bacularis\Common\Modules\PluginConfigBase;

/**
 * Web plugin configuration module.
 * It manages all plugin config settings.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class PluginConfig extends PluginConfigBase
{
	/**
	 * Plugin config file path
	 */
	private const CONFIG_FILE_PATH = 'Bacularis.Web.Config.plugins';

	/**
	 * Plugin script directory path
	 */
	private const PLUGIN_DIR_PATH = 'Bacularis.Web.Plugins';

	/**
	 * Plugin config file format.
	 */
	private const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Get configuration file path in dot notation.
	 *
	 * @return string config file path
	 */
	protected function getConfigFilePath(): string
	{
		return self::CONFIG_FILE_PATH;
	}

	/**
	 * Get configuration file format.
	 *
	 * @return string config file format
	 */
	protected function getConfigFileFormat(): string
	{
		return self::CONFIG_FILE_FORMAT;
	}

	/**
	 * Get directory to store plugins.
	 *
	 * @return string plugin directory path
	 */
	protected function getPluginDirPath(): string
	{
		return self::PLUGIN_DIR_PATH;
	}
}
