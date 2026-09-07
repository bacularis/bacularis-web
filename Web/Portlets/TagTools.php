<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\TagAssignConfig;
use Bacularis\Web\Modules\TagConfig;
use Bacularis\Web\Modules\WebUserConfig;

/**
 * Tag tools control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class TagTools extends TagBase
{
	private const DATA_VIEW_NAME = 'DataViewName';

	private const ACTIONS = ['CreateTag', 'AssignTag', 'UnassignTag'];

	public $tags = [];
	public $tag_assign = [];
	public $palette = [];

	public $enable_global_tags;

	public function onLoad($param)
	{
		parent::onLoad($param);

		if ($this->getPage()->IsCallBack) {
			$cbet = $this->getPage()->getCallbackEventTarget()->ID ?? '';
			if (!in_array($cbet, self::ACTIONS)) {
				return;
			}
		}

		$wc = $this->getPage()->web_config['baculum'] ?? [];
		if (key_exists('enable_global_tags', $wc)) {
			$this->enable_global_tags = ($wc['enable_global_tags'] == 1);
		} else {
			$this->enable_global_tags = TagConfig::DEF_GLOBAL_TAG_ENABLED;
		}

		$this->setTagList();
		$this->setSeverityList();
		$this->setColorPalette();
		$this->setTagAssignList();
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
	 * Set existing tag list.
	 */
	private function setTagList(): void
	{
		$tag_config = $this->getModule('tag_config');
		$org_id = $this->getPage()->User->getOrganization();
		$user_id = $this->getPage()->User->getUsername();
		$tags = $tag_config->getTagConfig(
			$org_id,
			$user_id,
			'',
			$this->enable_global_tags
		);
		$tag_list = [];
		foreach ($tags as $tag => $vals) {
			$vals = is_array($vals) ? $vals : [];
			$tag_data = [
				'tag' => $tag,
				'color' => $vals['color'] ?? null,
				'severity' => $vals['severity'] ?? null,
				'access' => $vals['access'] ?? null
			];
			if ($this->isValidTagData($tag_data, true)) {
				$tag_list[$tag] = $tag_data;
			}
		}
		ksort($tag_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->tags = $tag_list;

		if ($this->getPage()->IsCallback) {
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oTagTools_' . $this->ClientID . '.update_tags',
				[$this->tags]
			);
		}

		$this->TagList->DataSource = array_values($tag_list);
		$this->TagList->dataBind();
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
	 * Set tag assign list.
	 */
	private function setTagAssignList(): void
	{
		$tag_assign_config = $this->getModule('tag_assign_config');
		$org_id = $this->getPage()->User->getOrganization();
		$user_id = $this->getPage()->User->getUsername();
		$view = $this->getViewName();
		$tag_assign = $tag_assign_config->getTagAssignConfig(
			$org_id,
			$user_id,
			$view,
			$this->enable_global_tags
		);
		$this->tag_assign = ($tag_assign[$view] ?? []);
		foreach ($this->tag_assign as $key => $assignment) {
			if (!is_array($assignment) || !is_array($assignment['tag'] ?? null)) {
				unset($this->tag_assign[$key]);
				continue;
			}
			$this->tag_assign[$key]['tag'] = array_values(array_filter(
				$assignment['tag'],
				function ($tag): bool {
					return is_string($tag) && key_exists($tag, $this->tags);
				}
			));
		}

		if ($this->getPage()->IsCallback) {
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oTagTools_' . $this->ClientID . '.update_tag_assign',
				[$this->tag_assign]
			);
		}
	}

	/**
	 * Create a new tag.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback param
	 */
	public function createTag($sender, $param): void
	{
		$data = $this->getCallbackData($param->getCallbackParameter());
		if (!$this->isValidTagData($data, true)) {
			return;
		}
		$tag = $data['tag'];
		$color = $data['color'];
		$severity = $data['severity'];
		$access = $data['access'];
		$org_id = $this->getPage()->User->getOrganization();
		$user_id = $this->getPage()->User->getUsername();
		$tag_config = $this->getModule('tag_config');
		$props = [
			'color' => $color,
			'severity' => $severity
		];
		$tag_props = [
			$tag => $props
		];
		$result = false;
		if ($access === TagConfig::ACCESSIBILITY_LOCAL) {
			$result = $tag_config->setTagConfig(
				$org_id,
				$user_id,
				$tag_props
			);
		} elseif ($access === TagConfig::ACCESSIBILITY_GLOBAL && $this->enable_global_tags) {
			$result = $tag_config->setGlobalTagConfig(
				$tag_props
			);
		}
		if ($result) {
			$laccess = ucfirst($access);
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"$laccess tag '{$tag}' has been created."
			);

			//refresh tag list
			$this->setTagList();
		} else {
			$out = var_export($tag, true);
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while creating a tag '{$out}' for organization '{$org_id}' user '{$user_id}'."
			);
		}
	}

	/**
	 * Assign tag(s) to element.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback param
	 */
	public function assignTag($sender, $param): void
	{
		$data = $this->getCallbackData($param->getCallbackParameter());
		if ($data === null || !is_array($data['tags'] ?? null)
			|| !is_string($data['id'] ?? null) || !is_string($data['value'] ?? null)) {
			return;
		}
		$tags = $data['tags'];
		$id = $data['id'];
		$value = $data['value'];
		$tag_names = [];
		foreach ($tags as $tag_data) {
			if (!is_array($tag_data) || !$this->isValidTagData($tag_data, true)
				|| !key_exists($tag_data['tag'], $this->tags)) {
				return;
			}
			$tag_names[] = $tag_data['tag'];
		}
		$tag_assign_config = $this->getModule('tag_assign_config');
		$org_id = $this->getPage()->User->getOrganization();
		$user_id = $this->getPage()->User->getUsername();
		$view = $this->getViewName();
		$result = true;
		$tout = [];
		$key = "{$id}_{$value}";
		for ($i = 0; $i < count($tag_names); $i++) {
			$tag_name = $tag_names[$i];
			$tag_data = $this->tags[$tag_name];
			[$oid, $uid] = ($tag_data['access'] == TagConfig::ACCESSIBILITY_GLOBAL ? ['', TagAssignConfig::GLOBAL_SECTION] : [$org_id, $user_id]);
			$result = $tag_assign_config->setTagAssignConfig(
				$oid,
				$uid,
				$view,
				$key,
				$tag_name
			);
			if (!$result) {
				$tout = [$tag_data, $id, $value];
				break;
			}
		}

		if ($result) {
			// refresh tag assign list
			$this->setTagAssignList();

			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oTagTools_' . $this->ClientID . '.on_assign_success'
			);
		} else {
			$out = var_export($tout, true);
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while assigning a tag '{$out}' for organization '{$org_id}' user '{$user_id}' in view '{$view}'."
			);
		}

		// Remove tags that were assigned to element but now they were unassigned
		$tag_all = $this->tag_assign[$key]['tag'] ?? [];
		$tag_assign = $tag_names;
		$tag_to_rm = array_diff($tag_all, $tag_assign);
		$tag_to_rm = array_values($tag_to_rm);

		for ($i = 0; $i < count($tag_to_rm); $i++) {
			$tag = $this->tags[$tag_to_rm[$i]] ?? '';
			$this->unassignTagInternal($id, $value, $tag);
		}
	}

	/**
	 * Unassign tag from element.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback param
	 */
	public function unassignTag($sender, $param): void
	{
		$data = $this->getCallbackData($param->getCallbackParameter());
		if ($data === null || !is_string($data['id'] ?? null) || !is_string($data['value'] ?? null)
			|| !is_array($data['tag'] ?? null)
			|| !$this->isValidTagData($data['tag'], true)) {
			return;
		}
		$tag = $data['tag'];
		if (!key_exists($tag['tag'], $this->tags)) {
			return;
		}
		$this->unassignTagInternal($data['id'], $data['value'], $this->tags[$tag['tag']]);
	}

	/**
	 * Unassign tag from element (internal).
	 *
	 * @param string $id element identifier
	 * @param string $value element value
	 * @param string $tag tag properties
	 */
	private function unassignTagInternal(string $id, string $value, array $tag): void
	{
		$tag_assign_config = $this->getModule('tag_assign_config');
		$org_id = $this->getPage()->User->getOrganization();
		$user_id = $this->getPage()->User->getUsername();
		$view = $this->getViewName();
		[$oid, $uid] = ($tag['access'] == TagConfig::ACCESSIBILITY_GLOBAL ? ['', TagAssignConfig::GLOBAL_SECTION] : [$org_id, $user_id]);
		$key = "{$id}_{$value}";
		$result = $tag_assign_config->removeTagAssignConfig(
			$oid,
			$uid,
			$view,
			$key,
			$tag['tag']
		);

		if ($result) {
			// refresh tag assign list
			$this->setTagAssignList();

			// unassign post action
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oTagTools_' . $this->ClientID . '.on_unassign_success'
			);
		} else {
			$tout = var_export($tag, true);
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while unassigning a tag '{$tout}' for organization '{$org_id}' user '{$user_id}' in view '{$view}' and element '{$key}'."
			);
		}
	}

	/**
	 * Set data view name.
	 *
	 * @param string $name view name
	 */
	public function setViewName(string $name): void
	{
		$this->setViewState(self::DATA_VIEW_NAME, $name);
	}

	/**
	 * Get data view name.
	 *
	 * @return string view name
	 */
	public function getViewName(): string
	{
		return $this->getViewState(self::DATA_VIEW_NAME, '');
	}

}
