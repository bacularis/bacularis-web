<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

use Bacularis\Web\Modules\WebUserRoles;

/**
 * Bulk apply patterns modal control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BulkApplyPatternsModal extends Portlets
{
	public const HOST = 'Host';
	public const COMPONENT_TYPE = 'ComponentType';

	public function onInit($param)
	{
		parent::onInit($param);
		$this->Visible = $this->User->isInRole(WebUserRoles::ADMIN);
	}

	public function setPatternsWindow($sender, $param)
	{
		$param = $param->getCallbackParameter();
		[$host, $component_type] = $param;
		$this->setHost($host);
		$this->setComponentType($component_type);
	}

	public function loadPatterns($sender, $param)
	{
		$patterns = $this->getModule('pattern_config')->getConfig();
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$pattern_vals = array_values($patterns);
		$pattern_vals = array_filter($pattern_vals, fn ($item) => $item['component'] == $component_type);
		$names = array_map(fn ($item) => $item['name'], $pattern_vals);
		$this->Patterns->DataSource = array_combine($names, $names);
		$this->Patterns->dataBind();

		$cb = $this->getPage()->getCallbackClient();
		$component_type_full = $this->getModule('misc')->getComponentFullName($component_type);
		$cb->callClientFunction(
			'oBulkApplyPatternsModal.update_component',
			[$component_type_full]
		);
		$cb->callClientFunction(
			'oBulkApplyPatternsModal.update_host',
			[$host]
		);
	}

	public function applyPatterns($sender, $param)
	{
		$param = $param->getCallbackParameter();
		if (empty($param) || !is_object($param) || !isset($param->simulate)) {
			return;
		}
		$simulate = $param->simulate;

		// Get selected patterns setting
		$pattern_config = $this->getModule('pattern_config');
		$pattern_list = $this->Patterns->getSelectedIndices();
		$patterns = [];
		foreach ($pattern_list as $indice) {
			for ($i = 0; $i < $this->Patterns->getItemCount(); $i++) {
				if ($i === $indice) {
					$pattern = $this->Patterns->Items[$i]->Value;
					$patterns[] = $pattern_config->getPatternConfig($pattern, true);
					break;
				}
			}
		}

		// Get component configuration
		$api = $this->getModule('api');
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$params = [
			'config',
			$component_type
		];
		$result = $api->get($params, $host);
		$cb = $this->getPage()->getCallbackClient();
		if ($result->error !== 0) {
			$cb->callClientFunction(
				'oBulkApplyPatternsModal.update_log_status',
				[$host, false, $result->output, '', $simulate]
			);
			return;
		}

		// Apply configs to selected resource
		$conf_config = $this->getModule('conf_config');
		$new_config = json_decode(json_encode($result->output), true);
		for ($i = 0; $i < count($patterns); $i++) {
			for ($j = 0; $j < count($patterns[$i]['configs']); $j++) {
				$config = $conf_config->getConfConfig($patterns[$i]['configs'][$j]);
				if (!key_exists('Name', $config['config'])) {
					/**
					 * Config does not have name, so it cannot be added as a new resource.
					 * and it can be only applied to existing resources. Skip it.
					 */
					continue;
				}
				/**
				 * Config has a name, so it can be added as a new resource
				 * or it can be applied to existing resources with the same name.
				 */
				$res_exists = false;
				for ($k = 0; $k < count($new_config); $k++) {
					if (!key_exists($config['resource'], $new_config[$k])) {
						// different resource, skip it
						continue;
					}
					if ($config['config']['Name'] === $new_config[$k][$config['resource']]['Name']) {
						// Resource exists in configuration, so update it
						$res_exists = true;
						$this->applyPatternConfig($config['config'], $new_config[$k][$config['resource']]);
						break;
					}
				}
				if (!$res_exists) {
					// Resource does not exists in configuration, add it as a new
					$new_config[] = [$config['resource'] => $config['config']];
				}
			}
		}

		$query = [];
		if ($simulate) {
			$query['mode'] = 'simulate';
		}
		$query_string = '';
		if (count($query) > 0) {
			$query_string = '?' . http_build_query($query);
		}
		$params = [
			'config',
			$component_type,
			$query_string
		];
		$conf = [
			'config' => json_encode($new_config)
		];
		$result = $api->set(
			$params,
			$conf,
			$host
		);
		if ($result->error === 0) {
			if (!$simulate) {
				$api->set(['console'], ['reload']);
			}
			$cb->callClientFunction(
				'oBulkApplyPatternsModal.update_log_status',
				[$host, true, '', $result->output, $simulate]
			);
		} else {
			$cb->callClientFunction(
				'oBulkApplyPatternsModal.update_log_status',
				[$host, false, $result->output, '', $simulate]
			);
			return;
		}
	}

	private function applyPatternConfig($config, &$resource)
	{
		foreach ($config as $directive => $value) {
			if (key_exists($directive, $resource)) {
				// Directive exists in original config resource
				if ($this->OverwritePolicyExisting->Checked) {
					$resource[$directive] = $value;
				} elseif ($this->OverwritePolicyAddNew->Checked) {
					if (is_array($value)) {
						$resource[$directive] = array_merge($resource[$directive], $value);
					}
				}
			} else {
				// Directive does not exist in original config
				$resource[$directive] = $value;
			}
		}
	}

	/**
	 * Get host.
	 *
	 * @return string host
	 */
	public function getHost(): ?string
	{
		return $this->getViewState(self::HOST);
	}

	/**
	 * Set host.
	 *
	 * @param string $host host
	 */
	public function setHost(?string $host): void
	{
		$this->PatternAPIHost->Value = $host;
		$this->setViewState(self::HOST, $host);
	}

	/**
	 * Get component type.
	 *
	 * @return string component type
	 */
	public function getComponentType(): string
	{
		return $this->getViewState(self::COMPONENT_TYPE, '');
	}

	/**
	 * Set component type.
	 *
	 * @param string $type component type
	 */
	public function setComponentType(string $type): void
	{
		$this->setViewState(self::COMPONENT_TYPE, $type);
	}
}
