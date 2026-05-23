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

use Bacularis\Web\Modules\BaculumWebPage;
use Prado\Prado;

/**
 * Cloud page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class Cloud extends BaculumWebPage
{
	private const SESS_AWS_FD_API_HOST = 'aws_fd_api_host';
	private const SESS_AWS_ACCOUNT = 'aws_account';

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$this->setAPIHosts();
	}

	private function setAPIHosts()
	{
		$sess = $this->getSession();
		$selected = $sess->itemAt(self::SESS_AWS_FD_API_HOST);
		$user_api_hosts = $this->User->getAPIHosts();
		$this->UserFDAPIHosts->DataSource = array_combine($user_api_hosts, $user_api_hosts);
		$this->UserFDAPIHosts->SelectedValue = $selected ?: $this->User->getDefaultAPIHost();
		$this->UserFDAPIHosts->dataBind();
		$this->setFDAPIHost($this->UserFDAPIHosts, null);
	}

	public function setFDAPIHost($sender, $param)
	{
		// Set FD API host
		$selected = $sender->SelectedValue;
		$this->AmazonAccounts->setFDAPIHost($selected);

		// Remember current FD API host selection
		$sess = $this->getSession();
		$sess->open();
		$sess->add(self::SESS_AWS_FD_API_HOST, $selected);

		// load AWS account list
		$this->setAmazonAccounts($sender, $param);
	}

	public function setAmazonAccounts($sender, $param)
	{
		$api = $this->getModule('api');
		$host = $this->UserFDAPIHosts->SelectedValue;
		$result = $api->get(
			['cloud', 'amazon', 'accounts'],
			$host,
			false
		);
		if ($result->error != 0) {
			$this->AmazonAccountCurrent->DataSource = [];
			$this->AmazonAccountCurrent->dataBind();
			$this->setCurrentAccount($sender, $param);
			return;
		}
		$sess = $this->getSession();
		$selected = $sess->itemAt(self::SESS_AWS_ACCOUNT);
		$hosts = array_filter($result->output, fn ($item) => $item->enabled == 1);
		$hosts = array_map(fn ($item) => $item->name, $hosts);
		$values = array_combine($hosts, $hosts);
		asort($values, SORT_NATURAL | SORT_FLAG_CASE);
		$values = array_merge([' ' => Prado::localize('Please select account')], $values);
		$this->AmazonAccountCurrent->DataSource = $values;
		if ($selected) {
			$this->AmazonAccountCurrent->SelectedValue = $selected;
		} elseif (count($values) == 2) {
			$keys = array_keys($values);
			$this->AmazonAccountCurrent->SelectedValue = $values[$keys[1]];
		}
		$this->AmazonAccountCurrent->dataBind();
		$this->setCurrentAccount($sender, $param);
	}

	public function setCurrentAccount($sender, $param)
	{
		if (!$this->IsCallback) {
			// This cannot be run on load
			return;
		}
		$selected = $this->AmazonAccountCurrent->SelectedValue;
		$controls = [
			$this->AmazonAccounts,
			$this->AmazonEC2Instances,
			$this->AmazonEBSVolumes
		];
		for ($i = 0; $i < count($controls); $i++) {
			$controls[$i]->setAmazonAccount($selected);
			$controls[$i]->initialize();
		}
		$sess = $this->getSession();
		$sess->open();
		$sess->add(self::SESS_AWS_ACCOUNT, $selected);
	}

	public function postSaveAccount($sender, $param)
	{
		$this->setAmazonAccounts($sender, $param);
	}

	public function postRemoveAccount($sender, $param)
	{
		$this->setAmazonAccounts($sender, $param);
	}

	public function getNavData()
	{
		return [
			[
				'page' => 'Dashboard'
			],
			[
				'page' => 'Cloud',
				'label' => 'Cloud',
				'icon' => 'fa-solid fa-cloud fa-fw',
				'actions' => []
			]
		];
	}
}
