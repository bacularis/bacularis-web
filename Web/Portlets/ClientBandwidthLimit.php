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

use Prado\Web\UI\ActiveControls\TActiveLinkButton;

/**
 * Set client bandwidth limit control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class ClientBandwidthLimit extends Portlets
{
	public const CLIENTID = 'ClientId';
	public const CLIENT_NAME = 'ClientName';

	/**
	 * Set up bandwidth limit value.
	 *
	 * @param TActiveLinkButton $sender sender
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setupBandwidthLimit($sender, $param)
	{
		$clientid = $this->getClientId();

		$result = $this->getModule('api')->set(
			['clients', $clientid, 'bandwidth'],
			['limit' => $this->BandwidthLimit->getValue()]
		);

		// this setting to empty string is required to not cache outputs for the same values
		$this->BandwidthLog->Text = '';
		if ($result->error === 0) {
			$this->BandwidthLog->Text = implode(PHP_EOL, $result->output);
		} else {
			$this->BandwidthLog->Text = $result->output;
		}

		$this->getPage()->getCallbackClient()->callClientFunction(
			'show_element',
			['#client_bandwidth_limit_log', true]
		);

		$this->onCallback(null);
	}

	/**
	 * Callback event method.
	 *
	 * @param mixed $param callback parameter or null
	 */
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * Set client clientid.
	 *
	 * @param mixed $clientid
	 */
	public function setClientId($clientid)
	{
		$clientid = (int) $clientid;
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
	 * Get bandwidth limit value from field.
	 *
	 * @return mixed bandwidth limit integer value or null if bandwidth not set
	 */
	public function getBandwidthLimit()
	{
		return $this->BandwidthLimit->getValue();
	}

	/**
	 * Set bandwidth limit value in field.
	 *
	 * @param int $bwlimit bandiwdth limit in bytes
	 */
	public function setBandwidthLimit($bwlimit)
	{
		$this->BandwidthLimit->setDirectiveValue($bwlimit);
	}
}
