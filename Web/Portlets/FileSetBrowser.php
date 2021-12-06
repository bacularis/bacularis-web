<?php
/*
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2019 Kern Sibbald
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

Prado::using('System.Web.UI.ActiveControls.TCallback');
Prado::using('Application.Web.Portlets.Portlets');

/**
 * FileSet browser control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class FileSetBrowser extends Portlets {

	const BCLIENT_ID = 'BClientId';
	const PATH = 'Path';

	/**
	 * Load client list.
	 *
	 * @return none
	 */
	public function loadClients($sender, $param) {
		$client_list = array();
		$clients = $this->getModule('api')->get(array('clients'))->output;
		for ($i = 0; $i < count($clients); $i++) {
			$client_list[$clients[$i]->clientid] = $clients[$i]->name;
		}
		arsort($client_list);
		$client_list['none'] = Prado::localize('Please select Client');
		uksort($client_list, 'sort_client_list');
		$this->Client->DataSource = $client_list;
		$this->Client->dataBind();
	}

	/**
	 * Set selected client.
	 *
	 * @param TActiveDropDownList $sender, sender object
	 * @param TCommandParameter $param parameters object
	 * @return none
	 */
	public function selectClient($sender, $param) {
		$client_id = $this->Client->getSelectedValue();
		if ($client_id !== 'none') {
			$this->setBClientId($client_id);
			$this->goToPath();
		}
	}

	public function getItems($sender, $param) {
		if ($param instanceof Prado\Web\UI\ActiveControls\TCallbackEventParameter) {
			$path = $param->getCallbackParameter();
			$this->setPath($path);
			$this->goToPath();
		}
	}

	public function goToPath() {
		$client_id = $this->getBClientId();
		$query = '?path=' . rawurlencode($this->getPath());
		$params = array(
			'clients',
			$client_id,
			'ls',
			$query
		);
		$result = $this->getModule('api')->get($params);
		$this->getPage()->getCallbackClient()->callClientFunction(
			'FileSetBrowser_set_content' . $this->ClientID,
			json_encode($result->output)
		);
	}

	public function setBClientId($bclient_id) {
		$this->setViewState(self::BCLIENT_ID, $bclient_id);
	}

	public function getBClientId() {
		return $this->getViewState(self::BCLIENT_ID);
	}

	public function setPath($path) {
		$this->setViewState(self::PATH, $path);
	}

	public function getPath() {
		return $this->getViewState(self::PATH, '/');
	}
}

function sort_client_list($a, $b) {
	if ($a === 'none') {
		return -1;
	} else {
		return 1;
	}
}
?>
