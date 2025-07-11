<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

namespace App\Models;

use App\Actions\Album\Delete;
use App\DTO\AlbumSortingCriterion;
use App\Enum\AspectRatioType;
use App\Enum\ColumnSortingType;
use App\Enum\LicenseType;
use App\Enum\OrderSortingType;
use App\Enum\TimelineAlbumGranularity;
use App\Exceptions\ConfigurationKeyMissingException;
use App\Exceptions\Internal\QueryBuilderException;
use App\Exceptions\MediaFileOperationException;
use App\Exceptions\ModelDBException;
use App\ModelFunctions\HasAbstractAlbumProperties;
use App\Models\Builders\AlbumBuilder;
use App\Models\Extensions\BaseAlbum;
use App\Models\Extensions\ToArrayThrowsNotImplemented;
use App\Relations\HasAlbumThumb;
use App\Relations\HasManyChildAlbums;
use App\Relations\HasManyChildPhotos;
use App\Relations\HasManyPhotosRecursively;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Kalnoy\Nestedset\Collection as NSCollection;
use Kalnoy\Nestedset\Contracts\Node;
use Kalnoy\Nestedset\DescendantsRelation;
use Kalnoy\Nestedset\NodeTrait;

/**
 * App\Models\Album.
 *
 * @property string                   $id
 * @property string|null              $parent_id
 * @property Album|null               $parent
 * @property Collection<int,Album>    $children
 * @property int                      $num_children             The number of children.
 * @property Collection<int,Photo>    $all_photos
 * @property int                      $num_photos               The number of photos in this album (excluding photos in subalbums).
 * @property LicenseType              $license
 * @property string|null              $cover_id
 * @property Photo|null               $cover
 * @property string|null              $header_id
 * @property Photo|null               $header
 * @property string|null              $track_short_path
 * @property string|null              $track_url
 * @property AspectRatioType|null     $album_thumb_aspect_ratio
 * @property TimelineAlbumGranularity $album_timeline
 * @property int                      $_lft
 * @property int                      $_rgt
 * @property BaseAlbumImpl            $base_class
 * @property User|null                $owner
 * @property bool                     $is_recursive_nsfw        /!\ This attribute is not loaded by default.
 *
 * @method static AlbumBuilder|Album query()                       Begin querying the model.
 * @method static AlbumBuilder|Album with(array|string $relations) Begin querying the model with eager loading.
 * @method        AlbumBuilder|Album newModelQuery()               Get a new, "pure" query builder for the model's table without any scopes, eager loading, etc.
 * @method        AlbumBuilder|Album newQuery()                    Get a new query builder for the model's table.
 *
 * @property Collection<int,AccessPermission> $access_permissions
 * @property int|null                         $access_permissions_count
 * @property AccessPermission|null            $current_user_permissions
 * @property AccessPermission|null            $public_permissions
 * @property Collection<int,User>             $shared_with
 * @property int|null                         $shared_with_count
 *
 * @method static AlbumBuilder|Album  addSelect($column)
 * @method static NSCollection<Album> all($columns = ['*'])
 * @method static AlbumBuilder|Album  ancestorsAndSelf($id, array $columns = [])
 * @method static AlbumBuilder|Album  ancestorsOf($id, array $columns = [])
 * @method static AlbumBuilder|Album  applyNestedSetScope(?string $table = null)
 * @method static AlbumBuilder|Album  countErrors()
 * @method static AlbumBuilder|Album  d()
 * @method static AlbumBuilder|Album  defaultOrder(string $dir = 'asc')
 * @method static AlbumBuilder|Album  descendantsAndSelf($id, array $columns = [])
 * @method static AlbumBuilder|Album  descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static AlbumBuilder|Album  fixSubtree($root)
 * @method static AlbumBuilder|Album  fixTree($root = null)
 * @method static NSCollection<Album> get($columns = ['*'])
 * @method static AlbumBuilder|Album  getNodeData($id, $required = false)
 * @method static AlbumBuilder|Album  getPlainNodeData($id, $required = false)
 * @method static AlbumBuilder|Album  getTotalErrors()
 * @method static AlbumBuilder|Album  hasChildren()
 * @method static AlbumBuilder|Album  hasParent()
 * @method static AlbumBuilder|Album  isBroken()
 * @method static AlbumBuilder|Album  join(string $table, string $first, string $operator = null, string $second = null, string $type = 'inner', string $where = false)
 * @method static AlbumBuilder|Album  joinSub($query, $as, $first, $operator = null, $second = null, $type = 'inner', $where = false)
 * @method static AlbumBuilder|Album  leaves(array $columns = [])
 * @method static AlbumBuilder|Album  leftJoin(string $table, string $first, string $operator = null, string $second = null)
 * @method static AlbumBuilder|Album  makeGap(int $cut, int $height)
 * @method static AlbumBuilder|Album  moveNode($key, $position)
 * @method static AlbumBuilder|Album  orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static AlbumBuilder|Album  orWhereDescendantOf($id)
 * @method static AlbumBuilder|Album  orWhereNodeBetween($values)
 * @method static AlbumBuilder|Album  orWhereNotDescendantOf($id)
 * @method static AlbumBuilder|Album  orderBy($column, $direction = 'asc')
 * @method static AlbumBuilder|Album  rebuildSubtree($root, array $data, $delete = false)
 * @method static AlbumBuilder|Album  rebuildTree(array $data, $delete = false, $root = null)
 * @method static AlbumBuilder|Album  reversed()
 * @method static AlbumBuilder|Album  root(array $columns = [])
 * @method static AlbumBuilder|Album  select($columns = [])
 * @method static AlbumBuilder|Album  whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static AlbumBuilder|Album  whereAncestorOrSelf($id)
 * @method static AlbumBuilder|Album  whereCoverId($value)
 * @method static AlbumBuilder|Album  whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static AlbumBuilder|Album  whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static AlbumBuilder|Album  whereId($value)
 * @method static AlbumBuilder|Album  whereIn(string $column, string $values, string $boolean = 'and', string $not = false)
 * @method static AlbumBuilder|Album  whereIsAfter($id, $boolean = 'and')
 * @method static AlbumBuilder|Album  whereIsBefore($id, $boolean = 'and')
 * @method static AlbumBuilder|Album  whereIsLeaf()
 * @method static AlbumBuilder|Album  whereIsRoot()
 * @method static AlbumBuilder|Album  whereLft($value)
 * @method static AlbumBuilder|Album  whereLicense($value)
 * @method static AlbumBuilder|Album  whereNodeBetween($values, $boolean = 'and', $not = false)
 * @method static AlbumBuilder|Album  whereNotDescendantOf($id)
 * @method static AlbumBuilder|Album  whereNotIn(string $column, string $values, string $boolean = 'and')
 * @method static AlbumBuilder|Album  whereParentId($value)
 * @method static AlbumBuilder|Album  whereRgt($value)
 * @method static AlbumBuilder|Album  whereTrackShortPath($value)
 * @method static AlbumBuilder|Album  withDepth(string $as = 'depth')
 * @method static AlbumBuilder|Album  withoutRoot()
 *
 * @implements Node<Album>
 */
class Album extends BaseAlbum implements Node
{
	/** @phpstan-use NodeTrait<Album,string> */
	use NodeTrait;
	use ToArrayThrowsNotImplemented;
	/** @phpstan-use HasFactory<\Database\Factories\AlbumFactory> */
	use HasFactory;
	use HasAbstractAlbumProperties;

	/**
	 * The model's attributes.
	 *
	 * We must list all attributes explicitly here, otherwise the attributes
	 * of a new model will accidentally be set on the parent class.
	 * The trait {@link \App\Models\Extensions\ForwardsToParentImplementation}
	 * only works properly, if it knows which attributes belong to the parent
	 * class and which attributes belong to the child class.
	 *
	 * @var array<string, mixed>
	 */
	protected $attributes = [
		'id' => null,
		'parent_id' => null,
		'album_timeline' => null,
		'license' => 'none',
		'cover_id' => null,
		'header_id' => null,
		'album_thumb_aspect_ratio' => null,
		'_lft' => null,
		'_rgt' => null,
		'album_sorting_col' => null,
		'album_sorting_order' => null,
	];

	/**
	 * @var array<string, string>
	 */
	protected $casts = [
		'min_taken_at' => 'datetime',
		'max_taken_at' => 'datetime',
		'num_children' => 'integer',
		'num_photos' => 'integer',
		'is_recursive_nsfw' => 'boolean',
		'album_thumb_aspect_ratio' => AspectRatioType::class,
		'album_timeline' => TimelineAlbumGranularity::class,
		'_lft' => 'integer',
		'_rgt' => 'integer',
	];

	/**
	 * The relationships that should always be eagerly loaded by default.
	 */
	protected $with = ['cover', 'cover.size_variants', 'thumb'];

	/**
	 * Return the relationship between this album and photos which are
	 * direct children of this album.
	 *
	 * @phpstan-ignore method.childReturnType, method.childReturnType
	 */
	public function photos(): HasManyChildPhotos
	{
		return new HasManyChildPhotos($this);
	}

	/**
	 * Returns the relationship between this album and all photos incl.
	 * photos which are recursive children of this album.
	 */
	public function all_photos(): HasManyPhotosRecursively
	{
		return new HasManyPhotosRecursively($this);
	}

	public function thumb(): HasAlbumThumb
	{
		return new HasAlbumThumb($this);
	}

	/**
	 * Return the relationship between an album and its sub-albums.
	 */
	public function children(): HasManyChildAlbums
	{
		return new HasManyChildAlbums($this);
	}

	/**
	 * Get query for descendants of the node.
	 *
	 * @return DescendantsRelation<Album>
	 *
	 * @throws QueryBuilderException
	 */
	public function descendants(): DescendantsRelation
	{
		try {
			/** @var DescendantsRelation<Album> */
			return new DescendantsRelation($this->newQuery(), $this);
			// @codeCoverageIgnoreStart
		} catch (\Throwable $e) {
			throw new QueryBuilderException($e);
		}
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Return the relationship between an album and its cover.
	 *
	 * @return HasOne<Photo,$this>
	 */
	public function cover(): HasOne
	{
		return $this->hasOne(Photo::class, 'id', 'cover_id');
	}

	/**
	 * Return the relationship between an album and its header.
	 *
	 * @return HasOne<Photo,$this>
	 */
	public function header(): HasOne
	{
		return $this->hasOne(Photo::class, 'id', 'header_id');
	}

	/**
	 * Return the License used by the album.
	 *
	 * @throws ConfigurationKeyMissingException
	 */
	protected function getLicenseAttribute(string|LicenseType|null $value): LicenseType
	{
		if ($value === null || $value === 'none' || $value === LicenseType::NONE) {
			return Configs::getValueAsEnum('default_license', LicenseType::class);
		}

		if (is_string($value)) {
			return LicenseType::from($value);
		}

		return $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws ModelDBException
	 * @throws MediaFileOperationException
	 */
	public function performDeleteOnModel(): void
	{
		$file_deleter = (new Delete())->do([$this->id]);
		$this->exists = false;
		$file_deleter->do();
	}

	/**
	 * This method is a no-op.
	 *
	 * This method is originally defined by {@link NodeTrait::deleteDescendants()}
	 * and called as part of the event listener for the 'deleting' event.
	 * The event listener is installed by {@link NodeTrait::bootNodeTrait()}.
	 *
	 * For efficiency reasons all descendants are deleted by
	 * {@link Delete::do()}.
	 * Hence, we must avoid any attempt to delete the descendants twice.
	 *
	 * @codeCoverageIgnore
	 */
	protected function deleteDescendants(): void
	{
		// deliberately a no op
	}

	/**
	 * Sets the ownership of all child albums and child photos to the owner
	 * of this album.
	 *
	 * ANSI SQL does not allow a `JOIN`-clause in the table reference
	 * of `UPDATE` statements.
	 * MySQL and PostgreSQL have their proprietary but different
	 * extension for that, SQLite does not support it at all.
	 * Hence, we must use a (slightly) less efficient, but
	 * SQL-compatible `WHERE EXIST` condition instead of a `JOIN`.
	 * This also means that we cannot use the succinct statements
	 *
	 *     $this->descendants()->update(['owner_id' => $this->owner_id])
	 *     $this->all_photos()->update(['owner_id' => $this->owner_id])
	 *
	 * because these method return queries which use `JOINS`.
	 * So, we need to build the queries from scratch.
	 */
	public function fixOwnershipOfChildren(): void
	{
		$this->refreshNode();
		$lft = $this->_lft;
		$rgt = $this->_rgt;

		BaseAlbumImpl::query()
			->whereExists(function (BaseBuilder $q) use ($lft, $rgt): void {
				$q
					->from('albums')
					->whereColumn('base_albums.id', '=', 'albums.id')
					->whereBetween('albums._lft', [$lft + 1, $rgt - 1]);
			})
			->update(['owner_id' => $this->owner_id]);
		// TODO: FIX ME This is no longer correct.
		// Photo::query()
		// 	->whereExists(function (BaseBuilder $q) use ($lft, $rgt): void {
		// 		$q
		// 			->from('albums')
		// 			->whereColumn('photos.album_id', '=', 'albums.id') // ! column no longer exists!
		// 			->whereBetween('albums._lft', [$lft, $rgt]);
		// 	})
		// 	->update(['owner_id' => $this->owner_id]);
	}

	/**
	 * Create a new Eloquent query builder for the model.
	 *
	 * @param BaseBuilder $query
	 */
	public function newEloquentBuilder($query): AlbumBuilder
	{
		return new AlbumBuilder($query);
	}

	/**
	 * Defines accessor for the Aspect Ratio.
	 */
	protected function getAlbumThumbAspectRatioAttribute(): ?AspectRatioType
	{
		return AspectRatioType::tryFrom($this->attributes['album_thumb_aspect_ratio'] ?? '1/1');
	}

	/**
	 * Defines setter for Aspect Ratio.
	 */
	protected function setAlbumThumbAspectRatioAttribute(?AspectRatioType $aspect_ratio): void
	{
		$this->attributes['album_thumb_aspect_ratio'] = $aspect_ratio?->value;
	}

	/**
	 * Defines accessor for the Album Timeline.
	 */
	protected function getAlbumTimelineAttribute(): ?TimelineAlbumGranularity
	{
		return TimelineAlbumGranularity::tryFrom($this->attributes['album_timeline']);
	}

	/**
	 * Defines setter for Album Timeline.
	 */
	protected function setAlbumTimelineAttribute(?TimelineAlbumGranularity $album_timeline): void
	{
		$this->attributes['album_timeline'] = $album_timeline?->value;
	}

	/**
	 * Accessor for the "virtual" attribute {@link Album::$track_url}.
	 *
	 * This is a convenient method which wraps
	 * {@link Album::$track_short_path} into
	 * {@link \Illuminate\Support\Facades\Storage::url()}.
	 *
	 * @return string|null the url of the track
	 */
	public function getTrackUrlAttribute(): ?string
	{
		return $this->track_short_path !== null && $this->track_short_path !== '' ?
			Storage::url($this->track_short_path) : null;
	}

	/**
	 * Set the GPX track for the album.
	 *
	 * @param UploadedFile $file the GPX track file to be set
	 *
	 * @throws ModelDBException
	 * @throws MediaFileOperationException
	 *
	 * @codeCoverageIgnore tested Locally
	 */
	public function setTrack(UploadedFile $file): void
	{
		try {
			if ($this->track_short_path !== null) {
				Storage::delete($this->track_short_path);
			}

			$new_track_name = strtr(base64_encode(random_bytes(18)), '+/', '-_') . '.xml';
			Storage::putFileAs('tracks/', $file, $new_track_name);
			$this->track_short_path = 'tracks/' . $new_track_name;
			$this->save();
		} catch (ModelDBException $e) {
			throw $e;
		} catch (\Exception $e) {
			throw new MediaFileOperationException('Could not save track file', $e);
		}
	}

	/**
	 * Delete the track of the album.
	 *
	 * @throws ModelDBException
	 *
	 * @codeCoverageIgnore tested Locally
	 */
	public function deleteTrack(): void
	{
		if ($this->track_short_path === null) {
			return;
		}
		Storage::delete($this->track_short_path);
		$this->track_short_path = null;
		$this->save();
	}

	protected function getAlbumSortingAttribute(): ?AlbumSortingCriterion
	{
		$sorting_column = $this->attributes['album_sorting_col'];
		$sorting_order = $this->attributes['album_sorting_order'];

		return ($sorting_column === null || $sorting_order === null) ?
			null :
			new AlbumSortingCriterion(
				ColumnSortingType::from($sorting_column),
				OrderSortingType::from($sorting_order));
	}

	protected function setAlbumSortingAttribute(?AlbumSortingCriterion $sorting): void
	{
		$this->attributes['album_sorting_col'] = $sorting?->column->value;
		$this->attributes['album_sorting_order'] = $sorting?->order->value;
	}

	/**
	 * Returns the criterion acc. to which **albums** inside the album shall be sorted.
	 */
	public function getEffectiveAlbumSorting(): AlbumSortingCriterion
	{
		return $this->getAlbumSortingAttribute() ?? AlbumSortingCriterion::createDefault();
	}
}