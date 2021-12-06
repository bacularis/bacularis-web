<?php
/*
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2021 Kern Sibbald
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

Prado::using('Application.Web.Class.WebModule');

/**
 * Directive documentation module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum Web
 */
class DirectiveDoc extends WebModule {

	/**
	 * Directive documentation file path.
	 */
	const DOC_PATH = 'Application.Web.Data.dir_docs';

	/**
	 * Documentation file extension.
	 */
	const DOC_EXT = '.html';

	private static $dom = null;

	/**
	 * Get directive documentation.
	 *
	 * @param string $component_type component type ('dir', 'sd, 'fd', 'bcons')
	 * @param string $resource_type resource type ('Job', 'Device' ... )
	 * @param string $directive_name directive name
	 * @return string directive HTML documentation or empty string if doc is not available
	 */
	public function getDoc($component_type, $resource_type, $directive_name) {
		$component = '';
		$misc = $this->Application->getModule('misc');
		if ($component_type != 'bcons') {
			$component = $misc->getMainComponentResource($component_type);
		} else {
			$component = $misc->getComponentFullName($component_type);
		}
		if ($resource_type == 'JobDefs') {
			$resource_type = 'Job';
		}

		$doc = '';
		$doc_file = Prado::getPathOfNamespace(self::DOC_PATH, self::DOC_EXT);
		if (is_null(self::$dom) && file_exists($doc_file)) {
			$dom = new DOMDocument();
			$dom->loadHTMLFile($doc_file);
			self::$dom = $dom;
		}
		if (self::$dom instanceof DOMDocument) {
			$id = "{$component}_{$resource_type}_{$directive_name}";
			$element = self::$dom->getElementById($id);
			if ($element) {
				$doc = $element->ownerDocument->saveHTML($element);
			}
		}
		return $doc;
	}
}
?>
