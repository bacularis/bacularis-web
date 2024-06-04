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
 * Copyright (C) 2013-2020 Kern Sibbald
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
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * New resource page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewResource extends BaculumWebPage
{
	public const COMPONENT_TYPE = 'ComponentType';
	public const COMPONENT_NAME = 'ComponentName';
	public const RESOURCE_TYPE = 'ResourceType';
	public const ORIGIN_URL = 'OriginUrl';

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		if (key_exists('HTTP_REFERER', $_SERVER)) {
			$this->setOriginUrl($_SERVER['HTTP_REFERER']);
		}
		$this->setConfigForm();
		$this->loadResourcesToCopy();
	}

	private function setConfigForm($resource_name = null)
	{
		$component_type = null;
		$component_name = null;
		$resource_type = null;
		if ($this->Request->contains('component_type')) {
			$component_type = $this->Request['component_type'];
		}
		if ($this->Request->contains('component_name')) {
			$component_name = $this->Request['component_name'];
		}
		if ($this->Request->contains('resource_type')) {
			$resource_type = $this->Request['resource_type'];
		}
		if ($component_type && $component_name && $resource_type) {
			$this->setComponentType($component_type);
			$this->setComponentName($component_name);
			$this->setResourceType($resource_type);
			// Non-admin can configure only host assigned to him
			$this->NewResource->setHost($this->User->getDefaultAPIHost());
			$this->NewResource->setComponentType($component_type);
			$this->NewResource->setComponentName($component_name);
			$this->NewResource->setResourceType($resource_type);
			if (is_string($resource_name)) {
				$this->NewResource->setResourceName($resource_name);
				$this->NewResource->setLoadValues(true);
				$this->NewResource->setCopyMode(true);
			} else {
				$this->NewResource->setResourceName(null);
				$this->NewResource->setLoadValues(false);
				$this->NewResource->setCopyMode(false);
			}
			$this->NewResource->raiseEvent('OnDirectiveListLoad', $this, null);
			$this->setHosts();
		}
	}

	private function loadResourcesToCopy()
	{
		if ($this->Request->contains('component_type') && $this->Request->contains('resource_type')) {
			$component_type = $this->Request['component_type'];
			$resource_type = $this->Request['resource_type'];
			$resources_start = ['' => ''];
			$params = [
				'config',
				$component_type,
				$resource_type
			];
			$res = $this->getModule('api')->get($params);
			if ($res->error === 0) {
				for ($i = 0; $i < count($res->output); $i++) {
					$r = $res->output[$i]->{$resource_type}->Name;
					$resources[$r] = $r;
				}
				natcasesort($resources);
			}
			$resources = array_merge($resources_start, $resources);
			$this->ResourcesToCopy->DataSource = $resources;
			$this->ResourcesToCopy->dataBind();
		}
	}

	public function copyConfig($sender, $param)
	{
		$resource_name = $this->ResourcesToCopy->SelectedValue;
		if (!empty($resource_name)) {
			$this->setConfigForm($resource_name);
			$this->NewResource->setResourceName(null);
		}
	}

	public function setHosts()
	{
		$config = $this->getModule('host_config')->getConfig();
		$hosts = ['' => Prado::localize('Please select host')];
		$user_api_hosts = $this->User->getAPIHosts();
		foreach ($config as $host => $vals) {
			if (!in_array($host, $user_api_hosts)) {
				continue;
			}
			$item = "Host: $host, Address: {$vals['address']}, Port: {$vals['port']}";
			$hosts[$host] = $item;
		}
		$this->Host->DataSource = $hosts;
		$this->Host->dataBind();
	}

	public function setComponents($sender, $param)
	{
		$components = ['' => Prado::localize('Please select component')];
		$this->NewResourceLog->Display = 'None';
		if ($this->Host->SelectedIndex > 0) {
			$config = $this->getModule('api')->get(
				['config'],
				$this->Host->SelectedValue,
				false
			);
			if ($config->error === 0) {
				for ($i = 0; $i < count($config->output); $i++) {
					$component = (array) $config->output[$i];
					if (key_exists('component_type', $component) && key_exists('component_name', $component)) {
						$label = $this->getModule('misc')->getComponentFullName($component['component_type']);
						$label .= ' - ' . $component['component_name'];
						$components[$component['component_type'] . ';' . $component['component_name']] = $label;
					}
				}
			} else {
				$this->NewResourceLog->Text = var_export($config, true);
				$this->NewResourceLog->Display = 'Dynamic';
			}
		} else {
			$this->Resource->DataSource = [];
			$this->Resource->dataBind();
		}
		$this->Component->DataSource = $components;
		$this->Component->dataBind();
	}

	public function setResource()
	{
		$resources = [];
		if ($this->Component->SelectedIndex > 0) {
			$this->NewResourceLog->Display = 'None';
			[$component_type, $component_name] = explode(';', $this->Component->SelectedValue);
			if ($component_type == 'dir') {
				$resources = [
					"Director",
					"JobDefs",
					"Client",
					"Job",
					"Storage",
					"Catalog",
					"Schedule",
					"Fileset",
					"Pool",
					"Messages",
					"Console",
					"Statistics"
				];
			} elseif ($component_type == 'sd') {
				$resources = [
					"Director",
					"Storage",
					"Device",
					"Autochanger",
					"Messages",
					"Statistics",
					"Cloud"
				];
			} elseif ($component_type == 'fd') {
				$resources = [
					"Director",
					"FileDaemon",
					"Messages",
					"Schedule",
					"Console",
					"Statistics"
				];
			} elseif ($component_type == 'bcons') {
				$resources = [
					"Director",
					"Console"
				];
			}
			$resources = array_combine($resources, $resources);
		}
		$this->Resource->DataSource = $resources;
		$this->Resource->dataBind();
	}

	public function getComponentType()
	{
		return $this->getViewState(self::COMPONENT_TYPE);
	}

	public function setComponentType($type)
	{
		$this->setViewState(self::COMPONENT_TYPE, $type);
	}

	public function getComponentName()
	{
		return $this->getViewState(self::COMPONENT_NAME);
	}

	public function setComponentName($name)
	{
		$this->setViewState(self::COMPONENT_NAME, $name);
	}

	public function getResourceType()
	{
		return $this->getViewState(self::RESOURCE_TYPE);
	}

	public function setResourceType($type)
	{
		$this->setViewState(self::RESOURCE_TYPE, $type);
	}

	public function getOriginUrl()
	{
		return $this->getViewState(self::ORIGIN_URL);
	}

	public function setOriginUrl($url)
	{
		$this->setViewState(self::ORIGIN_URL, $url);
	}

	public function createResource()
	{
		if ($this->Host->SelectedIndex > 0 && $this->Component->SelectedIndex > 0 && $this->Resource->SelectedValue) {
			$host = $this->Host->SelectedValue;
			[$component_type, $component_name] = explode(';', $this->Component->SelectedValue);
			$resource_type = $this->Resource->SelectedValue;
			$this->goToPage('NewResource', [
				'host' => $host,
				'component_type' => $component_type,
				'component_name' => $component_name,
				'resource_type' => $resource_type
			]);
		}
	}
}
