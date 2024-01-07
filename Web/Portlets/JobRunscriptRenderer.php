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

use Prado\Prado;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;

/**
 * Job runscript renderer.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class JobRunscriptRenderer extends DirectiveRenderer
{
	public const DIRECTIVE_COUNT = 7;

	private static $render_index = 0;
	private $item_count;

	public function onInit($param)
	{
		parent::onInit($param);
		$directive_runscript = $this->getSourceTemplateControl();
		if (!method_exists($directive_runscript, 'removeRunscript')) {
			return;
		}
		if ($this->ItemIndex % self::DIRECTIVE_COUNT === 0) {
			$alb = new TActiveLinkButton();
			$alb->CssClass = 'w3-button w3-red w3-right';
			$alb->attachEventHandler(
				'OnCommand',
				[$directive_runscript, 'removeRunscript']
			);
			$alb->CommandName = $this->ItemIndex / self::DIRECTIVE_COUNT;
			$alb->Text = '<i class="fa fa-trash-alt"></i> &nbsp;' . Prado::localize('Remove');
			$this->addParsedObject($alb);
		}
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->item_count = $this->getParent()->getItems()->getCount();
	}

	public function render($writer)
	{
		if (self::$render_index % self::DIRECTIVE_COUNT === 0) {
			$writer->write('<h3 class="w3-left runscript_options">Runscript #' . ((self::$render_index / self::DIRECTIVE_COUNT) + 1) . '</h3>');
		}
		self::$render_index++;

		if (self::$render_index === $this->item_count) {
			$this->resetRenderIndex();
		}
		parent::render($writer);
	}

	public static function resetRenderIndex()
	{
		self::$render_index = 0;
	}
}
