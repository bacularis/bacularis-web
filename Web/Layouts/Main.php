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

namespace Bacularis\Web\Layouts;

use Prado\Web\UI\TTemplateControl;

/**
 * Main layout class.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Layout
 */
class Main extends TTemplateControl
{
	public $web_config;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getPage()->IsPostBack || $this->getPage()->IsCallBack) {
			return;
		}
		$api_hosts = $this->User->getAPIHosts();
		$this->UserAPIHosts->DataSource = array_combine($api_hosts, $api_hosts);
		$this->UserAPIHosts->SelectedValue = $this->User->getDefaultAPIHost();
		$this->UserAPIHosts->dataBind();
		if (count($api_hosts) === 1) {
			$this->UserAPIHostsContainter->Visible = false;
		}
	}


	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->web_config = $this->Application->getModule('web_config')->getConfig();
	}

	public function setAPIHost($sender, $param)
	{
		$api_host = $this->UserAPIHosts->SelectedValue;
		if (!empty($api_host)) {
			$this->User->setDefaultAPIHost($api_host);
			$this->getPage()->resetSessionUserVars();
			$this->getResponse()->reload();
		}
	}
}
