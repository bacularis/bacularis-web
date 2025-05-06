<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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

use Bacularis\Web\Modules\ConstantConfig;
use Bacularis\Web\Modules\VariableConfig;

/**
 * Constant picker control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class ConstantPicker extends Portlets
{
	private const ROOT_HTML_ELEMENT_ID = 'RootHTMLElementId';

	public function loadConstants($sender, $param)
	{
		$constant_config = $this->getModule('constant_config');
		$constants = $constant_config->getConfig();
		$var_config = array_values($constants);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oConstantPicker.load_constants_cb',
			[$var_config]
		);
	}

	public function setRootHTMLElementID($root_id)
	{
		$this->setViewState(self::ROOT_HTML_ELEMENT_ID, $root_id);
	}

	public function getRootHTMLElementID()
	{
		return $this->getViewState(self::ROOT_HTML_ELEMENT_ID);
	}
}
