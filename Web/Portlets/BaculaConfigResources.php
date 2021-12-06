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

Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('System.Web.UI.ActiveControls.TActiveRepeater');
Prado::using('Application.Web.Portlets.ResourceListTemplate');

/**
 * Bacula config resource control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class BaculaConfigResources extends ResourceListTemplate {

	const CHILD_CONTROL = 'BaculaConfigDirectives';

	public $resource_names = array();

	public function getConfigData($host, $component_type) {
		$params = array('config', $component_type);
		$result = $this->Application->getModule('api')->get($params, $host, false);
		$config = array();
		if (is_object($result) && $result->error === 0 && is_array($result->output)) {
			$config = $result->output;
		}
		return $config;
	}

	public function loadConfig() {
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resources = array();
		$config = $this->getConfigData($host, $component_type);
		for ($i = 0; $i < count($config); $i++) {
			$resource_type = $this->getConfigResourceType($config[$i]);
			$resource_name = property_exists($config[$i]->{$resource_type}, 'Name') ? $config[$i]->{$resource_type}->Name : '';
			$resource = array(
				'host' => $host,
				'component_type' => $component_type,
				'component_name' => $component_name,
				'resource_type' => $resource_type,
				'resource_name' => $resource_name
			);
			array_push($resources, $resource);
			if (!array_key_exists($resource_type, $this->resource_names)) {
				$this->resource_names[$resource_type] = array();
			}
			array_push($this->resource_names[$resource_type], $resource_name);
		}

		$this->RepeaterResources->DataSource = $resources;
		$this->RepeaterResources->dataBind();
	}

	public function createResourceListElement($sender, $param) {
		if (!is_array($param->Item->Data)) {
			// skip parent repeater items
			return;
		}
		$control = $this->getChildControl($param->Item, self::CHILD_CONTROL);
		if (is_object($control)) {
			$control->setHost($param->Item->Data['host']);
			$control->setComponentType($param->Item->Data['component_type']);
			$control->setComponentName($param->Item->Data['component_name']);
			$control->setResourceType($param->Item->Data['resource_type']);
			$control->setResourceName($param->Item->Data['resource_name']);
			$control->setResourceNames($this->resource_names);
		}
		$param->Item->RemoveResource->setCommandParameter($param->Item->Data);
	}

	public function getDirectives($sender, $param) {
		$control = $this->getChildControl($sender->getParent(), self::CHILD_CONTROL);
		if (is_object($control)) {
			$control->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Remove resource callback method.
	 *
	 * @return object $sender sender instance
	 * @return mixed $param additional parameters
	 * @return none
	 */
	public function removeResource($sender, $param) {
		if (!$this->getPage()->IsCallback) {
			// removing resource available only by callback
			return;
		}
		$host_params = $param->getCommandParameter();
		if (!is_array($host_params) || count($host_params) === 0) {
			return;
		}
		$host = $this->getHost();
		$config = $this->getConfigData($host, $host_params['component_type']);
		$deps = $this->getModule('data_deps')->checkDependencies(
			$host_params['component_type'],
			$host_params['resource_type'],
			$host_params['resource_name'],
			$config
		);
		if (count($deps) === 0) {
			// NO DEPENDENCY. Ready to remove.
			self::removeResourceFromConfig(
				$config,
				$host_params['resource_type'],
				$host_params['resource_name']
			);
			$result = $this->getModule('api')->set(
				array('config',	$host_params['component_type']),
				array('config' => json_encode($config)),
				$host,
				false
			);
			if ($result->error === 0) {
				$this->showRemovedResourceInfo(
					$host_params['resource_type'],
					$host_params['resource_name']
				);
			} else {
				$this->showRemovedResourceError($result->output);
			}
		} else {
			// DEPENDENCIES EXIST. List them on the interface.
			$error_message = self::prepareDependenciesError(
				$deps,
				$host_params['resource_type'],
				$host_params['resource_name']
			);
			$this->showRemovedResourceError($error_message);
		}
	}

	/**
	 * Show removed resource information.
	 *
	 * @param string $resource_type removed resource type
	 * @param string $resource_name removed resource name
	 * @return none
	 */
	private function showRemovedResourceInfo($resource_type, $resource_name) {
		$msg = Prado::localize('Resource %s "%s" removed successfully.');
		$msg = sprintf(
			$msg,
			$resource_type,
			$resource_name
		);
		$this->RemoveResourceOk->Text = $msg;
		$this->getPage()->getCallbackClient()->hide($this->RemoveResourceError);
		$this->getPage()->getCallbackClient()->show($this->RemoveResourceOk);
	}

	/**
	 * Show removed resource error message.
	 *
	 * @param string $error_message error message
	 * @return none
	 */
	private function showRemovedResourceError($error_message) {
		$this->RemoveResourceError->Text = $error_message;
		$this->getPage()->getCallbackClient()->hide($this->RemoveResourceOk);
		$this->getPage()->getCallbackClient()->show($this->RemoveResourceError);
	}

	/**
	 * Show dependencies error message.
	 * NOTE: Method called also externally (@see BaculaConfigResourceList)
	 *
	 * @param array $deps list dependencies for the removing resource
	 * @param string $resource_type resource type of the removing resource
	 * @param string $resource_name resource name of the removing resource
	 * @return string error message
	 */
	public static function prepareDependenciesError($deps, $resource_type, $resource_name) {
		$emsg = Prado::localize('Resource %s "%s" is used in the following resources:');
		$emsg = sprintf($emsg, $resource_type, $resource_name);
		$emsg_deps = Prado::localize('Component: %s, Resource: %s "%s", Directive: %s');
		$dependencies = array();
		for ($i = 0; $i < count($deps); $i++) {
			$dependencies[] = sprintf(
				$emsg_deps,
				$deps[$i]['component_type'],
				$deps[$i]['resource_type'],
				$deps[$i]['resource_name'],
				$deps[$i]['directive_name']
			);
		}
		$emsg_sum = Prado::localize('Please unassign resource %s "%s" from these resources and try again.');
		$emsg_sum = sprintf($emsg_sum, $resource_type, $resource_name);
		$error = array($emsg, implode('<br />', $dependencies),  $emsg_sum);
		return implode('<br /><br />', $error);
	}

	/**
	 * Remove resource from config.
	 * Note, passing config by reference.
	 * NOTE: Method called also externally (@see BaculaConfigResourceList)
	 *
	 * @param array $config entire config
	 * @param string $resource_type resource type to remove
	 * @param string $resource_name resource name to remove
	 * @return none
	 */
	public static function removeResourceFromConfig(&$config, $resource_type, $resource_name) {
		for ($i = 0; $i < count($config); $i++) {
			foreach ($config[$i] as $rtype => $resource) {
				if (!property_exists($resource, 'Name')) {
					continue;
				}
				if ($rtype === $resource_type && $resource->Name === $resource_name) {
					// remove resource
					array_splice($config, $i, 1);
					break 2;
				}
			}
		}
	}
}
?>
