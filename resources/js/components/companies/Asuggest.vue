<template>
    <div class="overflow-hidden sm:rounded-lg h-96 bg-div">
        <div class="p-6 border-gray-200">
            <div
                class="overflow-hidden overflow-x-auto min-w-full align-middle sm:rounded-md"
            >
                <div class="card w-full glass">
                    <div class="card-body">
                        <h1 class="card-title">Payment</h1>
                        <div
                            class="form-control"
                            style="background-color: white"
                        >
                            <v-select
                                v-model="chosen"
                                :reduce="(option) => option.id"
                                :options="[
                                    { label: 'One', id: 1 },
                                    { label: 'Two', id: 2 },
                                ]"
                                label="label"
                            />
                        </div>
                        <h1>Vue Select - Ajax</h1>
                        <div
                            class="form-control"
                            style="background-color: white"
                        >
                            <v-select
                                label="name"
                                :filterable="false"
                                :options="items"
                                @search="onSearch"
                                v-model="chosen"
                            >
                                <template #no-options>
                                    type to search..
                                </template>
                                <template #option="{ name, description }">
                                    <div class="d-center">
                                        {{ name }}<br />
                                        <small>{{ description }}</small>
                                    </div>
                                </template>
                                <template #selected-option="{ name }">
                                    <div class="selected d-center">
                                        {{ name }}
                                    </div>
                                </template>
                            </v-select>
                        </div>
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Student Number</span>
                            </label>

                            <input
                                type="text"
                                id="firstinput"
                                placeholder="Student Number"
                                class="input input-secondary"
                                ref="firstinput"
                                v-model="payment.student_number"
                            />
                        </div>
                        <div class="card-actions">
                            <button
                                class="btn btn-primary"
                                @click="paymentSummary"
                                id="btn_submit_pay"
                            >
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import "vue-select/dist/vue-select.css";
export default {
    data() {
        return {
            payment: {
                student_number: "",
                pref: null,
            },

            chosen: "",

            items: [],
        };
    },
    mounted() {
        this.itemGet();
    },
    methods: {
        onSearch(search, loading) {
            if (search.length) {
                loading(true);
                //  this.search(loading, search, this);
            }
            // const url = '/officeallstock';

            var query = search;
            //  let i = this.items;

            return new Promise((resolve) => {
                axios.get(`/getSearchItem?term=${query}`).then(({ data }) => {
                    this.items = [];

                    data.forEach((item) => {
                        if (item) {
                            this.items.push(item);
                            loading(false);
                        }
                    });
                    console.log(this.items);
                    resolve(this.items);
                    loading(false);
                });
            });
            loading(false);
        },
        itemGet() {
            window.axios.get("/get-food").then(({ data }) => {
                this.items = data;
            });
        },
        paymentSummary() {
            let self = this;

            //let token = document.getElementsByName("_token")[0].value;

            //return;

            var pref = this.payment.pref;
            var sn = this.payment.student_number;
            document.getElementById("btn_submit_pay").classList.add("loading");
            axios
                .post("payment/enrollment/validate-summary", {
                    pref: pref,
                    student_number: sn,
                })
                .then(function (response) {
                    console.log(response);
                    if (response.data.status == 0) {
                        Swal.fire({
                            title: "Oops!",
                            text: response.data.message,
                            icon: "error",
                        });
                        document
                            .getElementById("btn_submit_pay")
                            .classList.remove("loading");
                        //  document.getElementById("firstinput").focus();
                    } else {
                        if (response.data.payment_type == 1) {
                            self.$router.push({
                                path: `/payment/enrollment/summary/${pref}/${sn}`,
                            });
                            document
                                .getElementById("btn_submit_pay")
                                .classList.remove("loading", "btn-disabled");
                        } else {
                            // /cad/summary
                            var id = response.data.cad_id;
                            self.$router.push({
                                path: `/cad/summary/${id}`,
                            });
                            document
                                .getElementById("btn_submit_pay")
                                .classList.remove("loading", "btn-disabled");
                        }
                    }
                })
                .catch(function (error) {
                    console.log(error);
                    document
                        .getElementById("btn_submit_pay")
                        .classList.remove("loading", "btn-disabled");

                    if (error.response && 419 === error.response.status) {
                        console.log(
                            error.response.status,
                            error.response.data.message
                        );

                        let text =
                            "Status: " +
                            error.response.status +
                            "\nMessage: " +
                            error.response.data.message;
                        // if (confirm(text) == true) {
                        //     window.location.reload();
                        // }

                        Swal.fire({
                            title: "Oops!",
                            text: text,
                            icon: "error",
                            confirmButtonText: "OK",
                            showLoaderOnConfirm: true,
                            footer: "Click OK to reload page.",
                        }).then((result) => {
                            if (result.value) {
                                window.location.reload();
                            } //if approve
                        }); //then of swal with confirm
                    }

                    if (error.response.data.errors.student_number) {
                        Swal.fire({
                            title: "Oops!",
                            text: error.response.data.errors.student_number[0],
                            icon: "error",
                        });
                    }
                    if (error.response) {
                        console.log(
                            error.response.status,
                            error.response.data.message
                        );

                        let text =
                            "Status: " +
                            error.response.status +
                            "\nMessage: " +
                            error.response.data.message;
                        // if (confirm(text) == true) {
                        //     window.location.reload();
                        // }

                        Swal.fire({
                            title: "Oops!",
                            text: text,
                            icon: "error",
                            confirmButtonText: "OK",
                            showLoaderOnConfirm: true,
                            // footer: "Click OK to reload page.",
                        }).then((result) => {
                            if (result.value) {
                                window.location.reload();
                            } //if approve
                        }); //then of swal with confirm
                    }
                    // Swal.fire({
                    //     title: "Oops!",
                    //     text: error,
                    //     icon: "error",
                    // });
                });
        },
    },

    components: {},
    computed: {},
};
</script>
<style scoped>
.bg-div {
    background-image: url("/img/fem_bldg.jpg");
    background-size: cover;
    color: black;
}
</style>
