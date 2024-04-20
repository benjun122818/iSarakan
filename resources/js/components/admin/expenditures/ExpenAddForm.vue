<template>
    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="add_form_modal" class="modal-toggle" />
    <div class="modal modal-middle xl:modal-large">
        <div class="modal-box w-11/12 max-w-5xl">
            <label
                for="add_form_modal"
                class="btn btn-sm btn-circle btn-outline absolute right-2 top-2"
                >✕</label
            >
            <h3 class="font-bold text-lg">Expenditure Form</h3>
            <div class="alert alert-error shadow-lg" v-if="errors.length > 0">
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
            <form @submit="createExpen" id="form_add_ex">
                <div class="grid gap-4 grid-cols-3">
                    <div class="form-control w-full col-span-3">
                        <label class="label">
                            <span class="label-text">Description</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Type here"
                            class="input input-sm input-bordered w-full"
                            v-model="expenditure.des"
                            id="additem"
                            ref="additem"
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
                            v-model="expenditure.rca_code"
                        />
                    </div>
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">UACS Sub-Object Code</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Type here"
                            class="input input-sm input-bordered w-full"
                            v-model="expenditure.uacs_sub_object_code"
                        />
                    </div>
                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">UACS Object Code</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Type here"
                            class="input input-sm input-bordered w-full"
                            v-model="expenditure.uacs_object_code"
                        />
                    </div>
                </div>
                <br />
                <div class="card-actions">
                    <button class="btn btn-sm btn-primary" type="submit">
                        Submit
                    </button>
                </div>
            </form>
            <div class="modal-action">
                <!-- <label for="my-modal-5" class="btn">Yay!</label> -->
            </div>
        </div>
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import { useToast } from "vue-toastification";
export default {
    props: ["tblData"],
    data() {
        return {
            expenditure: {
                des: "",
                rca_code: null,
                uacs_sub_object_code: null,
                uacs_object_code: null,
            },
            errors: [],
        };
    },
    mounted() {},
    setup() {
        // Get toast interface
        const toast = useToast();

        return { toast };
    },
    methods: {
        createExpen(event) {
            event.preventDefault();
            axios
                .post("/admin/expenditure", {
                    expenditure: this.expenditure,
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
                    this.tblData();
                    document.getElementById("add_form_modal").checked = false;
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
                    }
                });
        },
        clearForm() {
            this.expenditure.description = "";
            this.expenditure.code = "";
        },
    },

    components: {
        //Loading,
    },
    computed: {},
};
</script>
<style scoped></style>
