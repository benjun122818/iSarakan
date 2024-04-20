<template>
    <div class="p-0 w-full">
        <div class="card w-full bg-base-100 shadow-xl">
            <div class="card-body">
                <h2 class="card-title">Data Form</h2>
                <!-- <div role="alert" class="alert alert-info shadow-lg">
                    <vue-feather type="alert-circle" size="20"></vue-feather>
                    <span>First dorm branch to register is considered as the main branch.</span>
                </div> -->
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
                <div class="join">
                    <button class="btn btn-accent join-item" @click="submitDormAppro(1)">Publish</button>
                    <button class="btn btn-secondary join-item" @click="submitDormAppro(0)">Withhold</button>
                    <template v-if="dorm_details.fstat == null">
                        <button class="btn btn-primary join-item" @click="setFeatured">
                            Set as Featured
                        </button>
                    </template>
                    <template v-else-if="dorm_details.fstat == 1">
                        <button class="btn btn-primary join-item" @click="setFeatured">
                            Unfeatured
                        </button>
                    </template>
                    <template v-else>
                        <button class="btn btn-primary join-item" @click="setFeatured">
                            Set as Featured
                        </button>
                    </template>
                </div>

                <!--info-->
                <div class="card bg-base-100">
                    <div class="card-body">
                        <div class="collapse">
                            <input type="checkbox" id="check_infipanel" checked />
                            <div class="collapse-title text-xl font-medium bg-primary text-primary-content"
                                @click="checkInfoPanel()">
                                <h2 class="card-title">
                                    Info
                                </h2>
                            </div>
                            <div class="collapse-content">
                                <div class="grid gap-4 grid-cols-2 pt-6">
                                    <div class="form-control w-full col-span-2 ">
                                        <h1 class="card-title">{{ dorm.name }}</h1>
                                    </div>
                                    <div class="form-control w-full col-span-2">
                                        <h2 class="card-title">Description</h2>
                                        <div class="pt-2" v-html="dorm.description"></div>

                                    </div>
                                    <div class="form-control w-full col-span-2">
                                        <h2 class="card-title">Contact</h2>
                                        <div class="pt-2" v-html="dorm.contact"></div>

                                    </div>
                                    <div class="form-control w-full col-span-2">
                                        <h2 class="card-title">Dorm Type</h2>

                                        {{ dorm_details.typedes }}
                                    </div>
                                    <div class="form-control w-full">
                                        <label class="label">
                                            <span class="label-text">Region</span>
                                        </label>
                                        <select class="select select-bordered" v-model="dorm.region"
                                            @change="getRefprovince()" disabled>
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
                                        <select class="select select-bordered" v-model="dorm.prov" @change="getRefcitymun()"
                                            disabled>
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
                                        <select class="select select-bordered" v-model="dorm.citymuni"
                                            @change="getRefbrgy()" disabled>
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
                                        <select class="select select-bordered" v-model="dorm.brgy" disabled>
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
                                        <textarea class="textarea textarea-bordered" v-model="dorm.address"
                                            disabled></textarea>
                                    </div>
                                </div>
                                <!-- <br />
                                <button class="btn btn-sm btn-primary" type="submit" @click="updateDormInfo">
                                    Update
                                </button> -->
                            </div>
                        </div>

                        <div class="divider"></div>
                        <!-- upload supporting docs -->
                        <div class="collapse">
                            <input type="checkbox" id="check_uploadpanel" checked />
                            <div class="collapse-title text-xl font-medium bg-primary text-primary-content"
                                @click="checkUpPanel()">
                                <h2 class="card-title">
                                    Supporting Documents
                                </h2>
                            </div>
                            <div class="collapse-content">

                                <UploadForm :supportingdocs="supportingdocs" :dormdetails="dorm_details"
                                    :supportingget="geEditData" :canedit="null">
                                </UploadForm>

                            </div>
                        </div>
                        <!-- upload supporting docs -->
                        <div class="divider"></div>
                        <!-- upload dorm img -->
                        <div class="collapse">
                            <input type="checkbox" id="check_upload_dorm_panel" checked />
                            <div class="collapse-title text-xl font-medium bg-primary text-primary-content"
                                @click="checkUpDormPanel()">
                                <h2 class="card-title">
                                    Dorm Images
                                </h2>
                            </div>
                            <div class="collapse-content">

                                <DormImgUpload :dormimgs="dormimages" :dormdetails="dorm_details"
                                    :supportingget="geEditData" :canedit="null">
                                </DormImgUpload>

                            </div>
                        </div>
                        <!-- upload dorm img -->
                        <!-- amenities -->
                        <div class="divider"></div>
                        <!-- upload dorm img -->
                        <div class="collapse">
                            <input type="checkbox" id="check_upload_dorm_panel" checked />
                            <div class="collapse-title text-xl font-medium bg-primary text-primary-content"
                                @click="checkUpDormPanel()">
                                <h2 class="card-title">
                                    Amenities
                                </h2>
                            </div>
                            <div class="collapse-content">
                                <Amenities></Amenities>


                            </div>
                        </div>
                        <!-- amenities -->
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import { useToast } from "vue-toastification";
import UploadForm from "./forms/FileUploadForm.vue";
import DormImgUpload from "./forms/DormImgUpload.vue";
import Amenities from "./forms/Amenities.vue";
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
            htmlContent: '<strong>About Cianas Dormitory</strong>',
            dorm_details: {},

            supportingdocs: [],
            dormimages: [],

            formstate: null,

            refregion: [],
            refprovince: [],
            refcitymun: [],
            refbrgy: [],

            rcs: [],
            errors: [],
        };
    },
    mounted() {
        this.geEditData();
        this.getRefregion()
        this.iniTinyMCEcontact();
        this.iniTinyMCEdesc();
    },
    setup() {
        // Get toast interface
        const toast = useToast();

        return { toast };
    },
    methods: {
        geEditData() {

            var id = this.$route.params.id;

            axios.post("/admin/form/dorm-data-get", {
                id: id,
            })
                .then((response) => {

                    this.dorm_details = response.data.dorm;
                    this.dorm.name = response.data.dorm.name;
                    this.dorm.description = response.data.dorm.description;
                    this.dorm.contact = response.data.dorm.contact;
                    // tinyMCE.get("descripttxtarea").setContent(" ");
                    // tinyMCE.get("contacttextarea").setContent(" ");
                    // setTimeout(() => {

                    //     tinyMCE.get("descripttxtarea").execCommand('mceInsertContent', false, response.data.dorm.description);
                    //     tinyMCE.get("contacttextarea").execCommand('mceInsertContent', false, response.data.dorm.contact);
                    // }, 300);


                    this.dorm.region = response.data.dorm.region;
                    this.dorm.prov = response.data.dorm.prov;
                    this.dorm.citymuni = response.data.dorm.citymuni;
                    this.dorm.brgy = response.data.dorm.brgy;
                    this.dorm.address = response.data.dorm.address;
                    this.supportingdocs = response.data.dorm.supporting_doc;
                    this.dormimages = response.data.dorm.dorm_images;

                    //
                    this.refprovince = response.data.refprovince;
                    this.refcitymun = response.data.refcitymun;
                    this.refbrgy = response.data.refbrgy;
                    //
                })
                .catch((error) => {
                    this.errors = [];
                });

            // axios.get(`/dorm/dorm-data-get/${id}`)
            //     .then(({ data }) => {
            //         // this.refcitymun = data;

            //     });
        },
        submitDormAppro(t) {
            var id = this.$route.params.id;

            // if (this.dorm_details.status == 1 || this.dorm_details.status == 2) {
            //     this.toast.error("Unable to Submit!", {
            //         position: "top-right",
            //         timeout: 5000,
            //         closeOnClick: true,
            //         pauseOnFocusLoss: true,
            //         pauseOnHover: true,
            //         draggable: true,
            //         draggablePercent: 0.6,
            //         showCloseButtonOnHover: false,
            //         closeButton: "button",
            //         icon: true,
            //         rtl: false,
            //     });
            //     return;
            // }
            // return;
            axios
                .post("/admin/form/dorm-for-approval", {
                    id: id,
                    state: t,

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
                        this.geEditData();
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
        setFeatured() {

            axios
                .post("/admin/featured/set-featured", {
                    dorm_id: this.dorm_details.id,


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
                        this.geEditData();
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
        updateDormInfo() {
            var id = this.$route.params.id;

            var getContact = tinyMCE.get("contacttextarea").getContent();
            var getDescript = tinyMCE.get("descripttxtarea").getContent();
            // alert(getContact);
            // return;
            axios
                .post("/form/dorm-data-update", {
                    id: id,
                    name: this.dorm.name,
                    description: getDescript,
                    contact: getContact,
                    region: this.dorm.region,
                    prov: this.dorm.prov,
                    citymuni: this.dorm.citymuni,
                    brgy: this.dorm.brgy,
                    address: this.dorm.address,
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
        checkInfoPanel() {
            var phase = false,
                chk = document.getElementById("check_infipanel").checked;
            if (chk == true) {
                phase = false;
            } else {
                phase = true;
            }
            document.getElementById("check_infipanel").checked = phase;

            console.log(chk);
        },
        checkUpPanel() {
            var phase = false,
                chk = document.getElementById("check_uploadpanel").checked;
            if (chk == true) {
                phase = false;
            } else {
                phase = true;
            }
            document.getElementById("check_uploadpanel").checked = phase;

            console.log(chk);
        },

        checkUpDormPanel() {
            var phase = false,
                chk = document.getElementById("check_upload_dorm_panel").checked;
            if (chk == true) {
                phase = false;
            } else {
                phase = true;
            }
            document.getElementById("check_upload_dorm_panel").checked = phase;

            console.log(chk);
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

    components: {
        UploadForm,
        DormImgUpload,
        Amenities
    },
    computed: {},
};
</script>
<style scoped></style>
