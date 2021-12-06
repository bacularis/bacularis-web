<?php
/*
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

Prado::using('Application.:Web.Class.WebModule');

/**
 * Module responsible for checking and keeping Bacula config dependencies.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum Web
 */
class DataDependencies extends WebModule {

	/**
	 * Data dependencies file
	 */
	const DATA_DEPS_FILE = 'Application.Web.Data.data_deps';

	private static $data_deps = null;

	private function getDataDeps() {
		if (is_null(self::$data_deps)) {
			self::$data_deps = $this->loadDataDependencies();
		}
		return self::$data_deps;
	}

	/**
	 * Load data dependencies file.
	 *
	 * @return mixed object with data dependencies or null if problem with reading data dependencies file
	 */
	private function loadDataDependencies() {
		$data_deps = null;
		$deps_file = Prado::getPathOfNamespace(self::DATA_DEPS_FILE, '.json');
		if (file_exists($deps_file) && is_readable($deps_file)) {
			$deps_file = file_get_contents($deps_file);
			$data_deps = json_decode($deps_file);
		} else {
			$emsg = "Data dependencies file '$deps_file' does not exist or is not readable.";
			$this->Application->getModule('logging')->log(
				__FUNCTION__,
				$emsg,
				Logging::CATEGORY_APPLICATION,
				__FILE__,
				__LINE__
			);
		}
		return $data_deps;
	}

	/**
	 * Get single resource dependencies.
	 *
	 * @param string $component_type component type: 'dir', 'sd, 'fd' or 'bcons'.
	 * @param string $resource_type resource type: Job, Client, Pool...etc.
	 * @return mixed resource dependencies object or null if resource dependencies don't exist
	 */
	private function getDependencies($component_type, $resource_type) {
		$deps = null;
		$data_deps = $this->getDataDeps();
		if (isset($data_deps->{$component_type}->{$resource_type})) {
			$deps = (array)$data_deps->{$component_type}->{$resource_type};
		}
		return $deps;
	}

	/**
	 * Check if resource is used in other resources.
	 * Method to internal use.
	 *
	 * @param object $resource resource to check
	 * @param string $resource_type resource type to check
	 * @param string $resource_name resource name to check
	 * @param string $deps_directive dependent directive name in this resource (from data_deps.json)
	 * @return bool true if resource is used in other resources, othwerise false
	 */
	private function isResourceDependent($resource, $resource_type, $resource_name, $deps_directive) {
		$dependent = false;
		if ($resource_type === 'Schedule' && property_exists($resource, 'Run')) {
			for ($i = 0; $i < count($resource->Run); $i++) {
				if (property_exists($resource->Run[$i], $deps_directive) && $resource->Run[$i]->{$deps_directive} === $resource_name) {
					$dependent = true;
					break;
				}
			}
		} else {
			if (property_exists($resource, $deps_directive)) {
				if (is_array($resource->{$deps_directive})) {
					for ($i = 0; $i < count($resource->{$deps_directive}); $i++) {
						if ($resource->{$deps_directive}[$i] === $resource_name) {
							$dependent = true;
							break;
						}

					}
				} elseif ($resource->{$deps_directive} === $resource_name) {
					$dependent = true;
				}
			}
		}
		return $dependent;
	}

	/**
	 * Check if resource is dependent on other resources.
	 * Get detailed list wit all resources where examined resource is used.
	 *
	 * @param string $component_type component type: 'dir', 'sd, 'fd' or 'bcons'.
	 * @param string $resource_type resource type: Job, Client, Pool...etc.
	 * @param string $resource_name resource name
	 * @param array $config all component configuration to check
	 * @return array array with all resources in which current resource is used or empty array if resource doesn't depend from other resources
	 */
	public function checkDependencies($component_type, $resource_type, $resource_name, $config) {
		$result = array();
		$deps = $this->getDependencies($component_type, $resource_type);
		if (is_array($deps)) {
			foreach ($deps as $rdtype => $directives) {
				for ($i = 0; $i < count($config); $i++) {
					foreach ($config[$i] as $rctype => $resource) {
						if ($rdtype !== $rctype) {
							continue;
						}
						for ($j = 0; $j < count($directives); $j++) {
							if (!$this->isResourceDependent($resource, $rdtype, $resource_name, $directives[$j])) {
								continue;
							}
							$result[] = array(
								'component_type' => $component_type,
								'resource_type' => $rctype,
								'resource_name' => $resource->Name,
								'directive_name' => $directives[$j]
							);
						}
					}
				}
			}
		}
		return $result;
	}
}
?>
