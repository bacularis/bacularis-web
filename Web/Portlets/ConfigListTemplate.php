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

Prado::using('Application.Web.Portlets.DirectiveControlTemplate');

/**
 * Config list template control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class ConfigListTemplate extends DirectiveControlTemplate {

	public function getChildControl($parent, $type) {
		$child_control = null;
		$controls = $parent->findControlsByType($type, true);
		// Only one config-type child control is expected
		if (count($controls) === 1) {
			$child_control = $controls[0];
		}
		return $child_control;
	}

	public function getConfigResourceType($obj) {
		$obj_vars = get_object_vars($obj);
		$resource_type = key($obj_vars);
		return $resource_type;
	}

	protected function getModule($id) {
		return $this->getApplication()->getModule($id);
	}
}
?>
