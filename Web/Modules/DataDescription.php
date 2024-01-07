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

namespace Bacularis\Web\Modules;

use Prado\Prado;

/**
 * Data description module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class DataDescription extends WebModule
{
	/**
	 * Data description file
	 */
	public const DATA_DESC_FILE = 'Bacularis.Web.Data.data_desc';

	private static $data_desc;

	private function getDataDesc()
	{
		if (is_null(self::$data_desc)) {
			self::$data_desc = $this->loadDataDescription();
		}
		return self::$data_desc;
	}

	private function loadDataDescription()
	{
		$data_desc = null;
		$desc_file = Prado::getPathOfNamespace(self::DATA_DESC_FILE, '.json');
		if (file_exists($desc_file) && is_readable($desc_file)) {
			$desc_file = file_get_contents($desc_file);
			$data_desc = json_decode($desc_file);
		}
		return $data_desc;
	}

	public function getDescription($component_type, $resource_type, $directive_name = null)
	{
		$desc = null;
		$data_desc = $this->getDataDesc();
		if (!is_null($directive_name) && isset($data_desc->{$component_type}->{$resource_type}->{$directive_name})) {
			$desc = $data_desc->{$component_type}->{$resource_type}->{$directive_name};
		} elseif (isset($data_desc->{$component_type}->{$resource_type})) {
			$desc = $this->prepareDirectivesBySection($data_desc->{$component_type}->{$resource_type});
		}
		return $desc;
	}

	public function prepareDirectivesBySection($desc)
	{
		$desc_by_sect = [];
		$desc_sects = ['General' => []];
		foreach ($desc as $directive_name => $directive_desc) {
			if (property_exists($directive_desc, 'Section')) {
				if (!key_exists($directive_desc->Section, $desc_sects)) {
					$desc_sects[$directive_desc->Section] = [];
				}
			} else {
				$directive_desc->Section = 'General';
			}
			$desc_sects[$directive_desc->Section][] = [
				'name' => $directive_name,
				'desc' => $directive_desc
			];
		}
		foreach ($desc_sects as $sect => $directives) {
			for ($i = 0; $i < count($directives); $i++) {
				$desc_by_sect[$directives[$i]['name']] = $directives[$i]['desc'];
			}
		}
		return $desc_by_sect;
	}
}
