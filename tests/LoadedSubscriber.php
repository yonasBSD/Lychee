<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Event\TestSuite\Loaded;
use PHPUnit\Event\TestSuite\LoadedSubscriber as LoadedSubscriberInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

final class LoadedSubscriber implements LoadedSubscriberInterface
{
	use CreatesApplication;
	use MigrateApplication;

	public function notify(Loaded $event): void
	{
		$this->createApplication();
		$this->migrateApplication();

		// Detect local run base on the phpunit.xml file
		if (str_contains($event->testSuite()->name(), 'phpunit.xml') &&
			str_contains($event->testSuite()->tests()->asArray()[0]->file(), 'Feature_v1') &&
			config('features.vuejs')) {
			(new ConsoleOutput())->section()->writeln('<error>Wrong configuration:</error> <options=bold>Tests v1 are not compatible with VUEJS_ENABLED=true.</>');
			exit(1);
		}

		if (config('features.vuejs')) {
			// If there are any users in the DB, this tends to crash some tests (because we check exact count of users).
			if (User::query()->count() > 0) {
				User::truncate();
			}

			return;
		}

		/** @var User|null $admin */
		$admin = User::find(1);
		if ($admin === null) {
			$admin = new User();
			$admin->incrementing = false;
			$admin->id = 1;
			$admin->may_upload = true;
			$admin->may_edit_own_settings = true;
			$admin->may_administrate = true;
			$admin->username = 'admin';
			$admin->password = Hash::make('password');
			$admin->save();

			if (Schema::connection(null)->getConnection()->getDriverName() === 'pgsql' && DB::table('users')->count() > 0) {
				// when using PostgreSQL, the next ID value is kept when inserting without incrementing
				// which results in errors because trying to insert a user with ID = 1.
				// Thus, we need to reset the index to the greatest ID + 1
				/** @var User $lastUser */
				$lastUser = User::query()->orderByDesc('id')->first();
				DB::statement('ALTER SEQUENCE users_id_seq1 RESTART WITH ' . strval($lastUser->id + 1));
			}
		} elseif (!$admin->may_administrate) {
			$admin->may_administrate = true;
			$admin->save();
		}
	}
}