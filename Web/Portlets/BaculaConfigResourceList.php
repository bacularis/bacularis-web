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

Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveRepeater');
Prado::using('Application.Web.Portlets.BaculaConfigResources');
Prado::using('Application.Web.Portlets.Portlets');

/**
 * Bacula config resource list control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class BaculaConfigResourceList extends Portlets {

	const HOST = 'Host';
	const COMPONENT_TYPE = 'ComponentType';
	const COMPONENT_NAME = 'ComponentName';
	const RESOURCE_TYPE = 'ResourceType';
	const RESOURCE_LIST = 'ResourceList';

	public function onPreRender($param) {
		parent::onPreRender($param);
		if ($this->getPage()->IsCallBack || $this->getPage()->IsPostBack) {
			return;
		}
		$this->prepareTable();
	}

	private function prepareTable() {
		$res_list = $this->getResourceList();
		$this->ResourceListHeaderRepeater->DataSource = $res_list;
		$this->ResourceListHeaderRepeater->dataBind();
		$this->ResourceListFooterRepeater->DataSource = $res_list;
		$this->ResourceListFooterRepeater->dataBind();
		$this->ResourceListColumnsRepeater->DataSource = $res_list;
		$this->ResourceListColumnsRepeater->dataBind();
	}

	public function showError($show) {
		$cbc = $this->getPage()->getCallbackClient();
		if ($show) {
			$cbc->hide($this->ClientID . '_container');
			$cbc->show($this->ClientID . '_error_msg');
		} else {
			$cbc->hide($this->ClientID . '_error_msg');
			$cbc->show($this->ClientID . '_container');
		}
	}

	public function loadResourceListTable() {
		$this->showError(false);
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$this->ResourceTypeAddLink->Text = $resource_type;
		$this->ResourceTypeAddWindowTitle->Text = $resource_type;
		$this->ResourceTypeEditWindowTitle->Text = $resource_type;
		$config = $this->getModule('api')->get(
			[
				'config',
				$component_type,
				$resource_type
			],
			$this->getHost()
		);
		if ($config->error === 0) {
			$res_list = $this->getResourceList();
			$directives = [];
			for ($i = 0; $i < count($config->output); $i++) {
				$data = [];
				for ($j = 0; $j < count($res_list); $j++) {
					if (property_exists($config->output[$i]->{$resource_type}, $res_list[$j]['name'])) {
						$data[$res_list[$j]['name']] = $config->output[$i]->{$resource_type}->{$res_list[$j]['name']};
					}
				}
				$directives[] = $data;
			}
			$this->getPage()->getCallbackClient()->callClientFunction(
				'oBaculaConfigResourceList' . $this->ClientID . '.init',
				[$directives]
			);
		}
		$this->ResourceConfig->unloadDirectives();
	}

	public function loadResourceWindow($sender, $param) {
		list($cmd, $name) = $param->getCallbackParameter();
		$copy_el_id = 'resource_window_copy_resource' . $this->ClientID;
		if (!empty($name)) {
			// edit existing resource
			$this->ResourceConfig->setResourceName($name);
			$this->ResourceConfig->setLoadValues(true);
			$this->ResourceConfig->setCopyMode(false);
			$this->getPage()->getCallbackClient()->hide($copy_el_id);
		} else {
			// add new resource
			$this->ResourceConfig->setLoadValues(false);
			$this->getPage()->getCallbackClient()->callClientFunction(
				'oBaculaConfigSection.show_sections',
				[true]
			);
			$this->loadResourcesToCopy();
			$this->getPage()->getCallbackClient()->show($copy_el_id);
		}
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$this->ResourceConfig->setHost($host);
		$this->ResourceConfig->setComponentType($component_type);
		$this->ResourceConfig->setComponentName($component_name);
		$this->ResourceConfig->setResourceType($resource_type);
		$this->ResourceConfig->IsDirectiveCreated = false;
		$this->ResourceConfig->raiseEvent('OnDirectiveListLoad', $this, null);
	}

	public function unloadResourceWindow($sender, $param) {
		$this->ResourceConfig->unloadDirectives();
	}

	private function loadResourcesToCopy() {
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$resources = ['' => ''];
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
		}
		$this->ResourcesToCopy->DataSource = $resources;
		$this->ResourcesToCopy->dataBind();
	}

	public function copyConfig($sender, $param) {
		$resource_name = $this->ResourcesToCopy->SelectedValue;
		if (!empty($resource_name)) {
			$this->ResourceConfig->setResourceName($resource_name);
			$this->ResourceConfig->setLoadValues(true);
			$this->ResourceConfig->setCopyMode(true);
			$this->ResourceConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}


	public function removeResource($sender, $param) {
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$resource_name = $param->getCallbackParameter();
		$result = $this->getModule('api')->get(
			[
				'config',
				$component_type
			],
			$host
		);
		$config = [];
		if (is_object($result) && $result->error === 0 && is_array($result->output)) {
			$config = $result->output;
		}
		$deps = $this->getModule('data_deps')->checkDependencies(
			$component_type,
			$resource_type,
			$resource_name,
			$config
		);
		if (count($deps) === 0) {
			// NO DEPENDENCY. Ready to remove.
			BaculaConfigResources::removeResourceFromConfig(
				$config,
				$resource_type,
				$resource_name
			);
			$result = $this->getModule('api')->set(
				['config', $component_type],
				['config' => json_encode($config)],
				$host,
				false
			);
			if ($result->error !== 0) {
				$this->showRemovedResourceError($result->output);
			} else {
				$this->loadResourceListTable($sender, $param);
			}
		} else {
			// DEPENDENCIES EXIST. List them on the interface.
			$error_message = BaculaConfigResources::prepareDependenciesError(
				$deps,
				$resource_type,
				$resource_name
			);
			$this->showRemovedResourceError($error_message);
		}
	}

	private function showRemovedResourceError($error_message) {
		$this->RemoveResourceError->Text = $error_message;
		$err_win_id = 'resource_error_window' . $this->ClientID;
		$this->getPage()->getCallbackClient()->show($err_win_id);
	}

	public function renameResource($sender, $param) {
		$this->onRename($param);
	}

	public function onRename($param) {
		$this->raiseEvent('OnRename', $this, $param);
	}

	public function getHost() {
		return $this->getViewState(self::HOST);
	}

	public function setHost($host) {
		$this->setViewState(self::HOST, $host);
	}

	public function getComponentType() {
		return $this->getViewState(self::COMPONENT_TYPE);
	}

	public function setComponentType($type) {
		$this->setViewState(self::COMPONENT_TYPE, $type);
	}

	public function getComponentName() {
		return $this->getViewState(self::COMPONENT_NAME);
	}

	public function setComponentName($name) {
		$this->setViewState(self::COMPONENT_NAME, $name);
	}

	public function getResourceType() {
		return $this->getViewState(self::RESOURCE_TYPE);
	}

	public function setResourceType($type) {
		$this->setViewState(self::RESOURCE_TYPE, $type);
	}

	public function getResourceList() {
		return $this->getViewState(self::RESOURCE_LIST, []);
	}

	public function setResourceList($list) {
		$this->setViewState(self::RESOURCE_LIST, $list);
	}
}
?>
