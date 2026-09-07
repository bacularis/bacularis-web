<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis web interface is Marcin Haba.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero
 * General Public License, v3.0 ("AGPLv3") and some additional
 * permissions and terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\Web\Portlets;

use Bacularis\Web\Modules\TagConfig;

/**
 * Base control for tag-related portlets.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class TagBase extends Portlets
{
	/**
	 * Convert callback data to an array after validating its JSON representation.
	 *
	 * @param mixed $parameter callback parameter
	 * @return array|null decoded callback data or null if it is not a JSON object
	 */
	protected function getCallbackData($parameter): ?array
	{
		if (is_string($parameter)) {
			$json = $parameter;
		} else {
			$json = json_encode($parameter);
			if ($json === false) {
				return null;
			}
		}
		$data = json_decode($json, true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
			return null;
		}
		return $data;
	}

	/**
	 * Check whether a tag name uses the allowed tag format.
	 *
	 * @param mixed $tag tag name
	 * @return bool true if the tag name is valid
	 */
	protected function isValidTagName($tag): bool
	{
		return is_string($tag) && preg_match('/^' . TagConfig::TAG_PATTERN . '$/', $tag) === 1;
	}

	/**
	 * Check tag properties received from the client.
	 *
	 * @param array|null $data tag data
	 * @param bool $require_access whether accessibility must be present
	 * @return bool true if all tag properties are valid
	 */
	protected function isValidTagData(?array $data, bool $require_access = false): bool
	{
		if ($data == null) {
			return false;
		}

		$is_tag = (key_exists('tag', $data) && $this->isValidTagName($data['tag']));
		$is_color = (key_exists('color', $data) && key_exists($data['color'], TagConfig::TAG_COLORS));
		$is_severity = (key_exists('severity', $data) && key_exists($data['severity'], TagConfig::TAG_SEVERITY));

		if (!$is_tag || !$is_color || !$is_severity) {
			return false;
		}
		$access = $data['access'] ?? null;
		return !$require_access || in_array($access, [TagConfig::ACCESSIBILITY_LOCAL, TagConfig::ACCESSIBILITY_GLOBAL], true);
	}
}
