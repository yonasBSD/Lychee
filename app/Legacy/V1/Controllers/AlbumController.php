<?php

namespace App\Legacy\V1\Controllers;

use App\Actions\Album\Archive;
use App\Actions\Album\Create;
use App\Actions\Album\CreateTagAlbum;
use App\Actions\Album\Delete;
use App\Actions\Album\Merge;
use App\Actions\Album\Move;
use App\Actions\Album\PositionData;
use App\Actions\Album\SetProtectionPolicy;
use App\Actions\Album\Unlock;
use App\Contracts\Exceptions\LycheeException;
use App\Exceptions\Internal\LycheeLogicException;
use App\Exceptions\MediaFileOperationException;
use App\Exceptions\ModelDBException;
use App\Exceptions\UnauthenticatedException;
use App\Http\Resources\Collections\PositionDataResource;
use App\Legacy\V1\Requests\Album\AddAlbumRequest;
use App\Legacy\V1\Requests\Album\AddTagAlbumRequest;
use App\Legacy\V1\Requests\Album\ArchiveAlbumsRequest;
use App\Legacy\V1\Requests\Album\DeleteAlbumsRequest;
use App\Legacy\V1\Requests\Album\DeleteTrackRequest;
use App\Legacy\V1\Requests\Album\GetAlbumPositionDataRequest;
use App\Legacy\V1\Requests\Album\GetAlbumRequest;
use App\Legacy\V1\Requests\Album\MergeAlbumsRequest;
use App\Legacy\V1\Requests\Album\MoveAlbumsRequest;
use App\Legacy\V1\Requests\Album\SetAlbumCopyrightRequest;
use App\Legacy\V1\Requests\Album\SetAlbumCoverRequest;
use App\Legacy\V1\Requests\Album\SetAlbumDescriptionRequest;
use App\Legacy\V1\Requests\Album\SetAlbumHeaderRequest;
use App\Legacy\V1\Requests\Album\SetAlbumLicenseRequest;
use App\Legacy\V1\Requests\Album\SetAlbumNSFWRequest;
use App\Legacy\V1\Requests\Album\SetAlbumProtectionPolicyRequest;
use App\Legacy\V1\Requests\Album\SetAlbumSortingRequest;
use App\Legacy\V1\Requests\Album\SetAlbumsTitleRequest;
use App\Legacy\V1\Requests\Album\SetAlbumTagsRequest;
use App\Legacy\V1\Requests\Album\SetAlbumTrackRequest;
use App\Legacy\V1\Requests\Album\UnlockAlbumRequest;
use App\Legacy\V1\Resources\Models\AlbumResource;
use App\Legacy\V1\Resources\Models\SmartAlbumResource;
use App\Legacy\V1\Resources\Models\TagAlbumResource;
use App\Models\Album;
use App\Models\Extensions\BaseAlbum;
use App\Models\TagAlbum;
use App\SmartAlbums\BaseSmartAlbum;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlbumController extends Controller
{
	/**
	 * Add a new Album.
	 *
	 * @param AddAlbumRequest $request
	 *
	 * @return AlbumResource
	 *
	 * @throws LycheeException
	 */
	public function add(AddAlbumRequest $request): AlbumResource
	{
		/** @var int $ownerId */
		$ownerId = Auth::id() ?? throw new UnauthenticatedException();

		$create = new Create($ownerId);
		$album = $create->create($request->title(), $request->parentAlbum());

		return AlbumResource::make($album)->setStatus(201);
	}

	/**
	 * Add a new album generated by tags.
	 *
	 * @param AddTagAlbumRequest $request
	 * @param CreateTagAlbum     $create
	 *
	 * @return TagAlbumResource
	 *
	 * @throws LycheeException
	 */
	public function addTagAlbum(AddTagAlbumRequest $request, CreateTagAlbum $create): TagAlbumResource
	{
		$tagAlbum = $create->create($request->title(), $request->tags());

		return TagAlbumResource::make($tagAlbum)->setStatus(201);
	}

	/**
	 * Provided an albumID, returns the album.
	 *
	 * @param GetAlbumRequest $request
	 *
	 * @return AlbumResource|TagAlbumResource|SmartAlbumResource
	 */
	public function get(GetAlbumRequest $request): AlbumResource|TagAlbumResource|SmartAlbumResource
	{
		return match (true) {
			$request->album() instanceof BaseSmartAlbum => SmartAlbumResource::make($request->album()),
			$request->album() instanceof TagAlbum => TagAlbumResource::make($request->album()),
			$request->album() instanceof Album => AlbumResource::make($request->album()),
			default => throw new LycheeLogicException('This should not happen'),
		};
	}

	/**
	 * Provided an albumID, returns the album with only map related data.
	 *
	 * @param GetAlbumPositionDataRequest $request
	 * @param PositionData                $positionData
	 *
	 * @return PositionDataResource
	 */
	public function getPositionData(GetAlbumPositionDataRequest $request, PositionData $positionData): PositionDataResource
	{
		return $positionData->get($request->album(), $request->includeSubAlbums());
	}

	/**
	 * Provided the albumID and password, return whether the album can be accessed or not.
	 *
	 * @param UnlockAlbumRequest $request
	 * @param Unlock             $unlock
	 *
	 * @return void
	 *
	 * @throws LycheeException
	 */
	public function unlock(UnlockAlbumRequest $request, Unlock $unlock): void
	{
		$unlock->do($request->album(), $request->password());
	}

	/**
	 * Provided a title and albumIDs, change the title of the albums.
	 *
	 * @param SetAlbumsTitleRequest $request
	 *
	 * @return void
	 *
	 * @throws LycheeException
	 */
	public function setTitle(SetAlbumsTitleRequest $request): void
	{
		/** @var BaseAlbum $album */
		foreach ($request->albums() as $album) {
			$album->title = $request->title();
			$album->save();
		}
	}

	/**
	 * Sets the protection policy of the album.
	 *
	 * @param SetAlbumProtectionPolicyRequest $request
	 * @param SetProtectionPolicy             $setProtectionPolicy
	 *
	 * @return void
	 *
	 * @throws LycheeException
	 */
	public function setProtectionPolicy(SetAlbumProtectionPolicyRequest $request, SetProtectionPolicy $setProtectionPolicy): void
	{
		$setProtectionPolicy->do(
			$request->album(),
			$request->albumProtectionPolicy(),
			$request->isPasswordProvided(),
			$request->password()
		);
	}

	/**
	 * Change the description of the album.
	 *
	 * @param SetAlbumDescriptionRequest $request
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 */
	public function setDescription(SetAlbumDescriptionRequest $request): void
	{
		$request->album()->description = $request->description();
		$request->album()->save();
	}

	/**
	 * Change the copyright of the album.
	 *
	 * @param SetAlbumCopyrightRequest $request
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 */
	public function setCopyright(SetAlbumCopyrightRequest $request): void
	{
		$request->album()->copyright = $request->copyright();
		$request->album()->save();
	}

	/**
	 * Change show tags of the tag album.
	 *
	 * @param SetAlbumTagsRequest $request
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 */
	public function setShowTags(SetAlbumTagsRequest $request): void
	{
		$request->album()->show_tags = $request->tags();
		$request->album()->save();
	}

	/**
	 * Set cover image of the album.
	 *
	 * @param SetAlbumCoverRequest $request
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 */
	public function setCover(SetAlbumCoverRequest $request): void
	{
		$request->album()->cover_id = $request->photo()?->id;
		$request->album()->save();
	}

	/**
	 * Set header image of the album.
	 *
	 * @param SetAlbumHeaderRequest $request
	 *
	 * @return void
	 */
	public function setHeader(SetAlbumHeaderRequest $request): void
	{
		$request->album()->header_id = $request->photo()?->id;
		$request->album()->save();
	}

	/**
	 * Set the license of the Album.
	 *
	 * @param SetAlbumLicenseRequest $request
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 */
	public function setLicense(SetAlbumLicenseRequest $request): void
	{
		$request->album()->license = $request->license();
		$request->album()->save();
	}

	/**
	 * Upload a track for the Album.
	 *
	 * @param SetAlbumTrackRequest $request
	 *
	 * @return void
	 *
	 * @throws MediaFileOperationException
	 * @throws ModelDBException
	 */
	public function setTrack(SetAlbumTrackRequest $request): void
	{
		$request->album()->setTrack($request->file);
	}

	/**
	 * Delete a track from the Album.
	 *
	 * @param DeleteTrackRequest $request
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 */
	public function deleteTrack(DeleteTrackRequest $request): void
	{
		$request->album()->deleteTrack();
	}

	/**
	 * Delete the album and all of its pictures.
	 *
	 * @param DeleteAlbumsRequest $request the request
	 * @param Delete              $delete  the delete action
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 * @throws MediaFileOperationException
	 */
	public function delete(DeleteAlbumsRequest $request, Delete $delete): void
	{
		$fileDeleter = $delete->do($request->albumIDs());
		App::terminating(fn () => $fileDeleter->do());
	}

	/**
	 * Merge albums. The first of the list is the destination of the merge.
	 *
	 * @param MergeAlbumsRequest $request
	 * @param Merge              $merge
	 *
	 * @return void
	 *
	 * @throws LycheeException
	 * @throws ModelNotFoundException
	 */
	public function merge(MergeAlbumsRequest $request, Merge $merge): void
	{
		$merge->do($request->album(), $request->albums());
	}

	/**
	 * Move multiple albums into another album.
	 *
	 * @param MoveAlbumsRequest $request
	 * @param Move              $move
	 *
	 * @return void
	 *
	 * @throws LycheeException
	 * @throws ModelNotFoundException
	 */
	public function move(MoveAlbumsRequest $request, Move $move): void
	{
		$move->do($request->album(), $request->albums());
	}

	/**
	 * Sets whether an album contains sensitive pictures.
	 *
	 * @param SetAlbumNSFWRequest $request
	 *
	 * @return void
	 *
	 * @throws ModelDBException
	 */
	public function setNSFW(SetAlbumNSFWRequest $request): void
	{
		$request->album()->is_nsfw = $request->isNSFW();
		$request->album()->save();
	}

	/**
	 * Define the default sorting type.
	 *
	 * @param SetAlbumSortingRequest $request
	 *
	 * @return void
	 *
	 * @throws LycheeException
	 */
	public function setSorting(SetAlbumSortingRequest $request): void
	{
		$request->album()->photo_sorting = $request->sortingCriterion();
		$request->album()->save();
	}

	/**
	 * Return the archive of the pictures of the album and its sub-albums.
	 *
	 * @param ArchiveAlbumsRequest $request
	 * @param Archive              $archive
	 *
	 * @return StreamedResponse
	 *
	 * @throws LycheeException
	 */
	public function getArchive(ArchiveAlbumsRequest $request, Archive $archive): StreamedResponse
	{
		return $archive->do($request->albums());
	}
}