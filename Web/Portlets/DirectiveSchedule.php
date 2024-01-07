<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

namespace Bacularis\Web\Portlets;

use Prado\Web\UI\TCommandEventParameter;
use Bacularis\Common\Modules\Params;

/**
 * Schedule directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveSchedule extends DirectiveListTemplate
{
	public const SCHEDULE_MODE_HOURLY = 'hourly';
	public const SCHEDULE_MODE_DAILY = 'daily';
	public const SCHEDULE_MODE_WEEKLY = 'weekly';
	public const SCHEDULE_MODE_MONTHLY = 'monthly';
	public const SCHEDULE_MODE_CUSTOM = 'custom';

	private $directives_dir = [
		'Pool',
		'FullPool',
		'IncrementalPool',
		'DifferentialPool',
		'Level',
		'Storage',
		'Messages',
		'Priority',
		'SpoolData',
		'MaxRunSchedTime',
		'Accurate',
		'NextPool'
	];

	private $directives_fd = [
		'MaxConnectTime'
	];

	private $directive_name = [
		'dir' => 'Run',
		'fd' => 'Connect'
	];

	private $time_directives = [
		'TimeHourly',
		'TimeDaily',
		'TimeWeekly',
		'TimeMonthly',
		'TimeCustom',
		'TimeHourlyCustom',
		'DaysOfWeekWeekly',
		'DaysOfWeekMonthly',
		'DaysOfWeekCustom',
		'DaysOfMonthCustom',
		'WeeksOfMonthMonthly',
		'WeeksOfMonthCustom',
		'WeeksOfYearCustom',
		'MonthsOfYearCustom'
	];

	public function loadConfig()
	{
		$load_values = $this->getLoadValues();
		$directives = $this->getData();
		if ($load_values) {
			/**
			 * For existing config without any 'Run' defined don't show sample 'Run' in form.
			 * The sample 'Run' should be displayed only in new schedule form.
			 */
			$directives = array_filter($directives);
		}
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$subdirectives = $this->getSubDirectives();
		$resource_desc = $this->getResourceDesc();
		$time_directives = $directive_values = $overwrite_directives = [];
		foreach ($directives as $index => $directive) {
			for ($i = 0; $i < count($subdirectives); $i++) {
				$default_value = null;
				$data = null;
				$resource = null;
				$directive_desc = null;
				$required = false;
				if (key_exists($subdirectives[$i], $resource_desc)) {
					$directive_desc = $resource_desc[$subdirectives[$i]];
				}
				if (is_object($directive_desc)) {
					if (property_exists($directive_desc, 'DefaultValue')) {
						$default_value = $directive_desc->DefaultValue;
					}
					if (property_exists($directive_desc, 'Data')) {
						$data = $directive_desc->Data;
					}
					if (property_exists($directive_desc, 'Resource')) {
						$resource = $directive_desc->Resource;
					}
				}
				if (preg_match('/^(Full|Incremental|Differential)Pool$/', $subdirectives[$i]) === 1) {
					$resource = 'Pool';
				}
				$in_config = false;
				if ($load_values === true && is_object($directive)) {
					$in_config = property_exists($directive, $subdirectives[$i]);
				}

				$directive_value = null;
				if (is_object($directive) && property_exists($directive, $subdirectives[$i])) {
					$directive_value = $directive->{$subdirectives[$i]};
				}
				$overwrite_directives[$subdirectives[$i]] = [
					'host' => $host,
					'component_type' => $component_type,
					'component_name' => $component_name,
					'resource_type' => $resource_type,
					'resource_name' => $resource_name,
					'directive_name' => $subdirectives[$i],
					'directive_value' => $directive_value,
					'default_value' => $default_value,
					'required' => $required,
					'data' => $data,
					'resource' => $resource,
					'label' => $subdirectives[$i],
					'in_config' => $in_config,
					'show' => $in_config || !$load_values || $this->SourceTemplateControl->getShowAllDirectives(),
					'resource_names' => $this->getResourceNames(),
					'parent_name' => __CLASS__,
					'group_name' => $index
				];
			}

			for ($i = 0; $i < count($this->time_directives); $i++) {
				$time_directives[$this->time_directives[$i]] = [
					'host' => $host,
					'component_type' => $component_type,
					'component_name' => $component_name,
					'resource_type' => $resource_type,
					'resource_name' => $resource_name,
					'directive_name' => $this->time_directives[$i],
					'directive_values' => $directive,
					'default_value' => 0,
					'parent_name' => __CLASS__,
					'group_name' => $index
				];
			}
			$directive_values[] = [
				'overwrite_directives' => $overwrite_directives,
				'time_directives' => $time_directives
			];
		}
		$this->RepeaterScheduleRuns->DataSource = $directive_values;
		$this->RepeaterScheduleRuns->dataBind();
	}

	private function getSubDirectives()
	{
		$subdirectives = [];
		$component_type = $this->getComponentType();
		if ($component_type === 'dir') {
			$subdirectives = $this->directives_dir;
		} elseif ($component_type === 'fd') {
			$subdirectives = $this->directives_fd;
		}
		return $subdirectives;
	}

	private function getResourceDesc()
	{
		$desc = [];
		$component_type = $this->getComponentType();
		if ($component_type === 'dir') {
			$data_desc = $this->Application->getModule('data_desc');
			$desc = $data_desc->getDescription($this->getComponentType(), 'Job');
		} elseif ($component_type === 'fd') {
			// TODO: Move it to data_desc.json
			$desc = [
				'MaxConnectTime' => (object) [
					'Required' => false,
					'ValueType' => 'str',
					'DefaultValue' => 0,
					'FieldType' => 'TextBox',
					'Section' => 'General'
				]
			];
		}
		return $desc;
	}

	public function createRunItem($sender, $param)
	{
		$load_values = $this->getLoadValues();
		$subdirectives = $this->getSubDirectives();
		for ($i = 0; $i < count($subdirectives); $i++) {
			$control = $param->Item->{$subdirectives[$i]};
			if (is_object($control)) {
				$data = $param->Item->Data['overwrite_directives'][$subdirectives[$i]];
				$control->setHost($data['host']);
				$control->setComponentType($data['component_type']);
				$control->setComponentName($data['component_name']);
				$control->setResourceType($data['resource_type']);
				$control->setResourceName($data['resource_name']);
				$control->setDirectiveName($data['directive_name']);
				$control->setDirectiveValue($data['directive_value']);
				$control->setDefaultValue($data['default_value']);
				$control->setRequired($data['required']);
				$control->setData($data['data']);
				$control->setResource($data['resource']);
				$control->setLabel($data['label']);
				$control->setInConfig($data['in_config']);
				$control->setShow($data['show']);
				$control->setResourceNames($data['resource_names']);
				$control->setParentName($data['parent_name']);
			}
		}

		for ($i = 0; $i < count($this->time_directives); $i++) {
			$control = $param->Item->{$this->time_directives[$i]};
			if (is_object($control)) {
				$data = $param->Item->Data['time_directives'][$this->time_directives[$i]];
				$control->setHost($data['host']);
				$control->setComponentType($data['component_type']);
				$control->setComponentName($data['component_name']);
				$control->setResourceType($data['resource_type']);
				$control->setResourceName($data['resource_name']);
				$control->setDirectiveName($data['directive_name']);
				$control->setDefaultValue($data['default_value']);
				$control->setParentName($data['parent_name']);
			}
		}

		$directive = $param->Item->Data['time_directives']['TimeHourly']['directive_values'];

		// Hour and minute
		$hour = null;
		$minute = null;
		$is_hourly = false;
		if (is_object($directive)) {
			if (count($directive->Hour) == 24) {
				$is_hourly = true;
			}
			$hour = $directive->Hour[0];
			/**
			 * Check if Minute property exists because of bug about missing Minute
			 * @see http://bugs.bacula.org/view.php?id=2318
			 */
			$minute = property_exists($directive, 'Minute') ? $directive->Minute : 0;
		}

		if ($is_hourly && is_integer($minute) && $minute > 0) {
			$param->Item->TimeHourlyCustomOption->Checked = true;
			$hour = null;
		} elseif (!$is_hourly && is_integer($hour) && is_integer($minute)) {
			$param->Item->TimeAtCustomOption->Checked = true;
		} else {
			$param->Item->TimeEveryHourCustomOption->Checked = true;
			$hour = null;
			$minute = null;
		}

		$time_value = ['hour' => $hour, 'minute' => $minute];
		$param->Item->TimeHourly->setDirectiveValue($time_value);
		$param->Item->TimeDaily->setDirectiveValue($time_value);
		$param->Item->TimeWeekly->setDirectiveValue($time_value);
		$param->Item->TimeMonthly->setDirectiveValue($time_value);
		$param->Item->TimeHourlyCustom->setDirectiveValue($time_value);
		$param->Item->TimeCustom->setDirectiveValue($time_value);

		// Day of the week
		$all_dows = true;
		if (is_object($directive)) {
			$all_dows = count($directive->DayOfWeek) == 7;
			$param->Item->DaysOfWeekWeekly->setDirectiveValue($directive->DayOfWeek);
			$param->Item->DaysOfWeekMonthly->setDirectiveValue($directive->DayOfWeek);
			$param->Item->DaysOfWeekCustom->setDirectiveValue($directive->DayOfWeek);
		}

		// Week of the month
		$all_woms = true;
		if (is_object($directive)) {
			$all_woms = count($directive->WeekOfMonth) == 6;
			$param->Item->WeeksOfMonthMonthly->setDirectiveValue($directive->WeekOfMonth);
			$param->Item->WeeksOfMonthCustom->setDirectiveValue($directive->WeekOfMonth);
		}

		// Days of the month
		$all_doms = true;
		if (is_object($directive)) {
			$all_doms = count($directive->Day) == 31;
			$param->Item->DaysOfMonthCustom->setDirectiveValue($directive->Day);
		}

		// Months of the year
		$all_moys = true;
		if (is_object($directive)) {
			$all_moys = count($directive->Month) == 12;
			$param->Item->MonthsOfYearCustom->setDirectiveValue($directive->Month);
		}

		// Weeks of the month
		$all_woys = true;
		if (is_object($directive)) {
			$all_woys = count($directive->WeekOfYear) == 54;
			$param->Item->WeeksOfYearCustom->setDirectiveValue($directive->WeekOfYear);
		}


		if (is_object($directive)) {
			$custom = $all_doms && $all_moys && $all_woys;
			if ($is_hourly && $all_dows && $all_woms && $custom) {
				// hourly
				$param->Item->ScheduleMode->Value = self::SCHEDULE_MODE_HOURLY;
			} elseif (!$is_hourly && is_integer($hour) && is_integer($minute) && $all_dows && $all_woms && $custom) {
				// daily
				$param->Item->ScheduleMode->Value = self::SCHEDULE_MODE_DAILY;
			} elseif (!$is_hourly && is_integer($hour) && is_integer($minute) && !$all_dows && $all_woms && $custom) {
				// weekly
				$param->Item->ScheduleMode->Value = self::SCHEDULE_MODE_WEEKLY;
			} elseif (!$is_hourly && is_integer($hour) && is_integer($minute) && !$all_dows && !$all_woms && $custom) {
				// monthly
				$param->Item->ScheduleMode->Value = self::SCHEDULE_MODE_MONTHLY;
			} else {
				// custom
				$param->Item->ScheduleMode->Value = self::SCHEDULE_MODE_CUSTOM;
			}
		} else {
			// daily - default for new schedule
			$param->Item->ScheduleMode->Value = self::SCHEDULE_MODE_DAILY;
		}
	}

	public function removeSchedule($sender, $param)
	{
		if ($param instanceof TCommandEventParameter) {
			$idx = (int) $param->getCommandName();
			$data = $this->getDirectiveValue(true);
			array_splice($data, $idx, 1);
			$this->setData($data);
			$this->loadConfig();
		}
	}

	private function setTime($directive, &$obj, &$directive_values)
	{
		$t = $directive->getDirectiveValue();
		$obj->Hour = [$t['hour']];
		$obj->Minute = $t['minute'];
		$min = sprintf('%02d', $t['minute']);
		$directive_values[] = "at {$t['hour']}:{$min}";
	}

	private function setTimeHourly($t, &$obj, &$directive_values)
	{
		$obj->Hour = range(0, 23);
		$obj->Minute = $t['minute'];
		if ($t['minute'] > 0) {
			$min = sprintf('%02d', $t['minute']);
			$directive_values[] = "hourly at 0:{$min}";
		} else {
			$directive_values[] = 'hourly';
		}
	}

	private function setDaysOfWeek($directive, &$obj, &$directive_values)
	{
		$wdays = array_keys(Params::$wdays);
		$dows = $directive->getDirectiveValue();
		$dows_len = count($dows);
		if ($dows_len == 0) {
			$obj->DayOfWeek = range(0, 6);
		} else {
			$obj->DayOfWeek = $dows;
			$directive_values[] = Params::getDaysOfWeekConfig($dows);
		}
	}

	private function setWeeksOfYear($directive, &$obj, &$directive_values)
	{
		$woys = $directive->getDirectiveValue();
		$obj->WeekOfYear = $woys;
		$directive_values[] = Params::getWeeksOfYearConfig($woys);
	}

	private function setWeeksOfMonth($directive, &$obj, &$directive_values)
	{
		$woms = $directive->getDirectiveValue();
		$woms_len = count($woms);
		if ($woms_len == 0) {
			// all weeks
			$obj->WeekOfMonth = range(0, 5);
		} else {
			// selected weeks
			$obj->WeekOfMonth = $woms;
			$directive_values[] = Params::getWeeksOfMonthConfig($woms);
		}
	}

	private function setMonthsOfYear($directive, &$obj, &$directive_values)
	{
		$moys = $directive->getDirectiveValue();
		$obj->Month = $moys;
		$directive_values[] = Params::getMonthsOfYearConfig($moys);
	}

	private function setDaysOfMonth($directive, &$obj, &$directive_values)
	{
		$doms = $directive->getDirectiveValue();
		$doms_len = count($doms);
		$obj->Day = $doms;
		$directive_values[] = Params::getDaysOfMonthConfig($doms);
	}

	public function getDirectiveValue($ret_obj = false)
	{
		$directive_values = [];
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$subdirectives = $this->getSubDirectives();
		$directive_name = $this->directive_name[$component_type];
		$values = [];
		$values[$directive_name] = [];
		$objs = [];

		$ctrls = $this->RepeaterScheduleRuns->getItems();
		foreach ($ctrls as $value) {
			$obj = new \StdClass();
			for ($i = 0; $i < count($subdirectives); $i++) {
				$control = $value->{$subdirectives[$i]};
				$control->setValue();
				$subdirective_name = $control->getDirectiveName();
				$subdirective_value = $control->getDirectiveValue();
				$default_value = $control->getDefaultValue();
				if (is_null($subdirective_value)) {
					continue;
				}
				if (get_class($control) === 'Bacularis\Web\Portlets\DirectiveCheckBox') {
					settype($default_value, 'bool');
				}
				if (get_class($control) === 'Bacularis\Web\Portlets\DirectiveTextBox') {
					settype($default_value, 'string');
				}

				if ($subdirective_value === $default_value) {
					// value the same as default value, skip it
					continue;
				}
				$obj->{$subdirective_name} = $subdirective_value;
				if (get_class($control) === 'Bacularis\Web\Portlets\DirectiveCheckBox') {
					$subdirective_value = Params::getBoolValue($subdirective_value);
				}
				$directive_values[] = "{$subdirective_name}=\"{$subdirective_value}\"";
			}
			for ($i = 0; $i < count($this->time_directives); $i++) {
				$value->{$this->time_directives[$i]}->setValue();
			}

			switch ($value->ScheduleMode->Value) {
				case self::SCHEDULE_MODE_HOURLY: {
					// time (hourly)
					$t = $value->TimeHourly->getDirectiveValue();
					$this->setTimeHourly($t, $obj, $directive_values);
					break;
				}
				case self::SCHEDULE_MODE_DAILY: {
					// time (HH:MM)
					$this->setTime($value->TimeDaily, $obj, $directive_values);
					break;
				}
				case self::SCHEDULE_MODE_WEEKLY: {
					// set days of the week
					$this->setDaysOfWeek($value->DaysOfWeekWeekly, $obj, $directive_values);

					// time (HH:MM)
					$this->setTime($value->TimeWeekly, $obj, $directive_values);
					break;
				}
				case self::SCHEDULE_MODE_MONTHLY: {
					// weeks of the month
					$this->setWeeksOfMonth($value->WeeksOfMonthMonthly, $obj, $directive_values);

					// days of the week
					$this->setDaysOfWeek($value->DaysOfWeekMonthly, $obj, $directive_values);

					// time
					$this->setTime($value->TimeMonthly, $obj, $directive_values);
					break;
				}
				case self::SCHEDULE_MODE_CUSTOM: {
					// months of the year
					$this->setMonthsOfYear($value->MonthsOfYearCustom, $obj, $directive_values);

					// weeks of the year
					$this->setWeeksOfYear($value->WeeksOfYearCustom, $obj, $directive_values);

					// days of the month
					$this->setDaysOfMonth($value->DaysOfMonthCustom, $obj, $directive_values);

					// weeks of the month
					$this->setWeeksOfMonth($value->WeeksOfMonthCustom, $obj, $directive_values);

					// days of the week
					$this->setDaysOfWeek($value->DaysOfWeekCustom, $obj, $directive_values);

					// time
					if ($value->TimeEveryHourCustomOption->Checked) {
						$t = ['hour' => 0, 'minute' => 0];
						$this->setTimeHourly($t, $obj, $directive_values);
					} elseif ($value->TimeHourlyCustomOption->Checked) {
						$t = $value->TimeHourlyCustom->getDirectiveValue();
						$this->setTimeHourly($t, $obj, $directive_values);
					} elseif ($value->TimeAtCustomOption) {
						$this->setTime($value->TimeCustom, $obj, $directive_values);
					}
					break;
				}
			}
			// add missing default properties
			if (!property_exists($obj, 'Hour')) {
				$obj->Hour = range(0, 23);
			}

			if (!property_exists($obj, 'Minute')) {
				$obj->Minute = 0;
			}
			if (!property_exists($obj, 'DayOfWeek')) {
				$obj->DayOfWeek = range(0, 6);
			}
			if (!property_exists($obj, 'WeekOfYear')) {
				$obj->WeekOfYear = range(0, 53);
			}
			if (!property_exists($obj, 'WeekOfMonth')) {
				$obj->WeekOfMonth = range(0, 5);
			}
			if (!property_exists($obj, 'Month')) {
				$obj->Month = range(0, 11);
			}
			if (!property_exists($obj, 'Day')) {
				$obj->Day = range(0, 30);
			}

			$directive_values = array_filter($directive_values);
			$values[$directive_name][] = implode(' ', $directive_values);
			$objs[] = $obj;
			$directive_values = [];
		}
		return (($ret_obj) ? $objs : $values);
	}

	public function getDirectiveData()
	{
		return $this->getDirectiveValue(true);
	}

	public function newScheduleDirective()
	{
		$data = $this->getDirectiveValue(true);
		$obj = new \StdClass();
		$obj->Hour = [0];
		$obj->Minute = 0;
		$obj->Day = range(0, 30);
		$obj->Month = range(0, 11);
		$obj->DayOfWeek = range(0, 6);
		$obj->WeekOfMonth = range(0, 5);
		$obj->WeekOfYear = range(0, 53);

		if (is_array($data)) {
			$data[] = $obj;
		} else {
			$data = [$obj];
		}
		$this->setData($data);
		$this->SourceTemplateControl->setShowAllDirectives(true);
		$this->loadConfig();
	}
}
