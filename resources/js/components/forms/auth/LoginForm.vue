<template>
	<form v-focustrap class="flex flex-col gap-4 relative max-w-md w-full text-sm rounded-md pt-9">
		<div class="flex justify-center gap-2">
			<a
				class="inline-block text-xl text-muted-color transition-all duration-300 hover:text-primary-400 hover:scale-150 cursor-pointer"
				@click="openWebAuthn"
				title="WebAuthn"
			>
				<i class="fa-solid fa-fingerprint" />
			</a>
			<template v-if="oauths !== undefined">
				<a
					v-for="oauth in oauths"
					:href="oauth.url"
					class="inline-block text-xl text-muted-color hover:scale-125 transition-all cursor-pointer hover:text-primary-400 mb-6"
					:title="oauth.provider"
				>
					<i class="items-center" :class="oauth.icon"></i>
				</a>
			</template>
		</div>
		<div class="inline-flex flex-col gap-2" :class="props.padding ?? 'px-9'">
			<FloatLabel variant="on">
				<InputText id="username" v-model="username" autocomplete="username" :autofocus="true" />
				<label for="username">{{ $t("dialogs.login.username") }}</label>
			</FloatLabel>
		</div>
		<div class="inline-flex flex-col gap-2" :class="props.padding ?? 'px-9'">
			<FloatLabel variant="on">
				<InputPassword id="password" v-model="password" @keydown.enter="login" autocomplete="current-password" />
				<label for="password">{{ $t("dialogs.login.password") }}</label>
			</FloatLabel>
			<Message v-if="invalidPassword" severity="error">{{ $t("dialogs.login.unknown_invalid") }}</Message>
		</div>
		<div class="text-muted-color text-right font-semibold" :class="props.padding ?? 'px-9'">
			Lychee <span class="text-primary-500" v-if="is_se_enabled">SE</span>
		</div>
		<div class="flex items-center mt-9">
			<Button
				v-if="closeCallback !== undefined"
				@click="props.closeCallback"
				severity="secondary"
				class="w-full font-bold border-none rounded-none ltr:rounded-bl-xl rtl:rounded-br-xl shrink"
			>
				{{ $t("dialogs.button.cancel") }}
			</Button>
			<Button
				@click="login"
				severity="contrast"
				:class="{
					'w-full font-bold border-none shrink': true,
					'rounded-none ltr:rounded-br-xl rtl:rounded-bl-xl': closeCallback !== undefined,
					'rounded-xl': closeCallback === undefined,
				}"
			>
				{{ $t("dialogs.login.signin") }}
			</Button>
		</div>
	</form>
</template>
<script setup lang="ts">
import { ref } from "vue";
import FloatLabel from "primevue/floatlabel";
import Button from "primevue/button";
import Message from "primevue/message";
import AuthService from "@/services/auth-service";
import InputText from "@/components/forms/basic/InputText.vue";
import InputPassword from "@/components/forms/basic/InputPassword.vue";
import { useAuthStore } from "@/stores/Auth";
import AlbumService from "@/services/album-service";
import { useLycheeStateStore } from "@/stores/LycheeState";
import { storeToRefs } from "pinia";
import { useTogglablesStateStore } from "@/stores/ModalsState";
import { onMounted } from "vue";

const emits = defineEmits<{
	"logged-in": [];
}>();

type OauthProvider = {
	url: string;
	icon: string;
	provider: App.Enum.OauthProvidersType;
};

const props = defineProps<{
	closeCallback?: () => void;
	padding?: string;
}>();

const username = ref("");
const password = ref("");
const authStore = useAuthStore();
const togglableStore = useTogglablesStateStore();
const lycheeStore = useLycheeStateStore();
const { is_se_enabled } = storeToRefs(lycheeStore);
const { is_login_open, is_webauthn_open } = storeToRefs(togglableStore);
const invalidPassword = ref(false);

const oauths = ref<OauthProvider[] | undefined>(undefined);

function login() {
	AuthService.login(username.value, password.value)
		.then(() => {
			is_login_open.value = false;
			authStore.setUser(null);
			invalidPassword.value = false;
			AlbumService.clearCache();
			emits("logged-in");
		})
		.catch((e: any) => {
			if (e.response && e.response.status === 401) {
				invalidPassword.value = true;
			}
		});
}

function openWebAuthn() {
	is_login_open.value = false;
	is_webauthn_open.value = true;
	username.value = "";
	password.value = "";
	invalidPassword.value = false;
}

onMounted(() => {
	authStore.getOauthData().then((data) => {
		oauths.value = data;
	});
});
</script>
