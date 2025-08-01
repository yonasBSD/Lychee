<template>
	<div class="h-screen w-screen">
		<img v-if="imgSrc !== ''" alt="image background" class="absolute w-screen h-screen object-cover blur-lg object-center" :src="imgSrc" />
		<div class="w-screen h-screen flex justify-center items-center flex-wrap bg-repeat bg-[url(/img/noise.png)]">
			<img
				v-if="imgSrc !== ''"
				alt="Random Image"
				class="h-[95%] w-[95%] object-contain filter drop-shadow-black"
				:src="imgSrc"
				:srcset="imgSrcset"
			/>
		</div>
		<div id="shutter" class="absolute w-screen h-dvh bg-surface-950 transition-opacity duration-1000 ease-in-out top-0 left-0"></div>
		<div class="absolute top-0 ltr:left-0 rtl:right-0 p-3">
			<GoBack @go-back="goBack" />
		</div>
	</div>
</template>
<script setup lang="ts">
import GoBack from "@/components/headers/GoBack.vue";
import { useSlideshowFunction } from "@/composables/photo/slideshow";
import AlbumService from "@/services/album-service";
import { useLeftMenuStateStore } from "@/stores/LeftMenuState";
import { onKeyStroke } from "@vueuse/core";
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";

const props = defineProps<{
	albumId?: string;
}>();

const router = useRouter();
const imgSrc = ref("");
const imgSrcset = ref("");
const refreshTimeout = ref(5);
const leftMenuStore = useLeftMenuStateStore();

const is_slideshow_active = ref(false);

function getNext() {
	AlbumService.frame(props.albumId ?? null).then((response) => {
		imgSrc.value = response.data.src;
		imgSrcset.value = response.data.srcset;
	});
}

const { slideshow, clearTimeouts } = useSlideshowFunction(1000, is_slideshow_active, refreshTimeout, ref(null), getNext, undefined);

function start() {
	AlbumService.frame(props.albumId ?? null).then((response) => {
		refreshTimeout.value = response.data.timeout;
		getNext();
		slideshow();
	});
}

onMounted(() => {
	leftMenuStore.left_menu_open = false;
	const elem = document.getElementsByTagName("body")[0];

	elem.requestFullscreen()
		.then(() => {})
		.catch((err) => console.log(err));

	start();
});

onUnmounted(() => {
	try {
		document.exitFullscreen();
	} catch (_) {
		// Do nothing.
	}
	clearTimeouts();
});

function goBack() {
	clearTimeouts();
	try {
		document.exitFullscreen();
	} catch (_) {
		// Do nothing.
	}

	if (props.albumId !== undefined && props.albumId !== "") {
		router.push({ name: "album", params: { albumId: props.albumId } });
	} else {
		router.push({ name: "gallery" });
	}
}

onKeyStroke("Escape", () => {
	goBack();
});

onMounted(() => {
	document.documentElement.requestFullscreen();
});
</script>
