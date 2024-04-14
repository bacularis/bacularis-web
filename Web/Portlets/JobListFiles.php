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

namespace Bacularis\Web\Portlets;

/**
 * Job list files control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class JobListFiles extends Portlets
{
	public const JOBID = 'JobId';

	public const DEFAULT_PAGE_SIZE = 100;

	public function onLoad($param)
	{
		parent::onLoad($param);
		if ($this->getPage()->IsPostBack || $this->getPage()->IsCallBack) {
			return;
		}
		$this->FileListOffset->Text = 0;
		$this->FileListLimit->Text = self::DEFAULT_PAGE_SIZE;
	}

	public function loadFileList($sender, $param)
	{
		$params = [
			'offset' => (int) ($this->FileListOffset->Text),
			'limit' => (int) ($this->FileListLimit->Text)
		];
		if ($this->FileListOrderBy->SelectedValue != 'none') {
			$params['order_by'] = $this->FileListOrderBy->SelectedValue;
			$params['order_type'] = $this->FileListOrderType->SelectedValue;
		}
		if (!empty($this->FileListType->SelectedValue)) {
			$params['type'] = $this->FileListType->SelectedValue;
		}
		if (!empty($this->FileListSearch->Text)) {
			$params['search'] = $this->FileListSearch->Text;
		}
		$params['details'] = '1';
		$query = '?' . http_build_query($params);
		$result = $this->getModule('api')->get(
			['jobs', $this->getJobId(), 'files', $query]
		);
		if ($result->error === 0) {
			$file_list = $result->output;
			if (!empty($this->FileListSearch->Text)) {
				$this->findFileListItems($file_list, $this->FileListSearch->Text);
			}
			$this->FileList->DataSource = $file_list;
			$this->FileList->dataBind();
			$this->FileListCount->Text = count($file_list);
		} else {
			$this->FileList->DataSource = [];
			$this->FileList->dataBind();
			$this->FileListCount->Text = 0;
		}
	}

	private function findFileListItems(&$file_list, $keyword)
	{
		for ($i = 0; $i < count($file_list); $i++) {
			$pos = stripos($file_list[$i]->file, $keyword);
			$str1 = substr($file_list[$i]->file, 0, $pos);
			$key_len = strlen($keyword);
			$key = substr($file_list[$i]->file, $pos, $key_len);
			$str2 = substr($file_list[$i]->file, ($pos + $key_len));
			$file_list[$i]->file = $str1 . '<strong class="w3-text-red">' . $key . '</strong>' . $str2;
		}
	}

	/**
	 * Set job identifier to show files.
	 *
	 * @param mixed $jobid
	 */
	public function setJobId($jobid)
	{
		$jobid = (int) $jobid;
		$this->setViewState(self::JOBID, $jobid, 0);
	}

	/**
	 * Get job identifier to show files.
	 *
	 * @return int job identifier
	 */
	public function getJobId()
	{
		return $this->getViewState(self::JOBID, 0);
	}
}
