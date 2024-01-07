<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;

/**
 * Quick volume edit control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class QuickVolumeEdit extends Portlets implements IActiveControl
{
	public const SAVE_VOLUME_ACTION_OK = 'SaveVolumeActionOk';

	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	public function openQuickVolumeEdit($sender, $param)
	{
		$mediaid = (int) $param->getCallbackParameter();
		$this->open($mediaid);
	}

	public function open($mediaid)
	{
		$this->QuickVolumeEditDirectives->setMediaId($mediaid);
		$this->QuickVolumeEditDirectives->setVolume();
	}

	public function getSaveVolumeActionOK()
	{
		return $this->getViewState(self::SAVE_VOLUME_ACTION_OK, '');
	}

	public function setSaveVolumeActionOK($action_ok)
	{
		$this->setViewState(self::SAVE_VOLUME_ACTION_OK, $action_ok);
	}
}
