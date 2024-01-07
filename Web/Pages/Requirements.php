<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

namespace Bacularis\Web\Pages;

use Bacularis\Common\Modules\GeneralRequirements;

/**
 * Web part requirements class.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class Requirements extends GeneralRequirements
{
	/**
	 * Required PHP extensions.
	 *
	 * Note, requirements page is visible before any language is set and before
	 * translation engine initialization. From this reason all messages are not
	 * translated.
	 */
	private $req_exts = [
		[
			'ext' => 'curl',
			'help_msg' => 'Please install <b>PHP cURL module</b>.'
		],
		[
			'ext' => 'ldap',
			'help_msg' => 'Please install <b>PHP LDAP module</b>.'
		]
	];

	public function __construct($app_dir, $prot_dir, $base_dir)
	{
		parent::__construct($app_dir, $prot_dir, $base_dir);
		$this->validateEnvironment();
		parent::showResult('Bacularis Web');
	}

	/**
	 * Validate all Web environment depenencies.
	 *
	 */
	public function validateEnvironment()
	{
		parent::validateExtensions($this->req_exts);
	}
}
