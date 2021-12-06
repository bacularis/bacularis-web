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
 * Volume list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class VolumeList extends BaculumWebPage {

	const USE_CACHE = false;

	public $volumes;

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->volumes = $this->getVolumes();
	}

	public function getVolumes() {
		return $this->getModule('api')->get(
			array('volumes'),
			null,
			true,
			self::USE_CACHE
		)->output;
	}

	/**
	 * Prune multiple volumes.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 * @return none
	 */
	public function pruneVolumes($sender, $param) {
		$result = [];
		$mediaids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($mediaids); $i++) {
			$ret = $this->getModule('api')->set(
				['volumes', intval($mediaids[$i]), 'prune']
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update($this->BulkActions->BulkActionsOutput, implode(PHP_EOL, $result));
		$this->updateVolumes($sender, $param);
	}

	/**
	 * Purge multiple volumes.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 * @return none
	 */
	public function purgeVolumes($sender, $param) {
		$result = [];
		$mediaids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($mediaids); $i++) {
			$ret = $this->getModule('api')->set(
				['volumes', intval($mediaids[$i]), 'purge']
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update($this->BulkActions->BulkActionsOutput, implode(PHP_EOL, $result));
		$this->updateVolumes($sender, $param);
	}

	/**
	 * Delete multiple volumes.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 * @return none
	 */
	public function deleteVolumes($sender, $param) {
		$result = [];
		$mediaids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($mediaids); $i++) {
			$ret = $this->getModule('api')->remove(
				['volumes', intval($mediaids[$i])]
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update($this->BulkActions->BulkActionsOutput, implode(PHP_EOL, $result));
		$this->updateVolumes($sender, $param);
	}

	/**
	 * Update volumes callback.
	 * It updates volume table data.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 * @return none
	 */
	public function updateVolumes($sender, $param) {
		$volumes = $this->getVolumes();
		$this->getCallbackClient()->callClientFunction('oVolumeList.update', [$volumes]);
	}
}
?>
