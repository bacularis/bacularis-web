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

use Bacularis\Common\Modules\PluginManagerBase;

/**
 * Web plugin module manager.
 * It manages all Web plugins.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebPluginManager extends PluginManagerBase
{
	/**
	 * Module initialization.
	 *
	 * @param mixed $params onInit action params
	 */
	public function init($params)
	{
		parent::init($params);
		$this->initPlugins();
	}
}
