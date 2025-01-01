<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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

namespace Bacularis\Web\Portlets;

use Prado\TPropertyValue;

/**
 * Directive settings control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveSetting extends DirectiveListTemplate
{
	public function onLoadDirectives($param)
	{
		$this->raiseEvent('OnLoadDirectives', $this, $param);
	}

	public function setOption($sender, $param)
	{
		switch ($param->CallbackParameter) {
			case 'show_all_directives': {
				$this->onLoadDirectives(null);
				break;
			}
			case 'show_raw_config': {
				break;
			}
			case 'save_multiple_hosts': {
				break;
			}
			case 'save_addition_path': {
				break;
			}
			case 'download_resource_config': {
				break;
			}
		}
	}

	public function showOptions($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->DirectiveOptions->Display = $show ? 'Dynamic' : 'None';
	}
}
