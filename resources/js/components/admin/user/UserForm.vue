<template>
    <div class="p-0 w-full">
        <div class="card w-full bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">User Form</h2>
                <div class="alert alert-error shadow-lg" v-if="errors.length > 0">
                    <div class="flex-row">
                        <ul>
                            <li>
                                <strong>Whoops, looks like something went
                                    wrong...</strong>
                            </li>
                            <li v-for="error in errors">{{ error }}</li>
                        </ul>
                    </div>
                </div>
                <form @submit="createuser" id="form_add_user">
                    <div class="grid gap-4 grid-cols-2">
                        <div class="form-control w-full col-span-2">
                            <label class="label">
                                <span class="label-text">User role</span>
                            </label>
                            <select class="select select-bordered" v-model="user.role">
                                <option disabled selected>Pick one</option>
                                <option value="2">Dorm maneger</option>
                                <option value="3">Tenant</option>
                                <option value="1">Admin</option>
                            </select>
                        </div>

                        <div class="form-control w-full col-span-2">
                            <label class="label">
                                <span class="label-text">Name</span>
                            </label>
                            <input type="text" placeholder="Type here" class="input input-sm input-bordered w-full"
                                v-model="user.name" />
                        </div>

                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">User Name</span>
                            </label>
                            <input type="text" placeholder="Type here" class="input input-sm input-bordered w-full"
                                v-model="user.user_name" />
                        </div>
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Email</span>
                            </label>
                            <input type="text" placeholder="Type here" class="input input-sm input-bordered w-full"
                                v-model="user.email" />
                        </div>
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">password</span>
                            </label>
                            <input type="password" placeholder="Type here" class="input input-sm input-bordered w-full"
                                v-model="user.password" />
                        </div>
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Confirm</span>
                            </label>
                            <input type="password" placeholder="Type here" class="input input-sm input-bordered w-full"
                                v-model="user.password_confirmation" />
                        </div>
                    </div>
                    <br />
                    <div class="card-actions">
                        <button class="btn btn-sm btn-primary" type="submit">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import { useToast } from "vue-toastification";
export default {
    data() {
        return {
            user: {
                name: "",
                user_name: "",
                email: "",
                role: "",
                password: "",
                password_confirmation: "",
            },

            offices: [],
            rcs: [],
            errors: [],
        };
    },
    mounted() {

    },
    setup() {
        // Get toast interface
        const toast = useToast();

        return { toast };
    },
    methods: {

        createuser(event) {
            event.preventDefault();
            axios
                .post("/admin/user", {
                    name: this.user.name,
                    user_name: this.user.user_name,
                    email: this.user.email,
                    type: this.user.role,
                    password: this.user.password,
                    password_confirmation: this.user.password_confirmation,
                })
                .then((response) => {
                    //this.hasError = false;

                    this.toast.success(response.data.message, {
                        position: "top-right",
                        timeout: 2000,
                        closeOnClick: true,
                        pauseOnFocusLoss: true,
                        pauseOnHover: true,
                        draggable: true,
                        draggablePercent: 0.6,
                        showCloseButtonOnHover: false,
                        closeButton: "button",
                        icon: true,
                        rtl: false,
                    });
                    this.errors = [];
                    this.clearForm();
                })
                .catch((error) => {
                    this.errors = [];

                    if (error.response.data.errors.name) {
                        this.errors.push(error.response.data.errors.name[0]);
                    }
                    if (error.response.data.errors.user_name) {
                        this.errors.push(
                            error.response.data.errors.user_name[0]
                        );
                    }

                    if (error.response.data.errors.email) {
                        this.errors.push(error.response.data.errors.email[0]);
                    }

                    if (error.response.data.errors.type) {
                        this.errors.push(error.response.data.errors.type[0]);
                    }


                    if (error.response.data.errors.password) {
                        this.errors.push(
                            error.response.data.errors.password[0]
                        );
                    }
                });
        },
        clearForm() {
            this.user.name = "";
            this.user.user_name = "";
            this.user.rc_id = "";
            this.user.email = "";
            this.user.type = "";
            this.user.office_id = "";
            this.user.password = "";
            this.user.password_confirmation = "";
        },

    },

    components: {},
    computed: {},
};
</script>
<style scoped></style>
