require("./bootstrap");

require("alpinejs");

window.Vue = require("vue").default;

import { createApp } from "vue";
import { routes } from "./routes";
import { createRouter, createWebHistory } from "vue-router";
import MainLayout from "./components/styles/MainLayout";
import PubLayout from "./components/styles/PublicLayout";

import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

import Loading from "vue-loading-overlay";
import "vue-loading-overlay/dist/vue-loading.css";

import SimpleTypeahead from "vue3-simple-typeahead";
import "vue3-simple-typeahead/dist/vue3-simple-typeahead.css"; //Optional default
//window.SimpleTypeahead = require("vue3-simple-typeahead");
import VueFeather from "vue-feather";
import Toast from "vue-toastification";
// Import the CSS or use your own!
import "vue-toastification/dist/index.css";

import VueSuggestion from "vue-suggestion";

import vSelect from "vue-select";
const options = {
    // You can set your default options here
};
const Swal = require("sweetalert2");

const router = createRouter({
    history: createWebHistory(),
    routes: routes,
});

const app = createApp({});
app.component("main-layout", MainLayout);
app.component("pub-layout", PubLayout);
app.component("headerstyle", require("./components/styles/Header.vue").default);
app.component("Datepicker", Datepicker);
app.component(VueFeather.name, VueFeather);
app.component("Loading", Loading);
app.component("SimpleTypeahead", SimpleTypeahead);
app.component("VueSuggestion", VueSuggestion);
app.component("vSelect", vSelect);
app.use(VueSuggestion);
app.use(Toast, options);
app.use(SimpleTypeahead);

app.use(router);

app.mount("#app");
