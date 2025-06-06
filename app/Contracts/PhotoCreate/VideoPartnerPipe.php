<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

namespace App\Contracts\PhotoCreate;

use App\DTO\PhotoCreate\VideoPartnerDTO;

/**
 * Basic definition of a Video Partner pipe.
 */
interface VideoPartnerPipe
{
	/**
	 * @param VideoPartnerDTO                                   $state
	 * @param \Closure(VideoPartnerDTO $state): VideoPartnerDTO $next
	 *
	 * @return VideoPartnerDTO
	 */
	public function handle(VideoPartnerDTO $state, \Closure $next): VideoPartnerDTO;
}