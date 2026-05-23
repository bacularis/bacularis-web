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
 * Create Amazon EC2 instance backup control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AmazonCreateEC2InstanceBackupWindow extends AmazonBackupWindowBase
{
	public function onPreRender($param)
	{
		$page = $this->getPage();
		if ($page->IsCallback || $page->IsPostBack) {
			return;
		}
		$this->loadResource('JobDefs', $this->AmazonCreateEC2InstanceBackupJobDefs);
		$this->loadResource('Storage', $this->AmazonCreateEC2InstanceBackupStorage);
		$this->loadResource('Pool', $this->AmazonCreateEC2InstanceBackupPool);
		$this->loadResource('Schedule', $this->AmazonCreateEC2InstanceBackupSchedule);
		$this->loadResource('Messages', $this->AmazonCreateEC2InstanceBackupMessages);
		$this->loadAmazonAccounts($this->AmazonCreateEC2InstanceBackupAccount);
		$this->loadRegions($this->AmazonCreateEC2InstanceBackupRegion);
		$this->loadLevels($this->AmazonCreateEC2InstanceBackupLevel);
		$this->loadEndpoints($this->AmazonCreateEC2InstanceBackupServiceEndpoint);
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
		$directive_value = $this->AmazonCreateEC2InstanceBackupJobDefs->getValue();
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
			'oAmazonCreateEC2InstanceBackup.set_jobdefs_cb',
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
			'oAmazonCreateEC2InstanceBackup.get_backup_client_cb',
			[$fd_name]
		);
	}

	/**
	 * Create new EC2 instance backup.
	 *
	 * This includes creating:
	 *   - plugin settings
	 *   - FileSet resource
	 *   - Job resource
	 *
	 * @param TActiveLinkButton $sender event sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function createEC2Backup($sender, $param)
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
	 * Create plugin settings for EC2 instance backup.
	 *
	 * @return string plugin directive value to use in FileSet or empty string on error
	 */
	private function createPluginSettings(): string
	{
		// Prepare plugin options
		$instance_ids = $this->AmazonCreateEC2InstanceBackupInstanceIds->getValue();
		$plugin_options = [
			'name' => $this->AmazonCreateEC2InstanceBackupName->getText(),
			'parameters' => [
				'instance-method' => true,
				'instance-ids' => $instance_ids,
				'account' => $this->AmazonCreateEC2InstanceBackupAccount->getSelectedValue(),
				'region' => $this->AmazonCreateEC2InstanceBackupRegion->getSelectedValue(),
				'ebs-endpoint-type' => $this->AmazonCreateEC2InstanceBackupServiceEndpoint->getSelectedValue(),
				'backup-workers' => $this->AmazonCreateEC2InstanceBackupMaxBackupWorkers->getText(),
				'exclude-data-volume-ids' => $this->AmazonCreateEC2InstanceBackupExcludeDataVolumeIds->getText(),
				'description' => $this->AmazonCreateEC2InstanceBackupSnapshotDescription->getText(),
				'snapshot-tags' => $this->AmazonCreateEC2InstanceBackupSnapshotTags->getText(),
				'copy-tags-from-source' => $this->AmazonCreateEC2InstanceBackupCopyTagsVolumeSnapshot->getChecked()
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
	 * Create FileSet configuration resource for EC2 instance backup.
	 *
	 * @param string $plugin_val plugin directive value
	 * @return bool true on success, false otherwise
	 */
	private function createFileSet(string $plugin_val): bool
	{
		// Prepare FileSet
		$fileset_directives = [
			'Name' => $this->AmazonCreateEC2InstanceBackupName->getText(),
			'Include' => [
				'Plugin' => [
					$plugin_val
				]
			]
		];
		$description = $this->AmazonCreateEC2InstanceBackupDescription->getValue();
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
			'Client' => $this->AmazonCreateEC2InstanceBackupClient->getValue(),
			'Storage' => $this->AmazonCreateEC2InstanceBackupStorage->getValue(),
			'Pool' => $this->AmazonCreateEC2InstanceBackupPool->getValue(),
			'Messages' => $this->AmazonCreateEC2InstanceBackupMessages->getValue()
		];
		$level = $this->AmazonCreateEC2InstanceBackupLevel->getValue();
		if ($level) {
			$job_directives['Level'] = $level;
		}
		$schedule = $this->AmazonCreateEC2InstanceBackupSchedule->getValue();
		if ($schedule) {
			$job_directives['Schedule'] = $schedule;
		}
		$priority = $this->AmazonCreateEC2InstanceBackupPriority->getValue();
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
		// These directives cannot be taken from JobDefs for EC2 instance backup
		$name = $this->AmazonCreateEC2InstanceBackupName->getText();
		$job_directives['Name'] = $name;
		$job_directives['Fileset'] = $name;
		$description = $this->AmazonCreateEC2InstanceBackupDescription->getValue();
		if ($description) {
			$job_directives['Description'] = $description;
		}
		$jobdefs = $this->AmazonCreateEC2InstanceBackupJobDefs->getValue();
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

		if ($this->AmazonCreateEC2InstanceBackupRunJobNow->Checked) {
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
			'oAmazonCreateEC2InstanceBackup.close_window'
		);

		return $ret;
	}

	/**
	 * Hide error message.
	 */
	protected function hideError(): void
	{
		$this->AmazonCreateEC2InstanceBackupWindowError->Display = 'None';
	}

	/**
	 * Show error message in window.
	 *
	 * @param string $emsg error message
	 */
	protected function showError(string $emsg): void
	{
		$this->AmazonCreateEC2InstanceBackupWindowError->Text = Prado::localize($emsg);
		$this->AmazonCreateEC2InstanceBackupWindowError->Display = 'Dynamic';
	}
}
