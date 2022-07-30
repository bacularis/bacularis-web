<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2022 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 */

namespace Bacularis\Web\Modules;

use Prado\Prado;
use Bacularis\Common\Modules\AuditLog;

class WebAuditLog extends AuditLog {

	/**
	 * Audit log file path
	 */
	protected const LOG_FILE_PATH = 'Bacularis.Web.Logs.bacularis-audit';

	/**
	 * Audit log file extension.
	 */
	protected const LOG_FILE_EXT = '.log';

	public function getConfigFile() {
		return Prado::getPathOfNamespace(
			self::LOG_FILE_PATH,
			self::LOG_FILE_EXT
		);
	}
}
