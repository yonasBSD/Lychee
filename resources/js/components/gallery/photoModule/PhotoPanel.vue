<template>
	<div class="absolute z-20 top-0 left-0 w-full flex h-full overflow-hidden bg-black">
		<PhotoHeader :photo="props.photo" @toggle-slide-show="emits('toggleSlideShow')" @go-back="emits('goBack')" />
		<div class="w-0 flex-auto relative">
			<div class="animate-zoomIn w-full h-full">
				<Transition :name="props.transition">
					<div
						:key="photo.id"
						id="imageview"
						class="absolute top-0 left-0 w-full h-full flex items-center justify-center overflow-hidden"
						@click="emits('rotateOverlay')"
						ref="swipe"
						:class="{
							'pt-14': imageViewMode === ImageViewMode.Pdf && !is_full_screen,
						}"
					>
						<!--  This is a video file: put html5 player -->
						<video
							v-if="imageViewMode == ImageViewMode.Video"
							width="auto"
							height="auto"
							id="image"
							ref="videoElement"
							controls
							class="absolute m-auto w-auto h-auto"
							:class="is_full_screen || is_slideshow_active ? 'max-w-full max-h-full' : 'max-wh-full-56'"
							autobuffer
							:autoplay="lycheeStore.can_autoplay"
						>
							<source :src="props.photo.size_variants.original?.url ?? ''" />
							Your browser does not support the video tag.
						</video>
						<!-- This is a raw file: put a place holder -->
						<embed
							v-if="imageViewMode == ImageViewMode.Pdf"
							id="image"
							alt="pdf"
							:src="props.photo.size_variants.original?.url ?? ''"
							type="application/pdf"
							frameBorder="0"
							scrolling="auto"
							class="absolute m-auto bg-contain bg-center bg-no-repeat"
							height="90%"
							width="100%"
						/>
						<!-- This is a raw file: put a place holder -->
						<img
							v-if="imageViewMode == ImageViewMode.Raw"
							id="image"
							alt="placeholder"
							class="absolute m-auto w-auto h-auto bg-contain bg-center bg-no-repeat"
							:src="getPlaceholderIcon()"
						/>
						<!-- This is a normal image: medium or original -->
						<img
							v-if="imageViewMode == ImageViewMode.Medium"
							id="image"
							alt="medium"
							class="absolute m-auto w-auto h-auto bg-contain bg-center bg-no-repeat"
							:src="props.photo.size_variants.medium?.url ?? ''"
							:class="is_full_screen || is_slideshow_active ? 'max-w-full max-h-full' : 'max-wh-full-56'"
							:srcset="srcSetMedium"
						/>
						<img
							v-if="imageViewMode == ImageViewMode.Original"
							id="image"
							alt="big"
							class="absolute m-auto w-auto h-auto bg-contain bg-center bg-no-repeat"
							:class="is_full_screen || is_slideshow_active ? 'max-w-full max-h-full' : 'max-wh-full-56'"
							:style="style"
							:src="props.photo.size_variants.original?.url ?? ''"
						/>
						<!-- This is a livephoto : medium -->
						<div
							v-if="imageViewMode == ImageViewMode.LivePhotoMedium"
							id="livephoto"
							data-live-photo
							data-proactively-loads-video="true"
							:data-photo-src="photo?.size_variants.medium?.url"
							:data-video-src="photo?.live_photo_url"
							class="absolute m-auto w-auto h-auto"
							:class="is_full_screen || is_slideshow_active ? 'max-w-full max-h-full' : 'max-wh-full-56'"
							:style="style"
						></div>
						<!-- This is a livephoto : full -->
						<div
							v-if="imageViewMode == ImageViewMode.LivePhotoOriginal"
							id="livephoto"
							data-live-photo
							data-proactively-loads-video="true"
							:data-photo-src="photo?.size_variants.original?.url"
							:data-video-src="photo?.live_photo_url"
							class="absolute m-auto w-auto h-auto"
							:class="is_full_screen || is_slideshow_active ? 'max-w-full max-h-full' : 'max-wh-full-56'"
							:style="style"
						></div>

						<!-- <x-gallery.photo.overlay /> -->
					</div>
				</Transition>
			</div>
			<NextPrevious
				v-if="photo.previous_photo_id !== null && !is_slideshow_active"
				:albumId="props.albumId"
				:photoId="photo.previous_photo_id"
				:is_next="false"
				:style="previousStyle"
			/>
			<NextPrevious
				v-if="photo.next_photo_id !== null && !is_slideshow_active"
				:albumId="props.albumId"
				:photoId="photo.next_photo_id"
				:is_next="true"
				:style="nextStyle"
			/>
			<Overlay :photo="photo" v-if="!is_exif_disabled && imageViewMode !== ImageViewMode.Pdf" />
			<Dock
				v-if="photo.rights.can_edit && !is_photo_edit_open"
				:photo="photo"
				:is-narrow-menu="imageViewMode === ImageViewMode.Pdf"
				@toggleStar="emits('toggleStar')"
				@setAlbumHeader="emits('setAlbumHeader')"
				@rotatePhotoCCW="emits('rotatePhotoCCW')"
				@rotatePhotoCW="emits('rotatePhotoCW')"
				@toggleMove="emits('toggleMove')"
				@toggleDelete="emits('toggleDelete')"
			/>
		</div>
		<PhotoDetails v-model:are-details-open="are_details_open" :photo="photo" :is-map-visible="props.isMapVisible" v-if="!is_exif_disabled" />
	</div>
</template>
<script setup lang="ts">
import { ImageViewMode, usePhotoBaseFunction } from "@/composables/photo/basePhoto";
import { useLycheeStateStore } from "@/stores/LycheeState";
import { useTogglablesStateStore } from "@/stores/ModalsState";
import { useImageHelpers } from "@/utils/Helpers";
import { storeToRefs } from "pinia";
import { onMounted, ref } from "vue";
import NextPrevious from "./NextPrevious.vue";
import Overlay from "./Overlay.vue";
import PhotoDetails from "@/components/drawers/PhotoDetails.vue";
import PhotoHeader from "@/components/headers/PhotoHeader.vue";
import Dock from "./Dock.vue";
import { watch } from "vue";
import { useSwipe, type UseSwipeDirection } from "@vueuse/core";
import { shouldIgnoreKeystroke } from "@/utils/keybindings-utils";
import { onUnmounted } from "vue";
import { useDebounceFn } from "@vueuse/core";
import { Transition } from "vue";
import { useLtRorRtL } from "@/utils/Helpers";

const { isLTR } = useLtRorRtL();

const swipe = ref<HTMLElement | null>(null);
const videoElement = ref<HTMLVideoElement | null>(null);

const lycheeStore = useLycheeStateStore();
const togglableStore = useTogglablesStateStore();

const { is_exif_disabled } = storeToRefs(lycheeStore);
const { is_photo_edit_open, is_slideshow_active, are_details_open, is_full_screen } = storeToRefs(togglableStore);

const props = defineProps<{
	albumId: string;
	photo: App.Http.Resources.Models.PhotoResource;
	photos: App.Http.Resources.Models.PhotoResource[];
	isMapVisible: boolean;
	transition: "slide-next" | "slide-previous";
}>();

const photo = ref(props.photo);
const photos = ref(props.photos);

const emits = defineEmits<{
	toggleStar: [];
	setAlbumHeader: [];
	rotatePhotoCCW: [];
	rotatePhotoCW: [];
	toggleMove: [];
	toggleDelete: [];
	updated: [];
	rotateOverlay: [];
	toggleSlideShow: [];
	goBack: [];
	next: [];
	previous: [];
}>();

const { previousStyle, nextStyle, srcSetMedium, style, imageViewMode } = usePhotoBaseFunction(photo, photos, videoElement);
const { getPlaceholderIcon } = useImageHelpers();

// We use debounce to avoid multiple skipping too many pictures in one go via the trackpad
function scrollTo(event: WheelEvent) {
	if (shouldIgnoreKeystroke()) {
		return;
	}

	if (is_photo_edit_open.value) {
		// We do nothing! Otherwise we are switching photos without noticing.
		// especially with trackpads.
		return;
	}

	const delta = Math.sign(event.deltaY);
	if (delta > 0) {
		emits("next");
	} else if (delta < 0) {
		emits("previous");
	}
}
const debouncedScrollTo = useDebounceFn(scrollTo, 10);

onMounted(() => {
	window.addEventListener("wheel", debouncedScrollTo);
});

onUnmounted(() => {
	window.removeEventListener("wheel", debouncedScrollTo);
});

useSwipe(swipe, {
	onSwipe(_e: TouchEvent) {},
	onSwipeEnd(_e: TouchEvent, direction: UseSwipeDirection) {
		if (direction === "left" && isLTR()) {
			emits("next");
		} else if (direction === "right" && isLTR()) {
			emits("previous");
		} else if (direction === "left" && !isLTR()) {
			emits("previous");
		} else if (direction === "right" && !isLTR()) {
			emits("next");
		} else {
			emits("goBack");
		}
	},
});

watch(
	() => props.photo.id,
	() => {
		photo.value = props.photo;
	},
);
</script>

<style lang="css">
[dir="ltr"] {
	--next-enter-from: 5%;
	--next-leave-to: -5%;

	--previous-enter-from: -5%;
	--previous-leave-to: 5%;
}

[dir="rtl"] {
	--next-enter-from: -5%;
	--next-leave-to: 5%;

	--previous-enter-from: 5%;
	--previous-leave-to: -5%;
}
.slide-next-leave-active,
.slide-next-enter-active {
	transition:
		transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1),
		opacity 0.2s cubic-bezier(0.445, 0.05, 0.55, 0.95);
}
.slide-next-enter-from {
	transform: translate(var(--next-enter-from), 0);
	opacity: 0;
}
.slide-next-enter-to,
.slide-next-leave-from {
	transform: translate(0, 0);
	opacity: 1;
}
.slide-next-leave-to {
	transform: translate(var(--next-leave-to), 0);
	opacity: 0;
}

.slide-previous-leave-active,
.slide-previous-enter-active {
	transition:
		transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1),
		opacity 0.2s cubic-bezier(0.445, 0.05, 0.55, 0.95);
}
.slide-previous-enter-from {
	transform: translate(var(--previous-enter-from), 0);
	opacity: 0;
}
.slide-previous-enter-to,
.slide-previous-leave-from {
	transform: translate(0, 0);
	opacity: 1;
}
.slide-previous-leave-to {
	transform: translate(var(--previous-leave-to), 0);
	opacity: 0;
}
</style>
