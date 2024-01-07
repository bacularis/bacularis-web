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

namespace Bacularis\Web\Portlets;

use Prado\Prado;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;

/**
 * FileSet browser control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class FileSetBrowser extends Portlets
{
	public const BCLIENT_ID = 'BClientId';
	public const PATH = 'Path';

	/**
	 * Load client list.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function loadClients($sender, $param)
	{
		$client_list = [];
		$clients = $this->getModule('api')->get(['clients'])->output;
		for ($i = 0; $i < count($clients); $i++) {
			$client_list[$clients[$i]->clientid] = $clients[$i]->name;
		}
		arsort($client_list);
		$client_list['none'] = Prado::localize('Please select Client');
		\uksort($client_list, __NAMESPACE__ . '\sort_client_list');
		$this->Client->DataSource = $client_list;
		$this->Client->dataBind();
	}

	/**
	 * Set selected client.
	 *
	 * @param TActiveDropDownList $sender, sender object
	 * @param TCommandParameter $param parameters object
	 */
	public function selectClient($sender, $param)
	{
		$client_id = $this->Client->getSelectedValue();
		if ($client_id !== 'none') {
			$this->setBClientId($client_id);
			$this->goToPath();
		}
	}

	public function getItems($sender, $param)
	{
		if ($param instanceof TCallbackEventParameter) {
			$path = $param->getCallbackParameter();
			$this->setPath($path);
			$this->goToPath();
		}
	}

	public function goToPath()
	{
		$client_id = $this->getBClientId();
		$query = '?path=' . rawurlencode($this->getPath());
		$params = [
			'clients',
			$client_id,
			'ls',
			$query
		];
		$result = $this->getModule('api')->get($params);
		$this->getPage()->getCallbackClient()->callClientFunction(
			'FileSetBrowser_set_content' . $this->ClientID,
			json_encode($result->output)
		);
	}

	public function setBClientId($bclient_id)
	{
		$this->setViewState(self::BCLIENT_ID, $bclient_id);
	}

	public function getBClientId()
	{
		return $this->getViewState(self::BCLIENT_ID);
	}

	public function setPath($path)
	{
		$this->setViewState(self::PATH, $path);
	}

	public function getPath()
	{
		return $this->getViewState(self::PATH, '/');
	}
}

function sort_client_list($a, $b)
{
	if ($a === 'none') {
		return -1;
	} else {
		return 1;
	}
}
