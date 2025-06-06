<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

use App\Legacy\BaseConfigMigration;

return new class() extends BaseConfigMigration {
	public function getConfigs(): array
	{
		return [
			[
				'key' => 'editor_enabled',
				'value' => '1',
				'confidentiality' => '2',
				'cat' => 'Image Processing',
				'type_range' => self::BOOL,
				'description' => '',
			],
		];
	}
};
