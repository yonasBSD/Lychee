<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

namespace App\Http\Controllers;

use App\Events\Metrics\PhotoFavourite;
use App\Events\Metrics\PhotoVisit;
use App\Http\Requests\Metrics\PhotoMetricsRequest;
use App\Models\Configs;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * This is a Metrics Controller.
 * Most of the call here do not return anything.
 */
class MetricsController extends Controller
{
	/**
	 * This method is called when a photo is visited.
	 */
	public function photo(PhotoMetricsRequest $request): void
	{
		PhotoVisit::dispatchIf(self::shouldMeasure(), $request->visitorId(), $request->photoIds()[0]);

		return;
	}

	/**
	 * This method is called when a photo is marked as favourited.
	 *
	 * Note that it is impossible to know if a photo has been removed from favourites.
	 * This is because the data is stored client-side, as a result, we do not know if
	 * the user is e.g. in incognito mode...
	 */
	public function favourite(PhotoMetricsRequest $request): void
	{
		PhotoFavourite::dispatchIf(self::shouldMeasure(), $request->visitorId(), $request->photoIds()[0]);

		return;
	}

	/**
	 * Determine whether we should apply measurements or not.
	 */
	public static function shouldMeasure(): bool
	{
		if (Configs::getValueAsBool('metrics_enabled') === false && Configs::getValueAsBool('live_metrics_enabled') === false) {
			return false;
		}

		if (Auth::guest()) {
			return true;
		}

		if (Auth::user()->may_administrate) {
			return false;
		}

		return Configs::getValueAsBool('metrics_logged_in_users_enabed');
	}
}
