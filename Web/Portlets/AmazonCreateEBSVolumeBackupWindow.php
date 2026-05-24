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

use Bacularis\Web\Modules\JobAction;
use Prado\Prado;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;

/**
 * Create Amazon EBS volume backup control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AmazonCreateEBSVolumeBackupWindow extends AmazonBackupWindowBase
{
	public function onPreRender($param)
	{
		$page = $this->getPage();
		if ($page->IsCallback || $page->IsPostBack) {
			return;
		}
		$this->initialize();
	}

	public function initialize()
	{
		$this->loadResource('JobDefs', $this->AmazonCreateEBSVolumeBackupJobDefs);
		$this->loadResource('Storage', $this->AmazonCreateEBSVolumeBackupStorage);
		$this->loadResource('Pool', $this->AmazonCreateEBSVolumeBackupPool);
		$this->loadResource('Schedule', $this->AmazonCreateEBSVolumeBackupSchedule);
		$this->loadResource('Messages', $this->AmazonCreateEBSVolumeBackupMessages);
		$this->loadAmazonAccounts($this->AmazonCreateEBSVolumeBackupAccount);
		$this->loadRegions($this->AmazonCreateEBSVolumeBackupRegion);
		$this->loadLevels($this->AmazonCreateEBSVolumeBackupLevel);
		$this->loadEndpoints($this->AmazonCreateEBSVolumeBackupServiceEndpoint);
	}

	/**
	 * Setup directives based on selected JobDefs.
	 *
	 * @param TActiveLinkButton $sender event sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setupJobDefs($sender, $param)
	{
		$directives = [];
		$directive_value = $this->AmazonCreateEBSVolumeBackupJobDefs->getValue();
		if (is_string($directive_value)) {
			$jobdefs = rawurlencode($directive_value);
			$api = $this->getModule('api');
			$result = $api->get([
				'config', 'dir', 'jobdefs', $jobdefs
			]);
			if ($result->error != 0) {
				return;
			}
			$directives = $result->output;
			$json = json_encode($directives);
			$directives = json_decode($json, true);
		}
		$this->setJobDefs($directives);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oAmazonCreateEBSVolumeBackup.set_jobdefs_cb',
			[$directives]
		);
	}

	/**
	 * Set backup client to use in template.
	 *
	 * @param TActiveLinkButton $sender event sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setBackupClient($sender, $param)
	{
		$fd_name = $this->getBackupClient();
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oAmazonCreateEBSVolumeBackup.get_backup_client_cb',
			[$fd_name]
		);
	}

	/**
	 * Create new EBS backup.
	 *
	 * This includes creating:
	 *   - plugin settings
	 *   - FileSet resource
	 *   - Job resource
	 *
	 * @param TActiveLinkButton $sender event sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function createEBSBackup($sender, $param)
	{
		// Hide error message (if any)
		$this->hideError();

		// Create new plugin setting
		$line = $this->createPluginSettings();

		// Create new FileSet resource
		$status = false;
		if ($line) {
			$status = $this->createFileSet($line);
		}

		// Create new Job resource
		if ($status) {
			$this->createJob();
		}
	}

	/**
	 * Create plugin settings for EBS volume backup.
	 *
	 * @return string plugin directive value to use in FileSet or empty string on error
	 */
	private function createPluginSettings(): string
	{
		// Prepare plugin options
		$volume_ids = $this->AmazonCreateEBSVolumeBackupVolumeIds->getValue();
		$plugin_options = [
			'name' => $this->AmazonCreateEBSVolumeBackupName->getText(),
			'parameters' => [
				'volume-method' => true,
				'volume-ids' => $volume_ids,
				'account' => $this->AmazonCreateEBSVolumeBackupAccount->getSelectedValue(),
				'region' => $this->AmazonCreateEBSVolumeBackupRegion->getSelectedValue(),
				'ebs-endpoint-type' => $this->AmazonCreateEBSVolumeBackupServiceEndpoint->getSelectedValue(),
				'backup-workers' => $this->AmazonCreateEBSVolumeBackupMaxBackupWorkers->getText()
			]
		];

		// Create plugin settings
		$ret = $this->setPluginSettings($plugin_options);

		$line = '';
		if ($ret) {
			// Prepare Plugin directive value
			$line = $this->getPluginDirective($plugin_options['name']);
		}
		return $line;
	}

	/**
	 * Create FileSet configuration resource for EBS volume backup.
	 *
	 * @param string $plugin_val plugin directive value
	 * @return bool true on success, false otherwise
	 */
	private function createFileSet(string $plugin_val): bool
	{
		// Prepare FileSet
		$fileset_directives = [
			'Name' => $this->AmazonCreateEBSVolumeBackupName->getText(),
			'Include' => [
				'Plugin' => [
					$plugin_val
				]
			]
		];
		$description = $this->AmazonCreateEBSVolumeBackupDescription->getValue();
		if ($description) {
			$fileset_directives['Description'] = $description;
		}

		// Create new FileSet resource
		$api = $this->getModule('api');
		$result = $api->create(
			['config', 'dir', 'Fileset', $fileset_directives['Name']],
			['config' => json_encode($fileset_directives)]
		);
		$ret = ($result->error == 0);
		if (!$ret) {
			$emsg = sprintf("Error while writing Fileset resource. Error: '%s'.", $result->output);
			$this->showError($emsg);
		}
		return $ret;
	}

	/**
	 * Create new job.
	 *
	 * @return bool true if job created successfully, false otherwise
	 */
	private function createJob(): bool
	{
		// Prepare job directives
		$job_directives = [
			'Type' => 'Backup',
			'Client' => $this->AmazonCreateEBSVolumeBackupClient->getValue(),
			'Storage' => $this->AmazonCreateEBSVolumeBackupStorage->getValue(),
			'Pool' => $this->AmazonCreateEBSVolumeBackupPool->getValue(),
			'Messages' => $this->AmazonCreateEBSVolumeBackupMessages->getValue()
		];
		$level = $this->AmazonCreateEBSVolumeBackupLevel->getValue();
		if ($level) {
			$job_directives['Level'] = $level;
		}
		$schedule = $this->AmazonCreateEBSVolumeBackupSchedule->getValue();
		if ($schedule) {
			$job_directives['Schedule'] = $schedule;
		}
		$priority = $this->AmazonCreateEBSVolumeBackupPriority->getValue();
		if ($priority) {
			$job_directives['Priority'] = (int) $priority;
		}
		$jobdefs = $this->getJobDefs();
		if ($jobdefs) {
			$job_directives = array_filter(
				$job_directives,
				fn ($value, $name) => !$this->isInJobDefs($name, $value),
				ARRAY_FILTER_USE_BOTH
			);
		}
		// These directives cannot be taken from JobDefs for EBS backup
		$name = $this->AmazonCreateEBSVolumeBackupName->getText();
		$job_directives['Name'] = $name;
		$job_directives['Fileset'] = $name;
		$description = $this->AmazonCreateEBSVolumeBackupDescription->getValue();
		if ($description) {
			$job_directives['Description'] = $description;
		}
		$jobdefs = $this->AmazonCreateEBSVolumeBackupJobDefs->getValue();
		if ($jobdefs) {
			$job_directives['JobDefs'] = $jobdefs;
		}

		// Create new job resource
		$api = $this->getModule('api');
		$result = $api->create(
			['config', 'dir', 'Job', $name],
			['config' => json_encode($job_directives)]
		);
		$ret = ($result->error == 0);
		if (!$ret) {
			$emsg = sprintf(
				"Error while writing Job resource. Error: '%s'.",
				$result->output
			);
			$this->showError($emsg);
			return false;
		}

		// Reload Director configuration
		$api->set(['console'], ['reload']);

		if ($this->AmazonCreateEBSVolumeBackupRunJobNow->Checked) {
			$result = JobAction::runJobByName($name);
			if ($result->error != 0) {
				$emsg = sprintf(
					"Error while starting newly created Job. Error: '%s'.",
					$result->output
				);
				$this->showError($emsg);
				return false;
			}
		}

		// Everything is fine, close window
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oAmazonCreateEBSVolumeBackup.close_window'
		);

		return $ret;
	}

	/**
	 * Hide error message.
	 */
	protected function hideError(): void
	{
		$this->AmazonCreateEBSVolumeBackupWindowError->Display = 'None';
	}

	/**
	 * Show error message in window.
	 *
	 * @param string $emsg error message
	 */
	protected function showError(string $emsg): void
	{
		$this->AmazonCreateEBSVolumeBackupWindowError->Text = Prado::localize($emsg);
		$this->AmazonCreateEBSVolumeBackupWindowError->Display = 'Dynamic';
	}
}
