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

use Prado\Prado;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Bacularis\Common\Modules\Params;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Client view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class ClientView extends BaculumWebPage
{
	public const CLIENTID = 'ClientId';
	public const CLIENT_NAME = 'ClientName';
	public const CLIENT_ADDRESS = 'ClientAddress';

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$clientid = 0;
		if ($this->Request->contains('clientid')) {
			$clientid = $this->Request['clientid'];
		} elseif ($this->Request->contains('client')) {
			$result = $this->getModule('api')->get(['clients']);
			if ($result->error === 0) {
				for ($i = 0; $i < count($result->output); $i++) {
					if ($this->Request['client'] === $result->output[$i]->name) {
						$clientid = $result->output[$i]->clientid;
						break;
					}
				}
			}
		}
		$this->setClientId($clientid);
		$clientshow = $this->getModule('api')->get(
			['clients', $clientid, 'show', '?output=json'],
			null,
			true
		);
		if ($clientshow->error === 0) {
			if (property_exists($clientshow->output, 'enabled')) {
				$this->OEnabled->Text = $clientshow->output->enabled == 1 ? Prado::localize('Yes') : Prado::localize('No');
			}
			if (property_exists($clientshow->output, 'address')) {
				$this->setClientAddress($clientshow->output->address);
				$this->OFDAddress->Text = $clientshow->output->address;
			}
			if (property_exists($clientshow->output, 'fdport')) {
				$this->OFDPort->Text = $clientshow->output->fdport;
			}
			if (property_exists($clientshow->output, 'maxjobs') && property_exists($clientshow->output, 'numjobs')) {
				$this->ORunningJobs->Text = $clientshow->output->numjobs . '/' . $clientshow->output->maxjobs;
			}
			if (property_exists($clientshow->output, 'autoprune')) {
				$this->OAutoPrune->Text = $clientshow->output->autoprune = 1 ? Prado::localize('Yes') : Prado::localize('No');
			}
			if (property_exists($clientshow->output, 'jobretention')) {
				$this->OJobRetention->Text = $clientshow->output->jobretention;
			}
			if (property_exists($clientshow->output, 'fileretention')) {
				$this->OFileRetention->Text = $clientshow->output->fileretention;
			}
		}
		if (property_exists($clientshow->output, 'name')) {
			// set name from client show
			$this->setClientName($clientshow->output->name);
		} else {
			// set name from catalog data request
			$client = $this->getModule('api')->get(
				['clients', $clientid]
			);
			if ($client->error === 0) {
				$this->setClientName($client->output->name);
			}
		}
		$this->setAPIHosts();

		// Set component actions
		$fd_api_host = $this->getFDAPIHost();
		if ($fd_api_host) {
			$this->CompActions->setHost($fd_api_host);
			$this->CompActions->setComponentType('fd');
			$this->BulkApplyPatternsClient->setHost($fd_api_host);
		}
	}

	public function setDIRClientConfig($sender, $param)
	{
		$this->FDFileDaemonConfig->unloadDirectives();
		if (!empty($_SESSION['dir'])) {
			$this->DIRClientConfig->setComponentName($_SESSION['dir']);
			$this->DIRClientConfig->setResourceName($this->getClientName());
			$this->DIRClientConfig->setLoadValues(true);
			$this->DIRClientConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	private function setAPIHosts()
	{
		$def_host = null;
		$api_hosts = $this->getModule('host_config')->getConfig();
		$user_api_hosts = $this->User->getAPIHosts();
		$client_address = $this->getClientAddress();
		foreach ($api_hosts as $name => $attrs) {
			if (in_array($name, $user_api_hosts) && $attrs['address'] === $client_address) {
				$def_host = $name;
				break;
			}
		}
		$this->UserAPIHosts->DataSource = array_combine($user_api_hosts, $user_api_hosts);
		if ($def_host) {
			$this->UserAPIHosts->SelectedValue = $def_host;
		} else {
			$this->UserAPIHosts->SelectedValue = $this->User->getDefaultAPIHost();
		}
		$this->UserAPIHosts->dataBind();
		if (count($user_api_hosts) === 1) {
			$this->UserAPIHostsContainter->Visible = false;
		}
	}

	private function getFDAPIHost()
	{
		if (!$this->User->isUserAPIHost($this->UserAPIHosts->SelectedValue)) {
			// Validation error. Somebody manually modified select values
			return false;
		}
		return $this->UserAPIHosts->SelectedValue;
	}


	private function getFDName()
	{
		$fdname = null;
		if (!$this->User->isUserAPIHost($this->UserAPIHosts->SelectedValue)) {
			// Validation error. Somebody manually modified select values
			return $fdname;
		}
		$result = $this->getModule('api')->get(['config'], $this->UserAPIHosts->SelectedValue);
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if ($result->output[$i]->component_type === 'fd' && $result->output[$i]->state) {
					$fdname = $result->output[$i]->component_name;
				}
			}
		}
		return $fdname;
	}

	public function loadFDFileDaemonConfig($sender, $param)
	{
		$this->DIRClientConfig->unloadDirectives();
		$component_name = $this->getFDName();
		if (!is_null($component_name)) {
			$this->FDFileDaemonConfigErr->Display = 'None';
			$this->FDFileDaemonConfig->setHost($this->UserAPIHosts->SelectedValue);
			$this->FDFileDaemonConfig->setComponentName($component_name);
			$this->FDFileDaemonConfig->setResourceName($component_name);
			$this->FDFileDaemonConfig->setLoadValues(true);
			$this->FDFileDaemonConfig->raiseEvent('OnDirectiveListLoad', $this, null);
			$this->BulkApplyPatternsClient->setHost($this->UserAPIHosts->SelectedValue);
		} else {
			$this->FDFileDaemonConfigErr->Display = 'Dynamic';
		}
	}

	public function loadFDResourcesConfig($sender, $param)
	{
		$resource_type = $param->getCallbackParameter();
		$this->DIRClientConfig->unloadDirectives();
		$this->FDFileDaemonConfig->unloadDirectives();
		$component_name = $this->getFDName();
		if (!is_null($component_name) && !empty($resource_type)) {
			$this->FileDaemonResourcesConfig->setHost($this->UserAPIHosts->SelectedValue);
			$this->FileDaemonResourcesConfig->setResourceType($resource_type);
			$this->FileDaemonResourcesConfig->setComponentName($component_name);
			$this->FileDaemonResourcesConfig->loadResourceListTable();
			$this->BulkApplyPatternsClient->setHost($this->UserAPIHosts->SelectedValue);
		} else {
			$this->FileDaemonResourcesConfig->showError(true);
		}
	}

	/**
	 * Set client clientid.
	 *
	 * @param mixed $clientid
	 */
	public function setClientId($clientid)
	{
		$clientid = (int) $clientid;
		$this->BWLimit->setClientId($clientid);
		$this->setViewState(self::CLIENTID, $clientid, 0);
	}

	/**
	 * Get client clientid.
	 *
	 * @return int clientid
	 */
	public function getClientId()
	{
		return $this->getViewState(self::CLIENTID, 0);
	}

	/**
	 * Set client name.
	 *
	 * @param mixed $client_name
	 */
	public function setClientName($client_name)
	{
		$this->BWLimit->setClientName($client_name);
		$this->setViewState(self::CLIENT_NAME, $client_name);
	}

	/**
	 * Get client name.
	 *
	 * @return string client name
	 */
	public function getClientName()
	{
		return $this->getViewState(self::CLIENT_NAME);
	}

	/**
	 * Set client address.
	 *
	 * @param mixed $address
	 */
	public function setClientAddress($address)
	{
		$this->setViewState(self::CLIENT_ADDRESS, $address);
	}

	/**
	 * Get client address.
	 *
	 * @return string address
	 */
	public function getClientAddress()
	{
		return $this->getViewState(self::CLIENT_ADDRESS);
	}

	public function status($sender, $param)
	{
		$raw_status = $this->getModule('api')->get(
			['clients', $this->getClientId(), 'status']
		)->output;
		$this->ClientLog->Text = implode(PHP_EOL, $raw_status);

		$query_str = '?output=json&type=header';
		$graph_status = $this->getModule('api')->get(
			['clients', $this->getClientId(), 'status', $query_str]
		);
		$client_status = [
			'header' => [],
			'running' => [],
			'version' => Params::getComponentVersion($raw_status)
		];
		if ($graph_status->error === 0) {
			$client_status['header'] = $graph_status->output;
			if (!$this->BWLimit->BandwidthLimit->getDirectiveValue() && is_object($client_status['header'])) {
				$this->BWLimit->setBandwidthLimit($client_status['header']->bwlimit);
				$this->getCallbackClient()->callClientFunction(
					'oClientBandwidthLimit.set_value',
					[$client_status['header']->bwlimit]
				);
			}
		}

		$query_str = '?output=json&type=running';
		$graph_status = $this->getModule('api')->get(
			['clients', $this->getClientId(), 'status', $query_str]
		);
		if ($graph_status->error === 0) {
			$client_status['running'] = $graph_status->output;
		}

		$query_str = '?output=json';
		$show = $this->getModule('api')->get(
			['clients', $this->getClientId(), 'show', $query_str]
		);
		if ($show->error === 0) {
			$client_status['show'] = $show->output;
		}

		$this->getCallbackClient()->callClientFunction('init_graphical_client_status', [$client_status]);
	}

	public function setBandwidthControl($sender, $param)
	{
		if ($param instanceof TCallbackEventParameter) {
			[$jobid, $job_uname] = explode('|', $param->getCallbackParameter(), 2);
			$this->JobBandwidth->setJobId($jobid);
			$this->JobBandwidth->setJobUname($job_uname);
		}
	}
}
