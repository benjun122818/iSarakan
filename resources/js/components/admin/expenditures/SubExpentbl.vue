<template>
    <div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Desc</th>
                    <th>RCA Code</th>
                    <th>UACS Sub-Object Code</th>
                    <th>UACS Object Code</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr class="hover" v-for="e in subex">
                    <td>{{ e.id }}</td>
                    <td>{{ e.desc }}</td>
                    <td>{{ e.rca_code }}</td>
                    <td>{{ e.uacs_sub_object_code }}</td>
                    <td>{{ e.uacs_object_code }}</td>
                    <td>
                        <div class="btn-group">
                            <button
                                class="btn btn-error btn-sm"
                                @click="deletesubExpen(e.id)"
                            >
                                <vue-feather
                                    type="trash-2"
                                    size="15"
                                ></vue-feather>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import { useToast } from "vue-toastification";

export default {
    props: ["subex", "tblData"],
    data() {
        return {
            onaction: 0,
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
        deletesubExpen(id) {
            Swal.fire({
                title: "Confirm delete data?",
                text: "You can't revert your action",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes!",
                cancelButtonText: "No, I" + "'" + "ve change my mind!",
                showCloseButton: true,
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.value) {
                    axios
                        .post("/admin/del-subexpen", {
                            detail_id: id,
                        })
                        .then((response) => {
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
                                // setTimeout(() => {
                                this.tblData();
                                //}, 200);
                            } else {
                                Swal.fire({
                                    title: "Oops!",
                                    text: response.data.message,
                                    icon: "error",
                                });
                            }
                        })
                        .catch((error) => {
                            this.errors = [];
                            document
                                .getElementById("btn_request_submit")
                                .classList.remove("loading");

                            console.log(error.response);

                            if (error.response.status == 500) {
                                Swal.fire({
                                    title: error.response.statusText,
                                    text: error.response.data.message,
                                    icon: "error",
                                });
                            }
                        });
                } //if approve
            }); //then of swal
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
