<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

namespace App\Legacy\V1\Requests\Album;

use App\Contracts\Models\AbstractAlbum;
use App\Http\Requests\BaseApiRequest;
use App\Legacy\V1\Contracts\Http\Requests\HasAlbum;
use App\Legacy\V1\Contracts\Http\Requests\HasPhoto;
use App\Legacy\V1\Contracts\Http\Requests\RequestAttribute;
use App\Legacy\V1\Requests\Traits\HasAlbumTrait;
use App\Legacy\V1\Requests\Traits\HasPhotoTrait;
use App\Legacy\V1\RuleSets\Album\SetAlbumCoverRuleSet;
use App\Models\Album;
use App\Models\Photo;
use App\Policies\AlbumPolicy;
use App\Policies\PhotoPolicy;
use Illuminate\Support\Facades\Gate;

final class SetAlbumCoverRequest extends BaseApiRequest implements HasAlbum, HasPhoto
{
	use HasAlbumTrait;
	use HasPhotoTrait;

	/**
	 * {@inheritDoc}
	 */
	public function authorize(): bool
	{
		return Gate::check(AlbumPolicy::CAN_EDIT, [AbstractAlbum::class, $this->album]) &&
			($this->photo === null || Gate::check(PhotoPolicy::CAN_SEE, $this->photo));
	}

	/**
	 * {@inheritDoc}
	 */
	public function rules(): array
	{
		return SetAlbumCoverRuleSet::rules();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function processValidatedValues(array $values, array $files): void
	{
		/** @var string|null */
		$album_id = $values[RequestAttribute::ALBUM_ID_ATTRIBUTE];

		$this->album = Album::query()->findOrFail($album_id);
		/** @var ?string $photo_id */
		$photo_id = $values[RequestAttribute::PHOTO_ID_ATTRIBUTE];
		$this->photo = $photo_id === null ? null : Photo::query()->findOrFail($photo_id);
	}
}