<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\Web\Portlets;

/**
 * Amazon module providing common interface for Amazon-type portlets.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AmazonBase extends Portlets
{
	private const FD_API_HOST = 'FdAPIHost';
	private const AMAZON_ACCOUNT = 'AmazonAccount';

	public $web_config;

	/**
	 * Initialize page.
	 *
	 * @param mixed $param oninit event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->web_config = $this->getPage()->web_config;
	}

	public function setFDAPIHost($host)
	{
		$this->setViewState(self::FD_API_HOST, $host);
	}

	public function getFDAPIHost()
	{
		return $this->getViewState(self::FD_API_HOST);
	}

	public function setAmazonAccount($account)
	{
		$account = trim($account);
		$this->setViewState(self::AMAZON_ACCOUNT, $account);
	}

	public function getAmazonAccount()
	{
		return $this->getViewState(self::AMAZON_ACCOUNT);
	}
}
