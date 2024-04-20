<template>
    <div class="p-0 w-full">
        <div class="card w-full bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Data Form Form</h2>
                <div role="alert" class="alert alert-info shadow-lg">
                    <vue-feather type="alert-circle" size="20"></vue-feather>
                    <span>First dorm branch to register is considered as the main branch.</span>
                </div>
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
                <form @submit="createDormBranch" id="form_add_dorm">
                    <!--info-->
                    <div class="card bg-base-100">
                        <div class="card-body">
                            <h2 class="card-title">Info</h2>
                            <div class="grid gap-4 grid-cols-2">

                                <!-- <div class="form-control w-full col-span-2">
                            <label class="label">
                                <span class="label-text">User role</span>
                            </label>
                            <select class="select select-bordered" v-model="user.role">
                                <option disabled selected>Pick one</option>
                                <option value="2">Dorm maneger</option>
                                <option value="3">Tenant</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div> -->

                                <div class="form-control w-full col-span-2">
                                    <label class="label">
                                        <span class="label-text">Name</span>
                                    </label>
                                    <input type="text" placeholder="Type here" class="input input-sm input-bordered w-full"
                                        v-model="dorm.name" />
                                </div>

                                <!-- <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Type</span>
                            </label>
                            <select class="select select-bordered" v-model="dorm.type">
                                <option disabled selected>Pick one</option>
                                <option value="2">Branch</option>
                                <option value="1">Main</option>

                            </select>
                        </div> -->
                                <div class="form-control w-full col-span-2">
                                    <label class="label">
                                        <span class="label-text">Description</span>
                                    </label>
                                    <textarea class="textarea textarea-bordered" id="descripttxtarea"
                                        v-model="dorm.description"></textarea>
                                </div>
                                <div class="form-control w-full col-span-2">
                                    <label class="label">
                                        <span class="label-text">Contact</span>
                                    </label>
                                    <div class="form-control w-full col-span-2">
                                        <p><vue-feather type="alert-circle" size="20"></vue-feather> Contact Example.</p>
                                        <p>Newport Boulevard , 1309 , Manila , Philippines<br>Telephone: +63 6302 908-8600 |
                                            <a title="site" href="https://www.google.com">Official dorm site</a>
                                        </p>
                                    </div>

                                    <textarea class="textarea textarea-bordered" id="contacttextarea"
                                        v-model="dorm.contact"></textarea>
                                </div>
                                <div class="form-control w-full col-span-2">
                                    <label class="label">
                                        <span class="label-text">Dorm Type</span>
                                    </label>
                                    <select class="select select-bordered" v-model="dorm.dtype">
                                        <option disabled selected>Pick one</option>
                                        <template v-for="dt in dorm_type">
                                            <option :value="dt.id">{{ dt.des }}</option>
                                        </template>
                                    </select>
                                </div>

                                <div class="form-control w-full">
                                    <label class="label">
                                        <span class="label-text">Region</span>
                                    </label>
                                    <select class="select select-bordered" v-model="dorm.region" @change="getRefprovince()">
                                        <option disabled selected>Pick one</option>
                                        <template v-for="region in refregion">
                                            <option :value="region.regCode">{{ region.regDesc }}</option>
                                        </template>
                                    </select>
                                </div>
                                <div class="form-control w-full">
                                    <label class="label">
                                        <span class="label-text">Province</span>
                                    </label>
                                    <select class="select select-bordered" v-model="dorm.prov" @change="getRefcitymun()">
                                        <option disabled selected>Pick one</option>
                                        <template v-for="prov in refprovince">
                                            <option :value="prov.provCode">{{ prov.provDesc }}</option>
                                        </template>

                                    </select>
                                </div>
                                <div class="form-control w-full">
                                    <label class="label">
                                        <span class="label-text">City/Municipality</span>
                                    </label>
                                    <select class="select select-bordered" v-model="dorm.citymuni" @change="getRefbrgy()">
                                        <option disabled selected>Pick one</option>
                                        <template v-for="citymun in refcitymun">
                                            <option :value="citymun.citymunCode">{{ citymun.citymunDesc }}</option>
                                        </template>
                                    </select>
                                </div>
                                <div class="form-control w-full">
                                    <label class="label">
                                        <span class="label-text">Barangay</span>
                                    </label>
                                    <select class="select select-bordered" v-model="dorm.brgy">
                                        <option disabled selected>Pick one</option>
                                        <template v-for="brgy in refbrgy">
                                            <option :value="brgy.brgyCode">{{ brgy.brgyDesc }}</option>
                                        </template>

                                    </select>
                                </div>
                                <div class="form-control w-full col-span-2">
                                    <label class="label">
                                        <span class="label-text">Detailed Address</span>
                                    </label>
                                    <textarea class="textarea textarea-bordered" v-model="dorm.address"></textarea>
                                </div>
                            </div>
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
            dorm: {
                name: "",
                type: "",
                dtype: "",
                description: "",
                contact: "",
                region: "",
                prov: "",
                citymuni: "",
                brgy: "",
                address: "",
            },

            formstate: null,
            dorm_type: [],
            refregion: [],
            refprovince: [],
            refcitymun: [],
            refbrgy: [],

            rcs: [],
            errors: [],
        };
    },
    mounted() {
        this.formstate = this.$route.params.state;
        this.getRefregion();
        this.getDormType();
        setTimeout(() => {
            this.iniTinyMCEcontact();
            this.iniTinyMCEdesc();
        }, 300);

    },
    setup() {
        // Get toast interface
        const toast = useToast();

        return { toast };
    },
    methods: {

        createDormBranch(event) {
            event.preventDefault();
            var getContact = tinyMCE.get("contacttextarea").getContent();
            var getDescript = tinyMCE.get("descripttxtarea").getContent();
            // alert(getContact);
            // return;
            axios
                .post("/form/dorm-data-create", {
                    name: this.dorm.name,
                    description: getDescript,
                    contact: getContact,
                    region: this.dorm.region,
                    prov: this.dorm.prov,
                    citymuni: this.dorm.citymuni,
                    brgy: this.dorm.brgy,
                    address: this.dorm.address,
                    dtype: this.dorm.dtype
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
                        //  this.clearForm();
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

                    if (error.response.data.errors.region) {
                        this.errors.push(error.response.data.errors.region[0]);
                    }


                    if (error.response.data.errors.prov) {
                        this.errors.push(
                            error.response.data.errors.prov[0]
                        );
                    }

                    if (error.response.data.errors.citymuni) {
                        this.errors.push(
                            error.response.data.errors.citymuni[0]
                        );
                    }

                    if (error.response.data.errors.brgy) {
                        this.errors.push(
                            error.response.data.errors.brgy[0]
                        );
                    }


                    if (error.response.data.errors.address) {
                        this.errors.push(
                            error.response.data.errors.address[0]
                        );
                    }
                });
        },
        iniTinyMCEdesc() {
            tinymce.init({
                selector: "#descripttxtarea",

                plugins: [
                    "advlist",
                    "autolink",

                    "lists",
                    "link",
                    "image",
                    "charmap",
                    "preview",
                    "anchor",
                    "searchreplace",
                    "visualblocks",

                    "fullscreen",
                    "insertdatetime",
                    "media",
                    "table",
                    "help",
                    "wordcount"
                ],

                toolbar:
                    "undo redo | formatpainter casechange blocks | bold italic backcolor | " +
                    "alignleft aligncenter alignright alignjustify | " +
                    "bullist numlist checklist outdent indent | removeformat | a11ycheck code table help"
            });
        },
        iniTinyMCEcontact() {
            tinymce.init({
                selector: "#contacttextarea",

                plugins: [
                    "advlist",
                    "autolink",

                    "lists",
                    "link",
                    "image",
                    "charmap",
                    "preview",
                    "anchor",
                    "searchreplace",
                    "visualblocks",

                    "fullscreen",
                    "insertdatetime",
                    "media",
                    "table",
                    "help",
                    "wordcount"
                ],

                toolbar:
                    "undo redo | formatpainter casechange blocks | bold italic backcolor | " +
                    "alignleft aligncenter alignright alignjustify | " +
                    "bullist numlist checklist outdent indent | removeformat | a11ycheck code table help"
            });
        },
        getRefregion() {
            // `/form/common-refregion?page=${this.tbl.page}`
            axios.get(`/form/common-refregion`)
                .then(({ data }) => {
                    this.refregion = data;

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
        getDormType() {
            // `/form/common-refprovince?page=${this.tbl.page}`

            axios.get(`/form/dorm-type-get`)
                .then(({ data }) => {
                    this.dorm_type = data;

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
