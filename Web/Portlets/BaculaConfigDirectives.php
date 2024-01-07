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
use Prado\TPropertyValue;
use Prado\Web\UI\TCommandEventParameter;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Logging;

/**
 * Bacula config directives control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BaculaConfigDirectives extends DirectiveListTemplate
{
	public const SHOW_REMOVE_BUTTON = 'ShowRemoveButton';
	public const SHOW_CANCEL_BUTTON = 'ShowCancelButton';
	public const SHOW_ALL_DIRECTIVES = 'ShowAllDirectives';
	public const SHOW_BOTTOM_BUTTONS = 'ShowBottomButtons';
	public const SHOW_SECTION_TABS = 'ShowSectionTabs';
	public const SAVE_DIRECTIVE_ACTION_OK = 'SaveDirectiveActionOk';
	public const CANCEL_DIRECTIVE_ACTION_OK = 'CancelDirectiveActionOk';
	public const DISABLE_RENAME = 'DisableRename';

	private $show_all_directives = false;

	public $resource_names = [];

	private $directive_types = [
		'Bacularis\Web\Portlets\DirectiveCheckBox',
		'Bacularis\Web\Portlets\DirectiveCheckBoxSimple',
		'Bacularis\Web\Portlets\DirectiveComboBox',
		'Bacularis\Web\Portlets\DirectiveComboBoxReload',
		'Bacularis\Web\Portlets\DirectiveInteger',
		'Bacularis\Web\Portlets\DirectiveListBox',
		'Bacularis\Web\Portlets\DirectiveOrderedListBox',
		'Bacularis\Web\Portlets\DirectivePassword',
		'Bacularis\Web\Portlets\DirectiveTextBox',
		'Bacularis\Web\Portlets\DirectiveSize',
		'Bacularis\Web\Portlets\DirectiveSpeed',
		'Bacularis\Web\Portlets\DirectiveTimePeriod'
	];

	private $directive_list_types = [
		'Bacularis\Web\Portlets\DirectiveFileSet',
		'Bacularis\Web\Portlets\DirectiveSchedule',
		'Bacularis\Web\Portlets\DirectiveMessages',
		'Bacularis\Web\Portlets\DirectiveRunscript',
		'Bacularis\Web\Portlets\DirectiveMultiComboBox',
		'Bacularis\Web\Portlets\DirectiveMultiTextBox'
	];

	private $field_multiple_values = [
		'ListBox',
		'OrderedListBox'
	];

	public $display_directives;

	public function onInit($param)
	{
		parent::onInit($param);
		if (!$this->getPage()->isPostBack && !$this->getPage()->IsCallBack) {
			$this->Cancel->Visible = $this->getShowCancelButton();
		}
	}

	private function getConfigData($host, array $parameters)
	{
		$default_params = ['config'];
		$params = array_merge($default_params, $parameters);
		$result = $this->Application->getModule('api')->get($params, $host, false);
		$config = [];
		if (is_object($result) && $result->error === 0 && (is_object($result->output) || is_array($result->output))) {
			$config = $result->output;
		}
		return $config;
	}

	public function loadConfig($sender = null, $param = null, $name = '', $data = [])
	{
		if (empty($name) || $name === 'ondirectivelistload') {
			// initial config load, clear any remembered form values from previous sessions
			$this->setData($data);
			// default disable showing all directives
			$this->setShowAllDirectives(false);
		} else {
			$directive_values = $this->getDirectiveValues(true);
			$this->setData($directive_values);
		}

		$copy_mode = $this->getCopyMode();
		if ($copy_mode) {
			$this->setShowAllDirectives(true);
		}

		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$directives = [];
		$config = new \StdClass();
		$predefined = false;
		$load_values = $this->getLoadValues();
		if ($load_values) {
			$config = $this->getConfigData($host, [
				$component_type,
				$resource_type,
				$resource_name
			]);
			if (empty($component_name) || empty($resource_type) || empty($resource_name)) {
				$this->ConfigDirectives->Display = 'None';
				return;
			}
		}

		$data = $this->getData();
		// Pre-defined config for new resource can be provided in Data property.
		if (!empty($data)) {
			$config = (object) $data;
			$predefined = true;
		}

		$parent_directives = new \StdClass();
		if ($resource_type === 'Job' && isset($config->JobDefs)) {
			$parent_directives = $this->getConfigData($host, [
				$component_type,
				'JobDefs',
				$config->JobDefs
			]);
		}
		$data_desc = $this->Application->getModule('data_desc');
		$resource_desc = $data_desc->getDescription($component_type, $resource_type);
		foreach ($resource_desc as $directive_name => $directive_desc) {
			$in_config = false;
			if ($load_values === true) {
				$in_config = property_exists($config, $directive_name);
			}

			$directive_value = null;
			if (($in_config === true && $load_values === true) || ($predefined && property_exists($config, $directive_name))) {
				$directive_value = $config->{$directive_name};
			}

			$default_value = null;
			$data = null;
			$field_type = 'TextBox';
			$resource = null;
			$required = false;
			// @TODO: Add support for all directive properties defined in description file
			if (is_object($directive_desc)) {
				if (property_exists($directive_desc, 'Required')) {
					$required = $directive_desc->Required;
					if ($directive_name != 'Name' && property_exists($parent_directives, $directive_name)) {
						// values can be taken from JobDefs
						$required = false;
					}
				}
				if (property_exists($directive_desc, 'DefaultValue')) {
					$default_value = $directive_desc->DefaultValue;
				}
				if (property_exists($directive_desc, 'Data')) {
					$data = $directive_desc->Data;
				}
				if (property_exists($directive_desc, 'FieldType')) {
					$field_type = $directive_desc->FieldType;
				}
				if (property_exists($directive_desc, 'Resource')) {
					$resource = $directive_desc->Resource;
				}
			}

			if ((!is_array($directive_value) && !is_object($directive_value)) || in_array($field_type, $this->field_multiple_values)) {
				$directive_value = [$directive_value];
			}
			if (is_object($directive_value)) {
				$directive_value = (array) $directive_value;
			}

			if ($directive_name === 'Include' || $directive_name === 'Exclude' || $directive_name === 'Runscript' || $directive_name === 'Destinations') {
				// provide all include blocks at once
				$directive_value = [[
					$directive_name => $directive_value,
				]];
				if (property_exists($config, 'Exclude')) {
					$directive_value[0]['Exclude'] = (array) $config->{'Exclude'};
				}
			}

			if ($resource_type === 'Schedule' && in_array($directive_name, ['Run', 'Connect'])) {
				$directive_value = [$directive_value];
			}

			if ($directive_name === 'Exclude') {
				continue;
			}

			foreach ($directive_value as $key => $value) {
				$directive = [
					'host' => $host,
					'component_type' => $component_type,
					'component_name' => $component_name,
					'resource_type' => $resource_type,
					'resource_name' => $resource_name,
					'directive_name' => $directive_name,
					'directive_value' => $value,
					'default_value' => $default_value,
					'required' => $required,
					'data' => $data,
					'resource' => $resource,
					'field_type' => $field_type,
					'label' => $directive_name,
					'in_config' => $in_config,
					'parent_name' => null,
					'group_name' => null,
					'section' => $directive_desc->Section,
					'show' => (($in_config || !$load_values) || $this->getShowAllDirectives())
				];
				array_push($directives, $directive);
			}
		}
		$config = $this->getConfigData($host, [$component_type]);
		for ($i = 0; $i < count($config); $i++) {
			$resource_type = $this->getConfigResourceType($config[$i]);
			$resource_name = property_exists($config[$i]->{$resource_type}, 'Name') ? $config[$i]->{$resource_type}->Name : '';
			if (!array_key_exists($resource_type, $this->resource_names)) {
				$this->resource_names[$resource_type] = [];
			}
			array_push($this->resource_names[$resource_type], $resource_name);
		}
		$this->setResourceNames($this->resource_names);
		$this->RepeaterDirectives->DataSource = $directives;
		$this->RepeaterDirectives->dataBind();
		$this->ConfigDirectives->Display = 'Dynamic';
		$this->IsDirectiveCreated = true;
		if ($copy_mode || $this->getShowAllDirectives()) {
			$this->getPage()->getCallbackClient()->callClientFunction(
				'oBaculaConfigSection.show_sections',
				[true, $this->ClientID . '_directives']
			);
		}
		$this->showLoader(false);
		$this->getPage()->getCallbackClient()->show($this->ConfigDirectives);

		// set buttons
		$this->DirectiveSetting->showOptions($load_values && !$copy_mode);
	}

	public function loadDirectives($sender, $param, $name)
	{
		$show_all_directives = !$this->getShowAllDirectives();
		$this->setShowAllDirectives($show_all_directives);
		$this->loadConfig($sender, $param, $name);
		$this->getPage()->getCallbackClient()->callClientFunction(
			'oBaculaConfigSection.show_sections',
			[$show_all_directives, $this->ClientID . '_directives']
		);
	}

	public function unloadDirectives()
	{
		$this->RepeaterDirectives->DataSource = [];
		$this->RepeaterDirectives->dataBind();
	}

	public function getDirectiveValues($data_mode = false)
	{
		$directives = [];
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$resource_desc = $this->Application->getModule('data_desc')->getDescription($component_type, $resource_type);
		for ($i = 0; $i < count($this->directive_types); $i++) {
			$controls = $this->RepeaterDirectives->findControlsByType($this->directive_types[$i]);
			for ($j = 0; $j < count($controls); $j++) {
				$parent_name = $controls[$j]->getParentName();
				if (!is_null($parent_name)) {
					continue;
				}
				$controls[$j]->setValue(); // set value for tracking unset and empty fields
				$directive_name = $controls[$j]->getDirectiveName();
				$directive_value = $controls[$j]->getDirectiveValue();

				if (is_null($directive_name)) {
					// skip controls without data
					continue;
				}

				$default_value = null;
				if (key_exists($directive_name, $resource_desc)) {
					$default_value = $resource_desc[$directive_name]->DefaultValue;
				}
				$in_config = $controls[$j]->getInConfig();
				if (is_null($directive_value) || (is_array($directive_value) && count($directive_value) == 0)) {
					// skip not changed values that don't exist in config
					continue;
				}
				if ($this->directive_types[$i] === 'Bacularis\Web\Portlets\DirectiveCheckBox' || $this->directive_types[$i] === 'Bacularis\Web\Portlets\DirectiveCheckBoxSimple') {
					settype($default_value, 'bool');
				} elseif ($this->directive_types[$i] === 'Bacularis\Web\Portlets\DirectiveInteger') {
					settype($directive_value, 'int');
				}
				if ($directive_value === $default_value && $in_config === false) {
					// value the same as default value, skip it
					continue;
				}
				$directives[$directive_name] = $directive_value;
			}
		}
		for ($i = 0; $i < count($this->directive_list_types); $i++) {
			$controls = $this->RepeaterDirectives->findControlsByType($this->directive_list_types[$i]);
			for ($j = 0; $j < count($controls); $j++) {
				$parent_name = $controls[$j]->getParentName();
				if (!is_null($parent_name)) {
					continue;
				}
				$directive_name = $controls[$j]->getDirectiveName();
				if ($data_mode && method_exists($controls[$j], 'getDirectiveData')) {
					$directive_value = $controls[$j]->getDirectiveData();
				} else {
					$directive_value = $controls[$j]->getDirectiveValue();
				}
				if (is_null($directive_value)) {
					continue;
				}
				if ($directive_name === 'Exclude') {
					continue;
				}
				if (!array_key_exists($directive_name, $directives)) {
					$directives[$directive_name] = [];
				}
				if (is_array($directive_value)) {
					if ($this->directive_list_types[$i] === 'Bacularis\Web\Portlets\DirectiveMessages') {
						if ($data_mode) {
							$directive_value['Destinations'] = $directive_value;
						}
						$directives = array_merge($directives, $directive_value);
					} elseif ($this->directive_list_types[$i] === 'Bacularis\Web\Portlets\DirectiveRunscript') {
						if (!isset($directives[$directive_name])) {
							$directives[$directive_name] = [];
						}
						$directives[$directive_name] = array_merge($directives[$directive_name], $directive_value[$directive_name]);
					} elseif ($this->directive_list_types[$i] === 'Bacularis\Web\Portlets\DirectiveFileSet') {
						if (key_exists('Exclude', $directive_value) && count($directive_value['Exclude']) > 0) {
							$directives['Exclude'] = [$directive_value['Exclude']];
						}
						$directives[$directive_name] = $directive_value[$directive_name];
					} elseif ($this->directive_list_types[$i] === 'Bacularis\Web\Portlets\DirectiveSchedule') {
						if ($data_mode) {
							$directives[$directive_name] = $directive_value;
						} else {
							$directives[$directive_name] = $directive_value[$directive_name];
						}
					} elseif ($this->directive_list_types[$i] === 'Bacularis\Web\Portlets\DirectiveMultiTextBox' || $this->directive_list_types[$i] === 'Bacularis\Web\Portlets\DirectiveMultiComboBox') {
						if (key_exists($directive_name, $directives)) {
							$directive_value = array_merge($directives[$directive_name], $directive_value);
						}
						if ($data_mode) {
							$directives[$directive_name] = $directive_value;
						} else {
							$directives[$directive_name] = array_filter($directive_value);
						}
					} elseif (array_key_exists($directive_name, $directive_value)) {
						$directives[$directive_name][] = $directive_value[$directive_name];
					} elseif (count($directive_value) > 0) {
						$directives[$directive_name][] = $directive_value;
					}
				} else {
					$directives[$directive_name] = $directive_value;
				}
			}
		}
		return $directives;
	}

	public function saveResource($sender, $param)
	{
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$directives = $this->getDirectiveValues();
		$load_values = $this->getLoadValues();
		$res_name_dir = key_exists('Name', $directives) ? $directives['Name'] : null;
		$resource_name = $this->getResourceName();
		if (!$res_name_dir && $resource_name) {
			// In some cases with double control load Name value stays empty. Recreate it here.
			$directives['Name'] = $res_name_dir = $resource_name;
		}
		if ($load_values === true && $this->getCopyMode() === false) {
			if ($resource_name !== $res_name_dir) {
				// RENAME RESOURCE
				if ($this->renameResource($res_name_dir)) {
					// set new resource name
					$this->setResourceName($res_name_dir);
					$resource_name = $res_name_dir;
					$param = new TCommandEventParameter('rename', [
						'resource_type' => $resource_type,
						'resource_name' => $resource_name
					]);
					$this->onRename($param);
				}
			}
		} else {
			$resource_name = $res_name_dir;
		}

		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$result = $this->getModule('api')->set(
			$params,
			['config' => json_encode($directives)],
			$host,
			false
		);
		$component_full_name = $this->getModule('misc')->getComponentFullName($component_type);
		$amsg = "%s Component: {$component_full_name}, Resource: {$resource_type}, Name: {$resource_name}";
		if ($result->error === 0) {
			$this->SaveDirectiveOk->Display = 'Dynamic';
			$this->SaveDirectiveError->Display = 'None';
			$this->SaveDirectiveErrMsg->Text = '';
			if ($this->getComponentType() == 'dir') {
				$this->getModule('api')->set(['console'], ['reload']);
			}
			$action = $load_values && !$this->getCopyMode() ? 'Save Bacula config resource.' : 'Create Bacula config resource.';
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		} else {
			$this->SaveDirectiveOk->Display = 'None';
			$this->SaveDirectiveError->Display = 'Dynamic';
			$this->SaveDirectiveErrMsg->Display = 'Dynamic';
			$this->SaveDirectiveErrMsg->Text = "Error {$result->error}: {$result->output}";
			$action = $load_values && !$this->getCopyMode() ? 'Problem with saving Bacula config resource.' : 'Problem with creating Bacula config resource.';
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		}
		$this->onSave(null);
	}

	public function setShowAllDirectives($show_all_directives)
	{
		$this->DirectiveSetting->AllDirectives->Checked = $show_all_directives;
		$this->setViewState(self::SHOW_ALL_DIRECTIVES, $show_all_directives);
	}

	public function getShowAllDirectives()
	{
		return $this->getViewState(self::SHOW_ALL_DIRECTIVES, false);
	}

	public function getSaveDirectiveActionOK()
	{
		return $this->getViewState(self::SAVE_DIRECTIVE_ACTION_OK, '');
	}

	public function setSaveDirectiveActionOK($action_ok)
	{
		$this->setViewState(self::SAVE_DIRECTIVE_ACTION_OK, $action_ok);
	}

	public function getCancelDirectiveActionOK()
	{
		return $this->getViewState(self::CANCEL_DIRECTIVE_ACTION_OK, '');
	}

	public function setCancelDirectiveActionOK($action_ok)
	{
		$this->setViewState(self::CANCEL_DIRECTIVE_ACTION_OK, $action_ok);
	}

	/**
	 * Show or hide loader.
	 *
	 * @param bool $show if true, loader is displayed, if false it is hidden
	 */
	public function showLoader($show)
	{
		$cbc = $this->getPage()->getCallbackClient();
		$lid = 'bcd_loader_' . $this->ClientID;
		if ($show) {
			$cbc->show($lid);
		} else {
			$cbc->hide($lid);
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
		$component_type = $this->getComponentType();
		if (empty($_SESSION[$component_type])) {
			return;
		}
		$host = null;
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$config = $this->getConfigData($host, [$component_type]);
		$deps = $this->getModule('data_deps')->checkDependencies(
			$component_type,
			$resource_type,
			$resource_name,
			$config
		);
		if (count($deps) === 0) {
			// NO DEPENDENCY. Ready to remove.
			$this->removeResourceFromConfig(
				$config,
				$resource_type,
				$resource_name
			);
			$result = $this->getModule('api')->set(
				['config',	$component_type],
				['config' => json_encode($config)],
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
				$this->showRemovedResourceError($result->output);
				$this->getModule('audit')->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_CONFIG,
					sprintf($amsg, 'Problem with removing Bacula config resource.')
				);
			}
		} else {
			// DEPENDENCIES EXIST. List them on the interface.
			$this->showDependenciesError(
				$deps,
				$resource_type,
				$resource_name
			);
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
		$cc = $this->getPage()->getCallbackClient();
		$cc->callClientFunction('show_info', [
			$msg,
			null,
			true
		]);
		$cc->slideUp($this->ConfigDirectives);
	}

	/**
	 * Show removed resource error message.
	 *
	 * @param string $error_message error message
	 */
	private function showRemovedResourceError($error_message)
	{
		$this->getPage()->getCallbackClient()->callClientFunction('show_error', [
			$error_message
		]);
	}

	/**
	 * Show dependencies error message.
	 *
	 * @param array $deps list dependencies for the removing resource
	 * @param string $resource_type resource type of the removing resource
	 * @param string $resource_name resource name of the removing resource
	 */
	private function showDependenciesError($deps, $resource_type, $resource_name)
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
		$error_message = implode('<br /><br />', $error);
		$this->showRemovedResourceError($error_message);
	}

	/**
	 * Remove resource from config.
	 * Note, passing config by reference.
	 *
	 * @param array $config entire config
	 * @param string $resource_type resource type to remove
	 * @param string $resource_name resource name to remove
	 */
	private function removeResourceFromConfig(&$config, $resource_type, $resource_name)
	{
		for ($i = 0; $i < count($config); $i++) {
			foreach ($config[$i] as $rtype => $resource) {
				if (!property_exists($resource, 'Name')) {
					continue;
				}
				if ($rtype === $resource_type && $resource->Name === $resource_name) {
					// remove resource
					array_splice($config, $i, 1);
					break;
				}
			}
		}
	}

	/**
	 * Rename resource.
	 * This rename method takes into account resource dependencies
	 * and updates them as well.
	 *
	 * @param string $new_resource_name new resource name to set
	 * @param mixed $resource_name_new
	 * @return bool true on success, false on failure
	 */
	public function renameResource($resource_name_new)
	{
		$success = true;
		$component_type = $this->getComponentType();
		if (empty($_SESSION[$component_type])) {
			return false;
		}
		$host = null;
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();

		if ($resource_type == 'Pool') {
			/**
			 * For Pools there is done copy resource, not rename, to allow users
			 * re-assigning volumes from original pool to new pool and at the end
			 * to remove original pool.
			 */
			return true;
		}

		$config = $this->getConfigData($host, [$component_type]);
		$deps = $this->getModule('data_deps')->checkDependencies(
			$component_type,
			$resource_type,
			$resource_name,
			$config
		);
		$this->renameResourceInConfig(
			$config,
			$deps,
			$resource_type,
			$resource_name,
			$resource_name_new
		);
		$result = $this->getModule('api')->set(
			['config',	$component_type],
			['config' => json_encode($config)],
			$host,
			false
		);
		$component_full_name = $this->getModule('misc')->getComponentFullName($component_type);
		if ($result->error == 0) {
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				"Rename Bacula config resource. Component: {$component_full_name}, Resource: {$resource_type}, Name: {$resource_name} => {$resource_name_new}"
			);
		} else {
			$success = false;
			$emsg = 'Error while renaming resource: ' . $result->output;
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				"Problem with renaming Bacula config resource. Component: {$component_full_name}, Resource: {$resource_type}, Name: {$resource_name}"
			);
		}
		return $success;
	}

	/**
	 * Rename resource in config.
	 * Note, passing config by reference.
	 *
	 * @param array $config entire config
	 * @param string $resource_type resource type to rename
	 * @param string $resource_name resource name to rename
	 * @param string $resource_name_new new resource name to set
	 * @param mixed $deps
	 */
	private function renameResourceInConfig(&$config, $deps, $resource_type, $resource_name, $resource_name_new)
	{
		for ($i = 0; $i < count($config); $i++) {
			foreach ($config[$i] as $rtype => $resource) {
				for ($j = 0; $j < count($deps); $j++) {
					if ($rtype === $deps[$j]['resource_type'] && $resource->Name === $deps[$j]['resource_name']) {
						// Change resource name in dependent resources
						$config[$i]->{$rtype}->{$deps[$j]['directive_name']} = $resource_name_new;
					}
				}
				if ($rtype === $resource_type && $resource->Name === $resource_name) {
					// Change resource name
					$config[$i]->{$rtype}->Name = $resource_name_new;
				}
			}
		}
	}

	/**
	 * Set if remove button should be available.
	 *
	 * @param mixed $show
	 */
	public function setShowRemoveButton($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_REMOVE_BUTTON, $show);
	}

	/**
	 * Get if remove button should be available.
	 *
	 * @return bool true if available, otherwise false
	 */
	public function getShowRemoveButton()
	{
		return $this->getViewState(self::SHOW_REMOVE_BUTTON, true);
	}

	/**
	 * Set if cancel button should be available.
	 *
	 * @param mixed $show
	 */
	public function setShowCancelButton($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_CANCEL_BUTTON, $show);
	}

	/**
	 * Get if cancel button should be available.
	 *
	 * @return bool true if available, otherwise false
	 */
	public function getShowCancelButton()
	{
		return $this->getViewState(self::SHOW_CANCEL_BUTTON, true);
	}

	/**
	 * Set if buttons should be flexible and available at the bottom of the page.
	 *
	 * @param mixed $show
	 */
	public function setShowBottomButtons($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_BOTTOM_BUTTONS, $show);
	}

	/**
	 * Get if buttons should be flexible and available at the bottom of the page.
	 *
	 * @return bool true if buttons are available at the bottom of the page, otherwise false
	 */
	public function getShowBottomButtons()
	{
		return $this->getViewState(self::SHOW_BOTTOM_BUTTONS, true);
	}

	/**
	 * Set if config section tabs should be used.
	 *
	 * @param mixed $show
	 */
	public function setShowSectionTabs($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_SECTION_TABS, $show);
	}

	/**
	 * Get if config section tabs should be used.
	 *
	 * @return bool true if tabs are used,otherwise false
	 */
	public function getShowSectionTabs()
	{
		return $this->getViewState(self::SHOW_SECTION_TABS, false);
	}

	/**
	 * On save event fired when resource is saved.
	 *
	 * @param mixed $param
	 */
	public function onSave($param)
	{
		$this->raiseEvent('OnSave', $this, $param);
	}

	/**
	 * On rename event fired when resource is renamed.
	 *
	 * @param mixed $param
	 */
	public function onRename($param)
	{
		$this->raiseEvent('OnRename', $this, $param);
	}


	/**
	 * Set if name field should be disabled.
	 *
	 * @param mixed $rename
	 */
	public function setDisableRename($rename)
	{
		$rename = TPropertyValue::ensureBoolean($rename);
		$this->setViewState(self::DISABLE_RENAME, $rename);
	}

	/**
	 * Get if name field should be disabled.
	 *
	 * @return bool true if field is disabled, otherwise false
	 */
	public function getDisableRename()
	{
		return $this->getViewState(self::DISABLE_RENAME, false);
	}
}
