TransactionTypeController
<template>
    <!-- Put this part before </body> tag -->
    <input type="checkbox" id="add_type_modal" class="modal-toggle" />
    <div class="modal modal-middle xl:modal-large">
        <div class="modal-box w-11/12 max-w-5xl">
            <label
                for="add_type_modal"
                class="btn btn-sm btn-circle btn-outline absolute right-2 top-2"
                >✕</label
            >
            <h3 class="font-bold text-lg">Transaction Type Form</h3>
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
                <div class="grid gap-4 grid-cols-2">
                    <div class="form-control w-full col-span-2">
                        <label class="label">
                            <span class="label-text">Description</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Type here"
                            class="input input-sm input-bordered w-full"
                            v-model="expenditure.description"
                            id="additem"
                            ref="additem"
                        />
                    </div>
                    <div class="form-control w-full col-span-2">
                        <label class="label">
                            <span class="label-text">Code</span>
                        </label>
                        <input
                            type="text"
                            placeholder="Type here"
                            class="input input-sm input-bordered w-full"
                            v-model="expenditure.code"
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
                description: "",
                code: "",
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
                .post("/admin/transactions-type", {
                    desc: this.expenditure.description,
                    code: this.expenditure.code,
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
                    document.getElementById("add_type_modal").checked = false;
                })
                .catch((error) => {
                    this.errors = [];

                    if (error.response.data.errors.desc) {
                        this.errors.push(error.response.data.errors.desc[0]);
                        document
                            .getElementById("additem")
                            .classList.add("input-error");
                        //input-error
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
