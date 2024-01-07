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
 * Copyright (C) 2013-2020 Kern Sibbald
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
 * Bulk actions modal control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BulkActionsModal extends Portlets
{
	public const REFRESH_PAGE_BTN = 'RefreshPageBtn';

	public function onLoad($param)
	{
		parent::onLoad($param);

		if ($this->getRefreshPageBtn()) {
			$this->getPage()->getCallbackClient()->show('bulk_actions_refresh_page');
		}
	}

	public function setRefreshPageBtn($refresh_page_btn)
	{
		$refresh_page_btn = TPropertyValue::ensureBoolean($refresh_page_btn);
		$this->setViewState(self::REFRESH_PAGE_BTN, $refresh_page_btn);
	}

	public function getRefreshPageBtn()
	{
		return $this->getViewState(self::REFRESH_PAGE_BTN);
	}
}
