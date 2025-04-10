<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2021 Kern Sibbald
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

use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Console view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class ConsoleView extends BaculumWebPage
{
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$sess = $this->getApplication()->getSession();
		$component_name = $sess->itemAt('dir');
		$host = $this->User->getDefaultAPIHost();
		$this->ConsoleResourcesConfig->setHost($host);
		$this->ConsoleResourcesConfig->setComponentName($component_name);
	}

	public function loadConsoleResourcesConfig($sender, $param)
	{
		$resource_type = $param->getCallbackParameter();
		if (!empty($resource_type)) {
			$this->ConsoleResourcesConfig->setResourceType($resource_type);
			$this->ConsoleResourcesConfig->loadResourceListTable($sender, $param);
		} else {
			$this->ConsoleResourcesConfig->showError(true);
		}
	}
}
