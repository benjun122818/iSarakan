<template>
    <div class="flex justify-center">

        <template v-if="user_type.role == 1">
            <AdminSideNav></AdminSideNav>
        </template>
        <template v-if="user_type.role == 2">
            <dormSideNav :user_type="user_type"></dormSideNav>
        </template>

        <div class="flex-grow">
            <headerstyle></headerstyle>
            <div class="p-4">
                <main class="flex flex-col justify-center items-center">
                    <router-view :user_type="user_type"></router-view>
                </main>
            </div>
        </div>
    </div>
    <footer class="footer footer-center p-4 bg-base-200 bg-opacity-90 backdrop-blur text-base-content">
        <div class="justify-center">
            <p>
                Copyright © 2023 - All right reserved by
                <a href="https://www.mmsu.edu.ph/" class="link link-hover link-primary">MMSU</a>
            </p>
        </div>
    </footer>
</template>
<script>
import AdminSideNav from "../admin/SideBar.vue";
import dormSideNav from "../dormitory/SideBar.vue";
// import OfficeSideNav from "../office/SideBar.vue";
// import AcctgSideNav from "../accounting/SideBar.vue";
// import CashSideNav from "../cashier/SideBar.vue";
//import { mapState, mapActions } from "vuex";
export default {
    data() {
        return {
            user_type: []
        };
    },
    mounted() {
        this.getUsr();

        // alert('erick');
    },
    methods: {
        getUsr() {
            axios.get("/get-user-type")
                .then(({ data }) => {
                    this.user_type = data;

                });
        },
        redirectPages() {
            //  alert(this.user_type.role);
            if (this.user_type.role == 2) {
                this.$router.push({
                    path: `dorm/index`,
                });
            }
        },
    },

    components: {
        AdminSideNav,
        dormSideNav,
        // OfficeSideNav,
        // AcctgSideNav,
        // CashSideNav,
    },
    computed: {

    },
};
</script>

<style scoped></style>
