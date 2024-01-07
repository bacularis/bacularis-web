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
 * Copyright (C) 2013-2019 Kern Sibbald
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

use Prado\IDataRenderer;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TTemplateControl;

/**
 * Baculum conditional control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BConditional extends TTemplateControl implements IDataRenderer, IActiveControl
{
	public const BCONDITION = 'BCondition';
	public const TYPE_TPL_FALSE = 0;
	public const TYPE_TPL_TRUE = 1;

	private $item_true_template;
	private $item_false_template;
	private $data;
	private $creating_children = false;

	public function onInit($param)
	{
		parent::onInit($param);
	}

	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	public function getData()
	{
		return $this->data;
	}

	public function setData($data)
	{
		$this->data = $data;
	}

	public function setCondition($value)
	{
		settype($value, 'bool');
		$this->setViewState(self::BCONDITION, $value);
	}

	public function getCondition()
	{
		return $this->getViewState(self::BCONDITION);
	}

	public function createChildControls()
	{
		$this->creating_children = true;
		$this->dataBindProperties();
		$result = $this->getCondition();
		$true_template = $this->getTrueTemplate();
		$false_template = $this->getFalseTemplate();
		if ($result) {
			if ($true_template) {
				$true_template->instantiateIn($this->getTemplateControl(), $this);
			}
		} elseif ($false_template) {
			$false_template->instantiateIn($this->getTemplateControl(), $this);
		}
		$this->setData($this->getTemplateControl());
		$this->creating_children = false;
	}

	public function addParsedObject($object)
	{
		if ($this->creating_children) {
			parent::addParsedObject($object);
		}
	}

	public function getTrueTemplate()
	{
		return $this->item_true_template;
	}

	public function setTrueTemplate($template)
	{
		if ($template instanceof ITemplate) {
			$this->item_true_template = $template;
		}
	}

	public function getFalseTemplate()
	{
		return $this->item_false_template;
	}

	public function setFalseTemplate($template)
	{
		if ($template instanceof ITemplate) {
			$this->item_false_template = $template;
		}
	}
}
