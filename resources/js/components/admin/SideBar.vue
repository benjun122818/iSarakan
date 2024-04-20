<template>
    <div class="bg-base-100">
        <div class="z-20 bg-base-200 bg-opacity-90 backdrop-blur sticky top-0 items-center gap-2 px-4 py-2 hidden lg:flex">
            <a href="/dashboard" aria-current="page" aria-label="Homepage" class="flex-0 btn btn-ghost px-2">
                <div class="font-title text-primary inline-flex text-lg transition-all duration-200 md:text-3xl">
                    <span class="text-base-content lowercase">d</span>
                    <span class="uppercase">Finder</span>
                </div>
            </a>
            <a href="#" class="link link-hover font-mono text-xs text-opacity-50">
                <div data-tip="Changelog" class="tooltip tooltip-bottom">
                    | ADMINISTRATOR
                </div>
            </a>
        </div>
        <div class="drawer lg:drawer-open">
            <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
            <div class="drawer-content flex flex-col items-center justify-center">
                <!-- Page content here -->
                <label for="my-drawer-2" class="btn btn-primary drawer-button lg:hidden">Open Selection</label>
            </div>
            <div class="drawer-side">
                <label for="my-drawer-2" class="drawer-overlay"></label>
                <ul class="menu p-4 w-80 bg-base-100 text-base-content">

                    <li>
                        <router-link :to="'/admin/users'" active-class="active"><vue-feather type="user"
                                size="30"></vue-feather>Users</router-link>
                    </li>
                    <li>
                        <router-link :to="'/admin/dorm/index'" active-class="active"><vue-feather type="home"
                                size="30"></vue-feather>Dormitories</router-link>
                    </li>
                    <li>
                        <router-link :to="'/admin/subscribers'" active-class="active"><vue-feather type="list"
                                size="30"></vue-feather>Subscriber</router-link>
                    </li>
                    <!--  <li>
                        <router-link :to="'/admin/transaction-type'" active-class="active"><vue-feather type="archive"
                                size="30"></vue-feather>Transaction Type</router-link>
                    </li> -->
                </ul>
            </div>
        </div>
    </div>
</template>
<script>
//import UnitTable from "./UnitTable.vue";
export default {
    data() {
        return {};
    },
    mounted() { },
    methods: {},

    components: {
        // UnitTable,
    },
    computed: {},
};
</script>

<style lang=""></style>
