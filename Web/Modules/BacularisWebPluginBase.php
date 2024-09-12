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

/**
 * Base module class for web plugins.
 * Every web plugin should extend it.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BacularisWebPluginBase extends WebModule
{
	/**
	 * Stores single plugin configuration.
	 */
	private $config = [];

	public function __construct($config)
	{
		$this->setConfig($config);
	}

	/**
	 * Get plugin configuration.
	 *
	 * @return array plugin configuration or empty array inf configuration does not exist
	 */
	protected function getConfig(): array
	{
		return $this->config;
	}

	/**
	 * Set plugin configuration.
	 *
	 * @param array $config plugin configuration
	 */
	protected function setConfig(array $config): void
	{
		$this->config = $config;
	}
}
