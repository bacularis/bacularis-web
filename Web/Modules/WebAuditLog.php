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

use Prado\Prado;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\PluginConfigBase;

class WebAuditLog extends AuditLog
{
	/**
	 * Audit log file path
	 */
	protected const LOG_FILE_PATH = 'Bacularis.Web.Logs.bacularis-audit';

	/**
	 * Audit log file extension.
	 */
	protected const LOG_FILE_EXT = '.log';

	public function getConfigFile()
	{
		return Prado::getPathOfNamespace(
			self::LOG_FILE_PATH,
			self::LOG_FILE_EXT
		);
	}

	/**
	 * Process audit log.
	 *
	 * @param string $type message type (INFO, WARNING, ERROR)
	 * @param string $category message category (Config, Action, Application, Security)
	 * @param string $action message body
	 */
	public function audit($type, $category, $action)
	{
		// Write audit log
		parent::audit($type, $category, $action);

		// Run notification plugins
		$web_plugin = $this->getModule('web_plugin');
		$web_plugin->callPluginAction(
			PluginConfigBase::PLUGIN_TYPE_NOTIFICATION,
			'execute',
			$type,
			$category,
			$action
		);
	}
}
