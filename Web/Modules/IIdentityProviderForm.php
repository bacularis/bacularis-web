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

namespace Bacularis\Web\Modules;

/**
 * Interface for identity providers forms.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Interface
 */
interface IIdentityProviderForm
{
	/**
	 * Load settings from configuration to form.
	 *
	 * @param array $config identity provider config
	 */
	public function loadSettings(array $config): void;

	/**
	 * Load default settings to form.
	 */
	public function loadDefaultSettings(): void;

	/**
	 * Get settings from form to save in config.
	 *
	 * @return array configuration to save
	 */
	public function getSettings(): array;
}
