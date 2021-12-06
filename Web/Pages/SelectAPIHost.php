<?php
/*
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

Prado::using('Application.Web.Class.BaculumWebPage');

/**
 * Select API host page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class SelectAPIHost extends BaculumWebPage {

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$api_hosts = $this->User->getAPIHosts();
		array_unshift($api_hosts, '');
		$this->UserAPIHosts->DataSource = array_combine($api_hosts, $api_hosts);
		$this->UserAPIHosts->dataBind();
	}

	public function setAPIHost($sender, $param) {
		$api_host = $this->UserAPIHosts->SelectedValue;
		if (!empty($api_host)) {
			$this->User->setDefaultAPIHost($api_host);
			$this->getPage()->resetSessionUserVars();
			$this->goToDefaultPage();
		}
	}
}
?>
