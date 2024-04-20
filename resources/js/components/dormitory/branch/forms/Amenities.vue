<template>
    <div class="pt-6 w-full">

        <h2 class="card-title">Amenities</h2>

        <div v-if="errors.length > 0">
            <h3 class="font-bold">Oops Something went wrong!</h3>
            <div class="alert alert-error shadow-lg pl-4">
                <ul class="list-disc">
                    <span>
                        <li v-for="error in errors">{{ error }}</li>
                    </span>
                </ul>
            </div>
        </div>
        <form @submit="addAmenities" id="form_add_dorm">
            <!--info-->
            <div class="grid gap-4 grid-cols-2">



                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <input type="text" placeholder="Type here" class="input input-bordered w-full" v-model="ame.name" />
                </div>
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Icon</span>
                    </label>
                    <select class="select select-bordered" v-model="ame.icon">
                        <option disabled selected>Pick one</option>
                        <template v-for="icon in feather_icons">
                            <option :value="icon.des">{{ icon.des }}</option>
                        </template>

                    </select>
                </div>

            </div>

            <!--info-->

            <br />
            <div class="card-actions">
                <button class="btn btn-sm btn-primary" type="submit">
                    Submit
                </button>

            </div>
        </form>
        <br />

        <div class="grid grid-cols-3 gap-4 ">
            <template v-for="a in amenities">
                <div role="alert" class="alert shadow-lg"> <vue-feather :type="a.icon" size="20"> </vue-feather>
                    <span> {{ a.description }}</span>
                    <div>
                        <button class="btn btn-sm" @click="removeAmenities(a.id)"><vue-feather type="x-circle" size="20">
                            </vue-feather></button>

                    </div>
                </div>
            </template>
        </div>

    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import { useToast } from "vue-toastification";
export default {
    data() {
        return {
            ame: {
                name: "",
                icon: "",

            },

            formstate: null,
            amenities: [],
            feather_icons: [],

            rcs: [],
            errors: [],
        };
    },
    mounted() {
        this.formstate = this.$route.params.state;
        this.getFeatherIcon();
        this.getAmenities();
    },
    setup() {
        // Get toast interface
        const toast = useToast();

        return { toast };
    },
    methods: {

        addAmenities(event) {
            event.preventDefault();
            var id = this.$route.params.id;

            axios
                .post("/form/dorm-amenities-create", {
                    dorm_branch_id: id,
                    name: this.ame.name,
                    icon: this.ame.icon,

                })
                .then((response) => {
                    //this.hasError = false;
                    if (response.data.statcode == 1) {
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
                        this.getAmenities()();
                    }
                    else {
                        Swal.fire({
                            title: "Oops!",
                            text: response.data.message,
                            icon: "error",
                        });
                    }
                })
                .catch((error) => {
                    this.errors = [];

                    if (error.response.data.errors.name) {
                        this.errors.push(error.response.data.errors.name[0]);
                    }
                    if (error.response.data.errors.description) {
                        this.errors.push(
                            error.response.data.errors.description[0]
                        );
                    }

                    if (error.response.data.errors.contact) {
                        this.errors.push(error.response.data.errors.contact[0]);
                    }


                });
        },
        removeAmenities(id) {

            axios
                .post("/form/dorm-amenities-remove", {
                    branch_id: id,


                })
                .then((response) => {
                    //this.hasError = false;
                    if (response.data.statcode == 1) {
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
                        this.getAmenities()();
                    }
                    else {
                        Swal.fire({
                            title: "Oops!",
                            text: response.data.message,
                            icon: "error",
                        });
                    }
                })
                .catch((error) => {
                    this.errors = [];

                    if (error.response.data.errors.name) {
                        this.errors.push(error.response.data.errors.name[0]);
                    }
                    if (error.response.data.errors.description) {
                        this.errors.push(
                            error.response.data.errors.description[0]
                        );
                    }

                    if (error.response.data.errors.contact) {
                        this.errors.push(error.response.data.errors.contact[0]);
                    }


                });
        },
        getAmenities() {
            var id = this.$route.params.id;

            axios.get(`/form/dorm-amenities-get/${id}`)
                .then(({ data }) => {
                    this.amenities = data;

                });
        },
        getFeatherIcon() {
            // `/form/common-refregion?page=${this.tbl.page}`
            axios.get(`/get-feather-icons`)
                .then(({ data }) => {
                    this.feather_icons = data;

                });
        },
        getRefprovince() {
            // `/form/common-refprovince?page=${this.tbl.page}`
            this.refprovince = [];
            this.refcitymun = [];
            this.refbrgy = [];

            this.dorm.prov = "";
            this.dorm.citymuni = "";
            this.dorm.brgy = "";

            axios.get(`/form/common-refprovince?regCode=${this.dorm.region}`)
                .then(({ data }) => {
                    this.refprovince = data;

                });
        },
        getRefcitymun() {
            // `/form/common-refprovince?page=${this.tbl.page}`
            this.refbrgy = [];
            this.dorm.brgy = "";
            this.dorm.citymuni = "";
            axios.get(`/form/common-refcitymun?provCode=${this.dorm.prov}`)
                .then(({ data }) => {
                    this.refcitymun = data;

                });
        },
        getRefbrgy() {
            // `/form/common-refprovince?page=${this.tbl.page}`
            this.dorm.brgy = "";
            axios.get(`/form/common-refbrgy?citymunCode=${this.dorm.citymuni}`)
                .then(({ data }) => {
                    this.refbrgy = data;

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
