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

Prado::using('System.Web.TUrlMapping');

/**
 * Web URL mapper class.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category URL
 * @package Baculum Web
 */
class WebUrlMapping extends TUrlMappingPattern {

	const SERVICE_ID = 'web';

	public function __construct(BaculumUrlMapping $manager) {
		parent::__construct($manager);
		$this->setServiceID(self::SERVICE_ID);
	}
}
?>
