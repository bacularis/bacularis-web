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

namespace Bacularis\Web\Portlets;

use Prado\Prado;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Errors\BaculaConfigError;

/**
 * Bacula config resource control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BaculaConfigResources extends ResourceListTemplate
{
	public const CHILD_CONTROL = 'BaculaConfigDirectives';

	public $resource_names = [];

	public function getConfigData($host, $component_type)
	{
		$params = ['config', $component_type];
		$result = $this->Application->getModule('api')->get($params, $host, false);
		$config = [];
		if (is_object($result) && $result->error === 0 && is_array($result->output)) {
			$config = $result->output;
		}
		return $config;
	}

	public function loadConfig()
	{
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resources = [];
		$config = $this->getConfigData($host, $component_type);
		for ($i = 0; $i < count($config); $i++) {
			$resource_type = $this->getConfigResourceType($config[$i]);
			$resource_name = property_exists($config[$i]->{$resource_type}, 'Name') ? $config[$i]->{$resource_type}->Name : '';
			$resource = [
				'host' => $host,
				'component_type' => $component_type,
				'component_name' => $component_name,
				'resource_type' => $resource_type,
				'resource_name' => $resource_name
			];
			array_push($resources, $resource);
			if (!array_key_exists($resource_type, $this->resource_names)) {
				$this->resource_names[$resource_type] = [];
			}
			array_push($this->resource_names[$resource_type], $resource_name);
		}

		$this->RepeaterResources->DataSource = $resources;
		$this->RepeaterResources->dataBind();
	}

	public function createResourceListElement($sender, $param)
	{
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

	public function getDirectives($sender, $param)
	{
		$control = $this->getChildControl($sender->getParent(), self::CHILD_CONTROL);
		if (is_object($control)) {
			$control->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Remove resource callback method.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 * @return object $sender sender instance
	 * @return mixed $param additional parameters
	 */
	public function removeResource($sender, $param)
	{
		if (!$this->getPage()->IsCallback) {
			// removing resource available only by callback
			return;
		}
		$host_params = $param->getCommandParameter();
		if (!is_array($host_params) || count($host_params) === 0) {
			return;
		}
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$result = $this->getModule('api')->remove(
			$params,
			$host,
			false
		);
		$component_full_name = $this->getModule('misc')->getComponentFullName($component_type);
		$amsg = "%s Component: {$component_full_name}, Resource: {$resource_type}, Name: {$resource_name}";
		if ($result->error === 0) {
			$this->getModule('api')->set(['console'], ['reload']);
			$this->showRemovedResourceInfo(
				$resource_type,
				$resource_name
			);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, 'Remove Bacula config resource.')
			);
		} else {
			$error_message = '';
			if ($result->error === BaculaConfigError::ERROR_CONFIG_DEPENDENCY_ERROR) {
				$error_message = BaculaConfigDirectives::getDependenciesError(
					json_decode($result->output, true),
					$resource_type,
					$resource_name
				);
			} else {
				$error_message = $result->output;
				$this->getModule('audit')->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_CONFIG,
					sprintf($amsg, 'Problem with removing Bacula config resource.')
				);
			}
			$this->showRemovedResourceError($error_message);
		}
	}

	/**
	 * Show removed resource information.
	 *
	 * @param string $resource_type removed resource type
	 * @param string $resource_name removed resource name
	 */
	private function showRemovedResourceInfo($resource_type, $resource_name)
	{
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
	 */
	private function showRemovedResourceError($error_message)
	{
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
	public static function prepareDependenciesError($deps, $resource_type, $resource_name)
	{
		$emsg = Prado::localize('Resource %s "%s" is used in the following resources:');
		$emsg = sprintf($emsg, $resource_type, $resource_name);
		$emsg_deps = Prado::localize('Component: %s, Resource: %s "%s", Directive: %s');
		$dependencies = [];
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
		$error = [$emsg, implode('<br />', $dependencies),  $emsg_sum];
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
	 */
	public static function removeResourceFromConfig(&$config, $resource_type, $resource_name)
	{
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
