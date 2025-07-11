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

namespace Tests\Traits;

use App\Constants\PhotoAlbum as PA;
use Illuminate\Support\Facades\DB;
use function Safe\fileowner;
use function Safe\scandir;
use function Safe\unlink;

trait RequiresEmptyPhotos
{
	use InteractsWithFilesystemPermissions;

	abstract protected function assertDatabaseCount($table, int $count, $connection = null);

	protected function setUpRequiresEmptyPhotos(): void
	{
		$this->setUpInteractsWithFilesystemPermissions();
		// Assert that photo table is empty
		$this->assertDatabaseCount(PA::PHOTO_ALBUM, 0);
		$this->assertDatabaseCount('size_variants', 0);
		$this->assertDatabaseCount('photos', 0);
		$this->assertDatabaseCount('palettes', 0);
		$this->assertDatabaseCount('jobs_history', 0);
		static::assertEquals(
			0,
			DB::table('statistics')->whereNotNull('photo_id')
				->count()
		);
	}

	protected function tearDownRequiresEmptyPhotos(): void
	{
		// Clean up remaining stuff from tests
		DB::table('palettes')->delete();
		DB::table(PA::PHOTO_ALBUM)->delete();
		DB::table('size_variants')->delete();
		DB::table('photos')->delete();
		DB::table('jobs_history')->delete();
		DB::table('statistics')->whereNotNull('photo_id')->delete();
		self::cleanPublicFolders();
	}

	/**
	 * Cleans the "public" folders 'uploads' and 'sym'.
	 *
	 * Removes all files from the directories except for sub-directories and
	 * 'index.html'.
	 *
	 * @return void
	 */
	protected static function cleanPublicFolders(): void
	{
		self::cleanupHelper(base_path('public/uploads/'));
	}

	/**
	 * Cleans the designated directory recursively.
	 *
	 * Removes all files from the directories except for sub-directories and
	 * 'index.html'.
	 *
	 * @param string $dirPath the path of the directory
	 *
	 * @return void
	 */
	private static function cleanupHelper(string $dirPath): void
	{
		if (!is_dir($dirPath)) {
			return;
		}
		if (fileowner($dirPath) === self::$effUserId) {
			\Safe\chmod($dirPath, 02775);
		}
		$dirEntries = scandir($dirPath);
		foreach ($dirEntries as $dirEntry) {
			if (in_array($dirEntry, ['.', '..', 'index.html', '.gitignore'], true)) {
				continue;
			}

			$dirEntryPath = $dirPath . DIRECTORY_SEPARATOR . $dirEntry;
			if (is_dir($dirEntryPath) && !is_link($dirEntryPath)) {
				self::cleanupHelper($dirEntryPath);
			}
			if (is_file($dirEntryPath) || is_link($dirEntryPath)) {
				unlink($dirEntryPath);
			}
		}
	}
}
