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
 * FileSet list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class FileSetList extends BaculumWebPage {

	const USE_CACHE = true;

	public $filesets = array();

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$result = $this->getModule('api')->get(array('filesets', 'resnames'), null, true, self::USE_CACHE);
		if ($result->error === 0) {
			$filesets = array();
			foreach ($result->output as $director => $fileset) {
				for ($i = 0; $i < count($fileset); $i++) {
					$filesets[] = array('director' => $director, 'fileset' => $fileset[$i]);
				}
			}
			$this->filesets = $filesets;
		}
	}
}
?>
