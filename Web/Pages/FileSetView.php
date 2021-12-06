<?php
/*
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

Prado::using('Application.Web.Class.BaculumWebPage'); 

/**
 * FileSet view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class FileSetView extends BaculumWebPage {

	const USE_CACHE = true;

	const FILESET_NAME = 'FileSetName';

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		if ($this->Request->contains('fileset')) {
			$this->setFileSetName($this->Request['fileset']);
		}
	}

	public function onPreRender($param) {
		parent::onPreRender($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		if (!empty($_SESSION['dir'])) {
			$this->FileSetConfig->setComponentName($_SESSION['dir']);
			$this->FileSetConfig->setResourceName($this->getFileSetName());
			$this->FileSetConfig->setLoadValues(true);
			$this->FileSetConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Set fileset name.
	 *
	 * @return none;
	 */
	public function setFileSetName($fileset_name) {
		$this->setViewState(self::FILESET_NAME, $fileset_name);
	}

	/**
	 * Get fileset name.
	 *
	 * @return string fileset name
	 */
	public function getFileSetName() {
		return $this->getViewState(self::FILESET_NAME);
	}
}
?>
