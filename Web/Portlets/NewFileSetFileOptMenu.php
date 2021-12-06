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

Prado::using('Application.Web.Portlets.DirectiveListTemplate');

/**
 * New FileSet file options menu.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class NewFileSetFileOptMenu extends DirectiveListTemplate {

	const ITEM_INDEX = 'ItemIndex';
	const FS_BROWSER_ID = 'FsBrowserId';

	public function setItemIndex($index) {
		$this->setViewState(self::ITEM_INDEX, $index);
	}

	public function getItemIndex() {
		return $this->getViewState(self::ITEM_INDEX);
	}

	public function setFileSetBrowserId($id) {
		$this->setViewState(self::FS_BROWSER_ID, $id);
	}

	public function getFileSetBrowserId() {
		return $this->getViewState(self::FS_BROWSER_ID);
	}
}
?>
