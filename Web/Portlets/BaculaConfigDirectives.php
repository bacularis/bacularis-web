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

Prado::using('System.Web.UI.TCommandEventParameter');
Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');
Prado::using('System.Web.UI.ActiveControls.TActiveRepeater');
Prado::using('Application.Web.Portlets.DirectiveListTemplate');
Prado::using('Application.Web.Portlets.DirectiveCheckBox');
Prado::using('Application.Web.Portlets.DirectiveComboBox');
Prado::using('Application.Web.Portlets.DirectiveInteger');
Prado::using('Application.Web.Portlets.DirectiveListBox');
Prado::using('Application.Web.Portlets.DirectivePassword');
Prado::using('Application.Web.Portlets.DirectiveSize');
Prado::using('Application.Web.Portlets.DirectiveSpeed');
Prado::using('Application.Web.Portlets.DirectiveTextBox');
Prado::using('Application.Web.Portlets.DirectiveMultiComboBox');
Prado::using('Application.Web.Portlets.DirectiveMultiTextBox');
Prado::using('Application.Web.Portlets.DirectiveTimePeriod');
Prado::using('Application.Web.Portlets.DirectiveRunscript');
Prado::using('Application.Web.Portlets.DirectiveMessages');

/**
 * Bacula config directives control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class BaculaConfigDirectives extends DirectiveListTemplate {

	const SHOW_REMOVE_BUTTON = 'ShowRemoveButton';
	const SHOW_CANCEL_BUTTON = 'ShowCancelButton';
	const SHOW_ALL_DIRECTIVES = 'ShowAllDirectives';
	const SHOW_BOTTOM_BUTTONS = 'ShowBottomButtons';
	const SAVE_DIRECTIVE_ACTION_OK = 'SaveDirectiveActionOk';
	const DISABLE_RENAME = 'DisableRename';

	private $show_all_directives = false;

	public $resource_names = array();

	private $directive_types = array(
		'DirectiveCheckBox',
		'DirectiveComboBox',
		'DirectiveInteger',
		'DirectiveListBox',
		'DirectivePassword',
		'DirectiveTextBox',
		'DirectiveSize',
		'DirectiveSpeed',
		'DirectiveTimePeriod'
	);

	private $directive_list_types = array(
		'DirectiveFileSet',
		'DirectiveSchedule',
		'DirectiveMessages',
		'DirectiveRunscript',
		'DirectiveMultiComboBox',
		'DirectiveMultiTextBox'
	);

	private $field_multple_values = array(
		'ListBox'
	);

	public $display_directives;

	public function onInit($param) {
		parent::onInit($param);
		if (!$this->getPage()->isPostBack && !$this->getPage()->IsCallBack) {
			$this->Cancel->Visible = $this->getShowCancelButton();
		}
	}

	private function getConfigData($host, array $parameters) {
		$default_params = array('config');
		$params = array_merge($default_params, $parameters);
		$result = $this->Application->getModule('api')->get($params, $host, false);
		$config = array();
		if (is_object($result) && $result->error === 0 && (is_object($result->output) || is_array($result->output))) {
			$config = $result->output;
		}
		return $config;
	}

	public function loadConfig() {
		$load_values = $this->getLoadValues();
		if (!$load_values && $this->IsDirectiveCreated) {
			// This control is loaded only once, otherwise fields loose assigned values.
			return;
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
		$directives = array();
		$parent_directives = new StdClass;
		$config = new stdClass;
		$predefined = false;
		if ($load_values === true) {
			$config = $this->getConfigData($host, array(
				$component_type,
				$resource_type,
				$resource_name
			));
			if (empty($component_name) || empty($resource_type) || empty($resource_name)) {
				$this->ConfigDirectives->Display = 'None';
				return;
			}
			if ($resource_type === 'Job' && property_exists($config, 'JobDefs')) {
				$parent_directives = $this->getConfigData($host, array(
					$component_type,
					'JobDefs',
					$config->JobDefs
				));
			}
		} else {
			// Pre-defined config for new resource can be provided in Data property.
			$data = $this->getData();
			if (!empty($data)) {
				$config = $data;
				$predefined = true;
			}
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
					if ($load_values === true && property_exists($parent_directives, $directive_name)) {
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

			if ((!is_array($directive_value) && !is_object($directive_value)) || in_array($field_type, $this->field_multple_values)) {
				$directive_value = array($directive_value);
			}
			if (is_object($directive_value)) {
				$directive_value = (array)$directive_value;
			}

			if ($directive_name === 'Include' || $directive_name === 'Exclude' || $directive_name === 'Runscript' || $directive_name === 'Destinations') {
				// provide all include blocks at once
				$directive_value = array(array(
					$directive_name => $directive_value,
				));
				if (property_exists($config, 'Exclude')) {
					$directive_value[0]['Exclude'] = (array)$config->{'Exclude'};
				}
			}

			if ($resource_type === 'Schedule' && in_array($directive_name, ['Run', 'Connect'])) {
				$directive_value = array($directive_value);
			}

			if ($directive_name === 'Exclude') {
				continue;
			}

			foreach ($directive_value as $key => $value) {
				$directive = array(
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
				);
				array_push($directives, $directive);
			}
		}
		$config = $this->getConfigData($host, array($component_type));
		for ($i = 0; $i < count($config); $i++) {
			$resource_type = $this->getConfigResourceType($config[$i]);
			$resource_name = property_exists($config[$i]->{$resource_type}, 'Name') ? $config[$i]->{$resource_type}->Name : '';
			if (!array_key_exists($resource_type, $this->resource_names)) {
				$this->resource_names[$resource_type] = array();
			}
			array_push($this->resource_names[$resource_type], $resource_name);
		}
		$this->setResourceNames($this->resource_names);
		$this->RepeaterDirectives->DataSource = $directives;
		$this->RepeaterDirectives->dataBind();
		$this->ConfigDirectives->Display = 'Dynamic';
		$this->IsDirectiveCreated = true;
		if ($copy_mode) {
			$this->getPage()->getCallbackClient()->callClientFunction(
				'oBaculaConfigSection.show_sections',
				[true]
			);
		}
	}

	public function loadDirectives($sender, $param) {
		$show_all_directives = !$this->getShowAllDirectives();
		$this->setShowAllDirectives($show_all_directives);
		$this->loadConfig();
		$this->getPage()->getCallbackClient()->callClientFunction(
			'oBaculaConfigSection.show_sections',
			array($show_all_directives)
		);
	}

	public function unloadDirectives() {
		$this->RepeaterDirectives->DataSource = array();
		$this->RepeaterDirectives->dataBind();
	}

	public function saveResource($sender, $param) {
		$directives = array();
		$host = $this->getHost();
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
				if (is_null($directive_value)) {
					// skip not changed values that don't exist in config
					continue;
				}
				if ($this->directive_types[$i] === 'DirectiveCheckBox') {
					settype($default_value, 'bool');
				} elseif ($this->directive_types[$i] === 'DirectiveInteger') {
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
				$directive_value = $controls[$j]->getDirectiveValue();
				if (is_null($directive_value)) {
					continue;
				}
				if ($directive_name === 'Exclude') {
					continue;
				}
				if (!array_key_exists($directive_name, $directives)) {
					$directives[$directive_name] = array();
				}
				if (is_array($directive_value)) {
					if ($this->directive_list_types[$i] === 'DirectiveMessages') {
						$directives = array_merge($directives, $directive_value);
					} elseif ($this->directive_list_types[$i] === 'DirectiveRunscript') {
						if (!isset($directives[$directive_name])) {
							$directives[$directive_name] = array();
						}
						$directives[$directive_name] = array_merge($directives[$directive_name], $directive_value[$directive_name]);
					} elseif ($this->directive_list_types[$i] === 'DirectiveFileSet') {
						if (key_exists('Exclude', $directive_value) && count($directive_value['Exclude']) > 0) {
							$directives['Exclude'] = array($directive_value['Exclude']);
						}
						$directives[$directive_name] = $directive_value[$directive_name];
					} elseif ($this->directive_list_types[$i] === 'DirectiveSchedule') {
						$directives[$directive_name] = $directive_value[$directive_name];
					} elseif ($this->directive_list_types[$i] === 'DirectiveMultiTextBox' || $this->directive_list_types[$i] === 'DirectiveMultiComboBox') {
						if (key_exists($directive_name, $directives)) {
							$directive_value = array_merge($directives[$directive_name], $directive_value);
						}
						$directives[$directive_name] = array_filter($directive_value);
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

		$params = array(
			'config',
			$component_type,
			$resource_type,
			$resource_name
		);
		$result = $this->getModule('api')->set(
			$params,
			array('config' => json_encode($directives)),
			$host,
			false
		);
		if ($result->error === 0) {
			$this->SaveDirectiveOk->Display = 'Dynamic';
			$this->SaveDirectiveError->Display = 'None';
			$this->SaveDirectiveErrMsg->Text = '';
			if ($this->getComponentType() == 'dir') {
				$this->getModule('api')->set(array('console'), array('reload'));
			}
		} else {
			$this->SaveDirectiveOk->Display = 'None';
			$this->SaveDirectiveError->Display = 'Dynamic';
			$this->SaveDirectiveErrMsg->Display = 'Dynamic';
			$this->SaveDirectiveErrMsg->Text = "Error {$result->error}: {$result->output}";
		}
		$this->onSave(null);
	}

	public function setShowAllDirectives($show_all_directives) {
		$this->setViewState(self::SHOW_ALL_DIRECTIVES, $show_all_directives);
	}

	public function getShowAllDirectives() {
		return $this->getViewState(self::SHOW_ALL_DIRECTIVES, false);
	}

	public function getSaveDirectiveActionOK() {
		return $this->getViewState(self::SAVE_DIRECTIVE_ACTION_OK, '');
	}

	public function setSaveDirectiveActionOK($action_ok) {
		$this->setViewState(self::SAVE_DIRECTIVE_ACTION_OK, $action_ok);
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
		$component_type = $this->getComponentType();
		if (empty($_SESSION[$component_type])) {
			return;
		}
		$host = null;
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$config = $this->getConfigData($host, array($component_type));
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
				array('config',	$component_type),
				array('config' => json_encode($config)),
				$host,
				false
			);
			if ($result->error === 0) {
				$this->getModule('api')->set(array('console'), array('reload'));
				$this->showRemovedResourceInfo(
					$resource_type,
					$resource_name
				);
			} else {
				$this->showRemovedResourceError($result->output);
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
	 *
	 * @param array $deps list dependencies for the removing resource
	 * @param string $resource_type resource type of the removing resource
	 * @param string $resource_name resource name of the removing resource
	 * @return none
	 */
	private function showDependenciesError($deps, $resource_type, $resource_name) {
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
	 * @return none
	 */
	private function removeResourceFromConfig(&$config, $resource_type, $resource_name) {
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
	 * @return boolean true on success, false on failure
	 */
	public function renameResource($resource_name_new) {
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

		$config = $this->getConfigData($host, array($component_type));
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
			array('config',	$component_type),
			array('config' => json_encode($config)),
			$host,
			false
		);
		if ($result->error != 0) {
			$success = false;
			$emsg = 'Error while renaming resource: ' . $result->output;
			$this->Application->getModule('logging')->log(
				__FUNCTION__,
				$emsg,
				Logging::CATEGORY_APPLICATION,
				__FILE__,
				__LINE__
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
	 * @return none
	 */
	private function renameResourceInConfig(&$config, $deps, $resource_type, $resource_name, $resource_name_new) {
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
	 * @return none;
	 */
	public function setShowRemoveButton($show) {
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_REMOVE_BUTTON, $show);
	}

	/**
	 * Get if remove button should be available.
	 *
	 * @return bool true if available, otherwise false
	 */
	public function getShowRemoveButton() {
		return $this->getViewState(self::SHOW_REMOVE_BUTTON, true);
	}

	/**
	 * Set if cancel button should be available.
	 *
	 * @return none;
	 */
	public function setShowCancelButton($show) {
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_CANCEL_BUTTON, $show);
	}

	/**
	 * Get if cancel button should be available.
	 *
	 * @return bool true if available, otherwise false
	 */
	public function getShowCancelButton() {
		return $this->getViewState(self::SHOW_CANCEL_BUTTON, true);
	}

	/**
	 * Set if buttons should be flexible and available at the bottom of the page.
	 *
	 * @return none
	 */
	public function setShowBottomButtons($show) {
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_BOTTOM_BUTTONS, $show);
	}

	/**
	 * Get if buttons should be flexible and available at the bottom of the page.
	 *
	 * @return bool true if buttons are available at the bottom of the page, otherwise false
	 */
	public function getShowBottomButtons() {
		return $this->getViewState(self::SHOW_BOTTOM_BUTTONS, true);
	}

	/**
	 * On save event fired when resource is saved.
	 *
	 * @return none
	 */
	public function onSave($param) {
		$this->raiseEvent('OnSave', $this, $param);
	}

	/**
	 * On rename event fired when resource is renamed.
	 *
	 * @return none
	 */
	public function onRename($param) {
		$this->raiseEvent('OnRename', $this, $param);
	}


	/**
	 * Set if name field should be disabled.
	 *
	 * @return none;
	 */
	public function setDisableRename($rename) {
		$rename = TPropertyValue::ensureBoolean($rename);
		$this->setViewState(self::DISABLE_RENAME, $rename);
	}

	/**
	 * Get if name field should be disabled.
	 *
	 * @return bool true if field is disabled, otherwise false
	 */
	public function getDisableRename() {
		return $this->getViewState(self::DISABLE_RENAME, false);
	}
}
?>
