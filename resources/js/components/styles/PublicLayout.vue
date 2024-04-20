<template>
    <div class="flex justify-center">
        <div class="flex-grow">
            <div class="p-4">
                <main class="flex flex-col justify-center items-center">
                    <router-view :user_type="user_type"></router-view>
                </main>
            </div>
        </div>
    </div>
</template>
<script>

// import budgetSideNav from "../budget/SideBar.vue";
// import OfficeSideNav from "../office/SideBar.vue";
// import AcctgSideNav from "../accounting/SideBar.vue";
// import CashSideNav from "../cashier/SideBar.vue";
//import { mapState, mapActions } from "vuex";
export default {
    data() {
        return {};
    },
    mounted() {

    },
    methods: {
        //vuex
        // ...mapActions(["checkUserType"]),
        //vuex

    },

    components: {

        // budgetSideNav,
        // OfficeSideNav,
        // AcctgSideNav,
        // CashSideNav,
    },
    computed: {

    },
};
</script>

<style scoped></style>
