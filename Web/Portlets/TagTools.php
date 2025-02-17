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
use Bacularis\Web\Modules\TagAssignConfig;

/**
 * Tag tools control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class TagTools extends Portlets
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

		if ($this->getPage()->IsPostBack || $this->getPage()->IsCallBack) {
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
		$username = $this->getPage()->User->getUsername();
		$tags = $tag_config->getTagConfig(
			$username,
			'',
			$this->enable_global_tags
		);
		$tag_list = [];
		foreach ($tags as $tag => $vals) {
			$tag_list[$tag] = [
				'tag' => $tag,
				'color' => $vals['color'],
				'severity' => $vals['severity'],
				'access' => $vals['access']
			];
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
		$username = $this->getPage()->User->getUsername();
		$view = $this->getViewName();
		$tag_assign = $tag_assign_config->getTagAssignConfig(
			$username,
			$view,
			$this->enable_global_tags
		);
		$this->tag_assign = ($tag_assign[$view] ?? []);

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
		[
			'tag' => $tag,
			'color' => $color,
			'severity' => $severity,
			'access' => $access
		] = (array) $param->getCallbackParameter();
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
			$username = $this->getPage()->User->getUsername();
			$result = $tag_config->setTagConfig(
				$username,
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
				"Error while creating a tag '{$out}' for user '{$username}'."
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
		[
			'tags' => $tags,
			'id' => $id,
			'value' => $value
		] = (array) $param->getCallbackParameter();
		$tag_assign_config = $this->getModule('tag_assign_config');
		$username = $this->getPage()->User->getUsername();
		$view = $this->getViewName();
		$result = true;
		$tout = [];
		$key = "{$id}_{$value}";
		for ($i = 0; $i < count($tags); $i++) {
			$section = ($tags[$i]->access == TagConfig::ACCESSIBILITY_GLOBAL ? TagAssignConfig::GLOBAL_SECTION : $username);
			$result = $tag_assign_config->setTagAssignConfig(
				$section,
				$view,
				$key,
				$tags[$i]->tag
			);
			if (!$result) {
				$tout = [$tags[$i], $id, $value];
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
				"Error while assigning a tag '{$out}' for user '{$username}' in view '{$view}'."
			);
		}

		// Remove tags that were assigned to element but now they were unassigned
		$tag_all = $this->tag_assign[$key]['tag'] ?? [];
		$tag_assign = array_map(fn ($tag) => $tag->tag, $tags);
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
		[
			'tag' => $tag,
			'id' => $id,
			'value' => $value
		] = (array) $param->getCallbackParameter();
		$this->unassignTagInternal($id, $value, (array) $tag);
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
		$username = $this->getPage()->User->getUsername();
		$view = $this->getViewName();
		$section = ($tag['access'] == TagConfig::ACCESSIBILITY_GLOBAL ? TagAssignConfig::GLOBAL_SECTION : $username);
		$key = "{$id}_{$value}";
		$result = $tag_assign_config->removeTagAssignConfig(
			$section,
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
				"Error while unassigning a tag '{$tout}' for user '{$username}' in view '{$view}' and element '{$key}'."
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
