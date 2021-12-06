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
Prado::using('Application.Web.Portlets.Portlets');

/**
 * Job graphs control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class JobGraphs extends Portlets {

	const JOB = 'Job';

	public function onInit($param) {
		parent::onInit($param);
		if ($this->getPage()->IsPostBack || $this->getPage()->IsCallBack) {
			return;
		}
		$this->setClients();
	}

	public function setClients() {
		$result = $this->getModule('api')->get(array('clients'));
		$clients = array('@' => Prado::localize('select client'));
		foreach($result->output as $key => $client) {
			$clients[$client->clientid] = $client->name;
		}
		$this->Clients->dataSource = $clients;
		$this->Clients->dataBind();
	}

	/**
	 * Set client to show graphs.
	 *
	 * @return none
	 */
	public function setClient($client) {
		$this->setViewState(self::CLIENT, $client);
	}

	/**
	 * Get client to show graph.
	 *
	 * @return none
	 */
	public function getClient() {
		return $this->getViewState(self::CLIENT);
	}

	/**
	 * Set job to show graphs.
	 *
	 * @return none
	 */
	public function setJob($job) {
		$this->setViewState(self::JOB, $job);
	}

	/**
	 * Get job to show graph.
	 *
	 * @return none
	 */
	public function getJob() {
		return $this->getViewState(self::JOB);
	}
}
?>
