<template>
    <div class="p-0 w-full">
        <transition name="fade">
            <div class="card w-full bg-base-100 shadow-xl" v-if="showCard">
                <div class="card-body">
                    <h2 class="card-title">Expenditure Form</h2>
                    <div
                        class="alert alert-error shadow-lg"
                        v-if="errors.length > 0"
                    >
                        <div class="flex-row">
                            <ul>
                                <li>
                                    <strong
                                        >Whoops, looks like something went
                                        wrong...</strong
                                    >
                                </li>
                                <li v-for="error in errors">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                    <form @submit="exSubmit" id="form_budget">
                        <div class="grid gap-4 grid-cols-3">
                            <div class="form-control w-full col-span-3">
                                <label class="label">
                                    <span class="label-text">Description</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_desc"
                                    v-model="expenditure.desc"
                                />
                            </div>

                            <div class="form-control w-full">
                                <label class="label">
                                    <span class="label-text">RCA Code</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_desc"
                                    v-model="expenditure.rca_code"
                                />
                            </div>
                            <div class="form-control w-full">
                                <label class="label">
                                    <span class="label-text"
                                        >UACS Sub-Object Code</span
                                    >
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_desc"
                                    v-model="expenditure.uacs_sub_object_code"
                                />
                            </div>
                            <div class="form-control w-full">
                                <label class="label">
                                    <span class="label-text"
                                        >UACS Object Code</span
                                    >
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_desc"
                                    v-model="expenditure.uacs_object_code"
                                />
                            </div>
                        </div>
                        <br />
                        <div class="card-actions">
                            <button
                                class="btn btn-sm btn-primary"
                                id="btn_request_submit"
                                type="submit"
                            >
                                Update expenditure
                            </button>
                        </div>
                    </form>
                    <!--  -->
                    <hr />
                    <h2 class="card-title">Sub Expenditure Form</h2>
                    <div
                        class="alert alert-error shadow-lg"
                        v-if="errorsub.length > 0"
                    >
                        <div class="flex-row">
                            <ul>
                                <li>
                                    <strong
                                        >Whoops, looks like something went
                                        wrong...</strong
                                    >
                                </li>
                                <li v-for="error in errorsub">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                    <form @submit="subExSubmit" id="subex">
                        <div class="grid gap-4 grid-cols-3">
                            <div class="form-control w-full col-span-3">
                                <label class="label">
                                    <span class="label-text">Sub ex</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_subdesc"
                                    v-model="subex.des"
                                />
                            </div>

                            <!--  -->
                            <div class="form-control w-full">
                                <label class="label">
                                    <span class="label-text">RCA Code</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_desc"
                                    v-model="subex.rca_code"
                                />
                            </div>
                            <div class="form-control w-full">
                                <label class="label">
                                    <span class="label-text"
                                        >UACS Sub-Object Code</span
                                    >
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_desc"
                                    v-model="subex.uacs_sub_object_code"
                                />
                            </div>
                            <div class="form-control w-full">
                                <label class="label">
                                    <span class="label-text"
                                        >UACS Object Code</span
                                    >
                                </label>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-sm input-bordered w-full"
                                    id="txt_desc"
                                    v-model="subex.uacs_object_code"
                                />
                            </div>
                            <!--  -->
                        </div>

                        <br />
                        <div class="card-actions">
                            <button
                                class="btn btn-sm btn-primary"
                                id="btn_request_submitex"
                                type="submit"
                            >
                                Submit
                            </button>
                        </div>
                    </form>
                    <!--  -->
                    <template v-if="sub_expenditure">
                        <br />
                        <SubexpenTbl
                            :subex="sub_expenditure"
                            :tblData="getExpenInfo"
                        ></SubexpenTbl>
                    </template>
                </div>
            </div>
        </transition>
        <!-- <ReqRemarkModal :pba="proposed_budgetary_allocation"></ReqRemarkModal> -->
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import { useToast } from "vue-toastification";
import { ref } from "vue";
import SubexpenTbl from "../expenditures/SubExpentbl.vue";
//import ReqRemarkModal from "../update/BudgetRequestRamarkModal.vue";

export default {
    data() {
        return {
            subex: {
                des: "",
                rca_code: null,
                uacs_sub_object_code: null,
                uacs_object_code: null,
                type: 1,
            },

            expen: null,

            expenditure: {},
            sub_expenditure: [],

            errors: [],
            errorsub: [],
            showCard: false,
        };
    },
    mounted() {
        this.getExpenInfo();
        this.showCard = true;
    },
    setup() {
        const toast = useToast();

        return { toast };
    },
    watch: {
        expen: function (val) {
            this.budget.budgetary_allocation = val;
            this.amountTextFocus();
        },
    },
    methods: {
        exSubmit(e) {
            e.preventDefault();
            axios
                .patch("/admin/expenditure/" + this.expenditure.id, {
                    expenditure: this.expenditure,
                })
                .then((response) => {
                    if (response.data.status == 1) {
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
                    } else {
                    }
                })
                .catch((err) => {
                    this.errors = [];
                    if (err.response) {
                        console.log(err.response);
                        this.toast.error(err.response.data.message, {
                            position: "top-right",
                            timeout: 5000,
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

                        // document.getElementById(
                        //     "upload_status"
                        // ).checked = false;
                    }
                });
        },
        subExSubmit(event) {
            event.preventDefault();
            var id = this.$route.params.id;
            document
                .getElementById("btn_request_submitex")
                .classList.add("loading");
            axios
                .post("/admin/add-subexpen", {
                    expen_id: id,
                    subex: this.subex,
                    //   detail_id: this.budget.detail_id,
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
                        this.getExpenInfo();
                        document
                            .getElementById("btn_request_submitex")
                            .classList.remove("loading");
                        //   this.clearForm();
                    } else {
                        Swal.fire({
                            title: "Oops!",
                            text: response.data.message,
                            icon: "error",
                        });
                        document
                            .getElementById("btn_request_submitex")
                            .classList.remove("loading");
                        //   this.clearForm();
                    }
                })
                .catch((err) => {
                    this.errorsub = [];
                    document
                        .getElementById("btn_request_submitex")
                        .classList.remove("loading");

                    if (err.response) {
                        console.log(err.response);
                        this.toast.error(err.response.data.message, {
                            position: "top-right",
                            timeout: 5000,
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

                        // document.getElementById(
                        //     "upload_status"
                        // ).checked = false;
                    }
                });
        },
        getExpenInfo() {
            var id = this.$route.params.id;

            this.errors = [];
            axios.get("/admin/expenditure/" + id + "/edit").then(({ data }) => {
                this.expenditure = data.expen;
                this.sub_expenditure = data.subexpen;
            });
        },
        getsubExpenInfo() {
            var id = this.$route.params.id;

            this.errors = [];
            axios.get("/admin/expenditure/" + id + "/edit").then(({ data }) => {
                this.expenditure = data;
            });
        },

        yearPicker() {
            const year = ref(new Date().getFullYear());

            return {
                year,
            };
        },
    },

    components: {
        SubexpenTbl,
        // ReqRemarkModal,
    },
    computed: {},
};
</script>
<style scoped></style>
