<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2023 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\JobInfo;
use Bacularis\Web\Modules\WebConfig;

/**
 * Application settings class.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class ApplicationSettings extends BaculumWebPage
{
	public function onInit($param)
	{
		parent::onInit($param);
		$this->DecimalBytes->Checked = true;
		if (count($this->web_config) > 0) {
			$this->Language->SelectedValue = $this->web_config['baculum']['lang'];
			$this->Debug->Checked = ($this->web_config['baculum']['debug'] == 1);
			$this->MaxJobs->Text = (key_exists('max_jobs', $this->web_config['baculum']) ? (int) ($this->web_config['baculum']['max_jobs']) : JobInfo::DEFAULT_MAX_JOBS);
			if (key_exists('size_values_unit', $this->web_config['baculum'])) {
				$this->DecimalBytes->Checked = ($this->web_config['baculum']['size_values_unit'] === 'decimal');
				$this->BinaryBytes->Checked = ($this->web_config['baculum']['size_values_unit'] === 'binary');
			}
			if (key_exists('time_in_job_log', $this->web_config['baculum'])) {
				$this->TimeInJobLog->Checked = ($this->web_config['baculum']['time_in_job_log'] == 1);
			}
			if (key_exists('date_time_format', $this->web_config['baculum'])) {
				$this->DateTimeFormat->Text = $this->web_config['baculum']['date_time_format'];
			} else {
				$this->DateTimeFormat->Text = WebConfig::DEF_DATE_TIME_FORMAT;
			}
			$this->EnableMessagesLog->Checked = $this->getModule('web_config')->isMessagesLogEnabled();
			if (key_exists('job_age_on_job_status_graph', $this->web_config['baculum'])) {
				$this->JobAgeOnJobStatusGraph->setDirectiveValue($this->web_config['baculum']['job_age_on_job_status_graph']);
			} else {
				$this->JobAgeOnJobStatusGraph->setDirectiveValue(0);
			}
			$this->JobAgeOnJobStatusGraph->createDirective();

			$this->EnableAuditLog->Checked = (bool) ($this->web_config['baculum']['enable_audit_log'] ?? AuditLog::DEF_ENABLED);
			$this->AuditLogMaxLines->Text = $this->web_config['baculum']['audit_log_max_lines'] ?? AuditLog::DEF_MAX_LINES;
			if (key_exists('audit_log_types', $this->web_config['baculum']) && is_array($this->web_config['baculum']['audit_log_types'])) {
				$types = $this->web_config['baculum']['audit_log_types'];
				$this->LogTypeInfo->Checked = in_array(AuditLog::TYPE_INFO, $types);
				$this->LogTypeWarning->Checked = in_array(AuditLog::TYPE_WARNING, $types);
				$this->LogTypeError->Checked = in_array(AuditLog::TYPE_ERROR, $types);
			} else {
				// Default log settings (if not set in config)
				$this->LogTypeInfo->Checked = in_array(AuditLog::TYPE_INFO, AuditLog::DEF_TYPES);
				$this->LogTypeWarning->Checked = in_array(AuditLog::TYPE_WARNING, AuditLog::DEF_TYPES);
				$this->LogTypeError->Checked = in_array(AuditLog::TYPE_ERROR, AuditLog::DEF_TYPES);
			}
			if (key_exists('audit_log_categories', $this->web_config['baculum']) && is_array($this->web_config['baculum']['audit_log_categories'])) {
				$categories = $this->web_config['baculum']['audit_log_categories'];
				$this->LogCategoryConfig->Checked = in_array(AuditLog::CATEGORY_CONFIG, $categories);
				$this->LogCategoryAction->Checked = in_array(AuditLog::CATEGORY_ACTION, $categories);
				$this->LogCategoryApplication->Checked = in_array(AuditLog::CATEGORY_APPLICATION, $categories);
				$this->LogCategorySecurity->Checked = in_array(AuditLog::CATEGORY_SECURITY, $categories);
			} else {
				$this->LogCategoryConfig->Checked = in_array(AuditLog::CATEGORY_CONFIG, AuditLog::DEF_CATEGORIES);
				$this->LogCategoryAction->Checked = in_array(AuditLog::CATEGORY_ACTION, AuditLog::DEF_CATEGORIES);
				$this->LogCategoryApplication->Checked = in_array(AuditLog::CATEGORY_APPLICATION, AuditLog::DEF_CATEGORIES);
				$this->LogCategorySecurity->Checked = in_array(AuditLog::CATEGORY_SECURITY, AuditLog::DEF_CATEGORIES);
			}
		}
	}


	public function saveGeneral($sender, $param)
	{
		if (count($this->web_config) > 0) {
			$this->web_config['baculum']['lang'] = $this->Language->SelectedValue;
			$this->web_config['baculum']['debug'] = ($this->Debug->Checked === true) ? 1 : 0;
			$web_config = $this->getModule('web_config');
			$web_config->setConfig($this->web_config);
			$web_config->setLanguage($this->Language->SelectedValue);
		}
	}

	public function saveDisplay($sender, $param)
	{
		if (count($this->web_config) > 0) {
			$max_jobs = (int) ($this->MaxJobs->Text);
			$this->web_config['baculum']['max_jobs'] = $max_jobs;
			$this->web_config['baculum']['size_values_unit'] = $this->BinaryBytes->Checked ? 'binary' : 'decimal';
			$this->web_config['baculum']['time_in_job_log'] = ($this->TimeInJobLog->Checked === true) ? 1 : 0;
			$this->web_config['baculum']['date_time_format'] = $this->DateTimeFormat->Text;
			$this->web_config['baculum']['job_age_on_job_status_graph'] = $this->JobAgeOnJobStatusGraph->getValue();
			$this->getModule('web_config')->setConfig($this->web_config);
		}
	}

	public function saveFeatures($sender, $param)
	{
		if (count($this->web_config) > 0) {
			$this->web_config['baculum']['enable_messages_log'] = ($this->EnableMessagesLog->Checked === true) ? 1 : 0;
			$this->getModule('web_config')->setConfig($this->web_config);
		}
	}

	public function saveAuditLog($sender, $param)
	{
		if (count($this->web_config) > 0) {
			$this->web_config['baculum']['enable_audit_log'] = ($this->EnableAuditLog->Checked === true) ? 1 : 0;
			$this->web_config['baculum']['audit_log_max_lines'] = (int) $this->AuditLogMaxLines->Text;
			$types = [];
			if ($this->LogTypeInfo->Checked) {
				$types[] = AuditLog::TYPE_INFO;
			}
			if ($this->LogTypeWarning->Checked) {
				$types[] = AuditLog::TYPE_WARNING;
			}
			if ($this->LogTypeError->Checked) {
				$types[] = AuditLog::TYPE_ERROR;
			}
			if (count($types) > 0) {
				$this->web_config['baculum']['audit_log_types'] = $types;
			} elseif (isset($this->web_config['baculum']['audit_log_types'])) {
				unset($this->web_config['baculum']['audit_log_types']);
			}

			$categories = [];
			if ($this->LogCategoryConfig->Checked) {
				$categories[] = AuditLog::CATEGORY_CONFIG;
			}
			if ($this->LogCategoryAction->Checked) {
				$categories[] = AuditLog::CATEGORY_ACTION;
			}
			if ($this->LogCategoryApplication->Checked) {
				$categories[] = AuditLog::CATEGORY_APPLICATION;
			}
			if ($this->LogCategorySecurity->Checked) {
				$categories[] = AuditLog::CATEGORY_SECURITY;
			}
			if (count($categories) > 0) {
				$this->web_config['baculum']['audit_log_categories'] = $categories;
			} elseif (isset($this->web_config['baculum']['audit_log_categories'])) {
				unset($this->web_config['baculum']['audit_log_categories']);
			}
			$this->getModule('web_config')->setConfig($this->web_config);
		}
	}

	public function loadAuditLog($sender, $param)
	{
		$logs = $this->getModule('audit')->getLogs();
		$this->getCallbackClient()->callClientFunction(
			'oAppSettingsAuditLog.init',
			[$logs]
		);
	}
}
