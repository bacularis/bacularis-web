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

use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\TagConfig;
use Bacularis\Web\Modules\WebUserConfig;

/**
 * Tag manager control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class TagManager extends Portlets
{
	private const USERNAME = 'Username';

	public $palette = [];

	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->setSeverityList();
		$this->setColorPalette();
	}

	/**
	 * Load tag list to display in table.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadTagList($sender, $param): void
	{
		[$org_id, $user_id] = $this->getUsername();
		$tag_config = $this->getModule('tag_config');
		$tags = $tag_config->getTagConfig($org_id, $user_id);
		$tag_names = array_keys($tags);
		$tag_values = array_values($tags);
		$tags = array_map(fn ($tag, $val) => array_merge($val, ['tag' => $tag]), $tag_names, $tag_values);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oTagManagerList.update',
			[$tags]
		);
	}

	/**
	 * Set tag color palette.
	 */
	private function setColorPalette(): void
	{
		$this->palette = TagConfig::TAG_COLORS;

		$this->TagColors->DataSource = array_values(TagConfig::TAG_COLORS);
		$this->TagColors->dataBind();
	}

	/**
	 * Set tag severity list.
	 */
	private function setSeverityList(): void
	{
		$this->TagSeverity->DataSource = array_values(TagConfig::TAG_SEVERITY);
		$this->TagSeverity->dataBind();
	}

	/**
	 * Load edit tag window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function editTag($sender, $param): void
	{
		$tag = $param->getCallbackParameter();
		[$org_id, $user_id] = $this->getUsername();
		$tag_config = $this->getModule('tag_config');
		$tag_props = $tag_config->getTagConfig($org_id, $user_id, $tag);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oTagManagerAction.edit_cb',
			[$tag_props]
		);
	}

	/**
	 * Save tag.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveTag($sender, $param): void
	{
		[
			'tag' => $tag,
			'color' => $color,
			'severity' => $severity
		] = (array) $param->getCallbackParameter();
		$tag_vals = [
			$tag => [
				'color' => $color,
				'severity' => $severity
			]
		];
		[$org_id, $user_id] = $this->getUsername();
		$tag_config = $this->getModule('tag_config');
		$result = $tag_config->setTagConfig($org_id, $user_id, $tag_vals);

		if ($result) {
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oTagManagerAction.save_cb'
			);

			// refresh tag list
			$this->loadTagList($sender, $param);
		} else {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while editing tag '{$tag}' for user '{$username}'."
			);
		}
	}

	/**
	 * Delete tag.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function deleteTag($sender, $param): void
	{
		$tag = $param->getCallbackParameter();
		[$org_id, $user_id] = $this->getUsername();
		$tag_config = $this->getModule('tag_config');

		// First remove all tag assignments
		$tag_assign_config = $this->getModule('tag_assign_config');
		$result = $tag_assign_config->removeAllTagAssignsConfig(
			$org_id,
			$user_id,
			$tag
		);

		if ($result) {
			$result = $tag_config->removeTagConfig(
				$org_id,
				$user_id,
				$tag
			);
			if ($result) {
				$audit = $this->getModule('audit');
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Tag '{$tag}' has been removed."
				);

				// refresh tag list
				$this->loadTagList($sender, $param);
			} else {
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					"Error while removing tag '{$tag}' for organization '{$org_id}' user '{$user_id}'."
				);
			}
		} else {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while unassign all tag assignments for tag '{$tag}' for organization '{$org_id}' and user '{$user_id}'."
			);
		}
	}

	/**
	 * Set username.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param string $name view name
	 */
	public function setUsername(string $org_id, string $user_id): void
	{
		$this->setViewState(self::USERNAME, [$org_id, $user_id]);
	}

	/**
	 * Get username.
	 *
	 * @return string view name
	 */
	public function getUsername(): array
	{
		return $this->getViewState(self::USERNAME, ['', '']);
	}
}
