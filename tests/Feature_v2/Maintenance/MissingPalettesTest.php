<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

/**
 * We don't care for unhandled exceptions in tests.
 * It is the nature of a test to throw an exception.
 * Without this suppression we had 100+ Linter warning in this file which
 * don't help anything.
 *
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Tests\Feature_v2\Maintenance;

use Tests\Feature_v2\Base\BaseApiWithDataTest;

class MissingPalettesTest extends BaseApiWithDataTest
{
	public function testGuest(): void
	{
		$response = $this->getJsonWithData('Maintenance::missingPalettes');
		$this->assertUnauthorized($response);

		$response = $this->postJson('Maintenance::missingPalettes');
		$this->assertUnauthorized($response);
	}

	public function testUser(): void
	{
		$response = $this->actingAs($this->userLocked)->getJsonWithData('Maintenance::missingPalettes');
		$this->assertForbidden($response);

		$response = $this->actingAs($this->userLocked)->postJson('Maintenance::missingPalettes');
		$this->assertForbidden($response);
	}

	public function testAdmin(): void
	{
		$response = $this->actingAs($this->admin)->getJsonWithData('Maintenance::missingPalettes');
		$this->assertOk($response);

		$response = $this->actingAs($this->admin)->postJson('Maintenance::missingPalettes');
		$this->assertNoContent($response);
	}
}