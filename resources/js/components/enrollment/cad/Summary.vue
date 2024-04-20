<template>
    <div class="sm:rounded-lg p-7 bg-div">
        <div class="min-w-full align-middle sm:rounded-md">
            <div class="flex flex-col w-full border-opacity-50">
                <div class="card w-full glass">
                    <div class="card-body">
                        <h2 class="card-title">Student Information</h2>

                        <table class="table table-compact w-full">
                            <tbody>
                                <tr>
                                    <td width="120">
                                        <strong>Student No.</strong>
                                    </td>
                                    <td>{{ payor.sno }}</td>
                                    <td width="120">
                                        <strong>College</strong>
                                    </td>
                                    <td>{{ payor.college }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Student Name</strong></td>
                                    <td>{{ payor.name }}</td>
                                    <td><strong>Degree</strong></td>
                                    <td>{{ payor.degree }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Scholarship</strong></td>
                                    <td>{{ payor.scholarship }}</td>
                                    <td><strong>Year/Section</strong></td>
                                    <td>{{ payor.yearAndSection }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div>
                            <loading
                                v-model:active="isLoading"
                                :can-cancel="true"
                                :on-cancel="onCancel"
                                :is-full-page="fullPage"
                            />

                            <!-- detail payment -->
                            <template v-if="enrollment == null">
                                <div class="collapse">
                                    <input type="checkbox" class="peer" />
                                    <div
                                        class="collapse-title bg-primary text-primary-content peer-checked:bg-secondary peer-checked:text-secondary-content"
                                    >
                                        Detailed Payment Summary
                                    </div>
                                    <div
                                        class="collapse-content bg-primary peer-checked:bg-secondary peer-checked:text-secondary-content"
                                    >
                                        <table
                                            class="table table-compact w-full"
                                        >
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th>FUND</th>
                                                    <th>DESCRIPTION</th>
                                                    <th>AMOUNT</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template
                                                    v-for="f in fullpaymentFeeList"
                                                >
                                                    <tr>
                                                        <td
                                                            style="padding: 5px"
                                                            width="10"
                                                        >
                                                            &check;
                                                        </td>
                                                        <td
                                                            style="padding: 5px"
                                                        >
                                                            {{ f.fund }}
                                                        </td>
                                                        <td
                                                            style="padding: 5px"
                                                        >
                                                            {{ f.fund_desc }}
                                                        </td>
                                                        <td
                                                            style="padding: 5px"
                                                        >
                                                            {{
                                                                payor.type == 3
                                                                    ? "US $"
                                                                    : "₱"
                                                            }}
                                                            {{
                                                                formatPrice(
                                                                    f.amount
                                                                )
                                                            }}
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>
                            <!-- detail payment -->

                            <!-- Payment Details -->
                            <h2 class="card-title">Payment Details</h2>

                            <table
                                class="table table-compact w-full"
                                id="pay_det"
                            >
                                <tbody>
                                    <tr>
                                        <td width="50%" class="text-right">
                                            <h1
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                                class="text-2xl font-bold"
                                            >
                                                OR #:
                                            </h1>
                                        </td>
                                        <td
                                            width="50%"
                                            class="text-left"
                                            style="padding-left: 10px"
                                        >
                                            <h3
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                            >
                                                <input
                                                    type="text"
                                                    placeholder="OR Number here"
                                                    class="input input-bordered input-md w-full max-w-xs"
                                                    v-model="
                                                        to_pay.or_log.last_or
                                                    "
                                                />
                                            </h3>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td width="50%" class="text-right">
                                            <h1
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                                class="text-2xl font-bold"
                                            >
                                                Amount Due:
                                            </h1>
                                        </td>
                                        <td
                                            width="50%"
                                            class="text-left"
                                            style="padding-left: 10px"
                                        >
                                            <template v-if="enrollment != null">
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                >
                                                    {{
                                                        payor.type == 3
                                                            ? "US $"
                                                            : "₱"
                                                    }}
                                                    {{
                                                        formatPrice(
                                                            to_pay.fee_amt
                                                        )
                                                    }}
                                                </h1>
                                            </template>
                                            <template v-else>
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                >
                                                    {{
                                                        payor.type == 3
                                                            ? "US $"
                                                            : "₱"
                                                    }}
                                                    {{
                                                        formatPrice(
                                                            to_pay.fee_amt
                                                        )
                                                    }}
                                                </h1>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="50%" class="text-right">
                                            <h1
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                                class="text-2xl font-bold"
                                            >
                                                Amount Tendered:
                                            </h1>
                                        </td>
                                        <td
                                            width="50%"
                                            class="text-left"
                                            style="padding-left: 10px"
                                        >
                                            <template v-if="enrollment != null">
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                >
                                                    {{
                                                        payor.type == 3
                                                            ? "US $"
                                                            : "₱"
                                                    }}
                                                    {{
                                                        formatPrice(
                                                            or_record.cash
                                                        )
                                                    }}
                                                </h1>
                                            </template>
                                            <template v-else>
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                ></h1>

                                                <div class="form-control">
                                                    <label class="input-group">
                                                        <span>
                                                            {{
                                                                payor.type == 3
                                                                    ? "US $"
                                                                    : "₱"
                                                            }}</span
                                                        >
                                                        <input
                                                            type="number"
                                                            ref="firstinput"
                                                            placeholder="Amount"
                                                            class="input input-bordered"
                                                            v-on:keyup.enter="
                                                                pressEnter
                                                            "
                                                            v-model="
                                                                to_pay.amount
                                                            "
                                                        />
                                                    </label>
                                                </div>
                                            </template>
                                        </td>
                                    </tr>
                                    <tr>
                                        <template v-if="enrollment != null">
                                            <td width="50%" class="text-right">
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                >
                                                    Balance
                                                </h1>
                                            </td>
                                            <td
                                                width="50%"
                                                class="text-left"
                                                style="padding-left: 10px"
                                            >
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                >
                                                    {{
                                                        payor.type == 3
                                                            ? "US $"
                                                            : "₱"
                                                    }}
                                                    {{
                                                        formatPrice(
                                                            or_record.amount
                                                        )
                                                    }}
                                                </h1>
                                            </td>
                                        </template>
                                        <template v-else>
                                            <td width="50%" class="text-right">
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                >
                                                    Change:
                                                </h1>
                                            </td>
                                            <td
                                                width="50%"
                                                class="text-left"
                                                style="padding-left: 10px"
                                            >
                                                <h1
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-2xl font-bold"
                                                >
                                                    {{
                                                        payor.type == 3
                                                            ? "US $"
                                                            : "₱"
                                                    }}
                                                    {{ formatPrice(supli) }}
                                                </h1>
                                            </td>
                                        </template>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button
                                                class="btn btn-secondary btn-block"
                                                @click="paymentCharge"
                                                :disabled="
                                                    to_pay.or_log.last_or ==
                                                        '' ||
                                                    to_pay.amount == 0 ||
                                                    to_pay.amount == '' ||
                                                    to_pay.amount <
                                                        to_pay.fee_amt
                                                "
                                                id="btn_charge"
                                            >
                                                Charge Payment
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <a
                                                class="btn btn-ghost btn-block"
                                                href="/enrollment-index"
                                                >Done</a
                                            >
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- Payment Details -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
// import Loading from "vue-loading-overlay";
// import "vue-loading-overlay/dist/vue-loading.css";

export default {
    data() {
        return {
            isLoading: false,
            fullPage: false,
            pref_id: null,

            payor: {
                name: "",
                sno: "",
                college: "",
                degree: "",
                scholarship: "",
                yearAndSection: "",
                type: "",
            },

            to_pay: {
                or_log: [],
                date: null,
                fee_amt: null,
                amount: 0,
            },

            student_record: null,

            enrollment: null,
            fullpaymentFeeList: null,

            or_record: null,
        };
    },
    mounted() {
        this.paymentSummary();
        this.to_pay.date = new Date();

        const element = document.getElementById("pay_det");
        element.scrollIntoView();
    },
    computed: {
        supli() {
            var change = 0;

            if (this.to_pay.amount == "") {
                return change;
            }

            change =
                parseFloat(this.to_pay.amount) -
                parseFloat(this.to_pay.fee_amt);

            if (change <= 0) {
                change = 0;
            }

            return change;
        },
    },
    methods: {
        paymentSummary() {
            let self = this;
            self.isLoading = true;

            axios
                .post("/cad/summary", {
                    cad_id: self.$route.params.id,
                })
                .then(function (response) {
                    console.log(response.data);

                    // self.pref_id = response.data.pref;

                    // self.enrollment = response.data.enrollment;

                    self.payor.name = response.data.studentName;
                    self.payor.sno = response.data.studentNumber;
                    self.payor.college = response.data.college;
                    self.payor.degree = response.data.degree;
                    self.payor.scholarship = response.data.scholarship;
                    self.payor.yearAndSection = response.data.yearAndSection;

                    self.to_pay.or_log = response.data.myORLog;
                    // self.or_record = response.data.orRecord;

                    self.payor.type = response.data.assess.studentType;

                    self.fullpaymentFeeList = response.data.feesList;
                    self.to_pay.fee_amt = response.data.assess.totalAmount;

                    self.isLoading = false;

                    setTimeout(() => {
                        //this.fucusme()
                        self.$refs.firstinput.focus();
                        self.$refs.firstinput.select();
                    }, 500);
                })
                .catch(function (error) {
                    //   console.log(error.response.data);

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
                            footer: "Click OK to reload page.",
                        }).then((result) => {
                            if (result.value) {
                                // window.location.reload();
                            } //if approve
                        }); //then of swal with confirm
                    }

                    self.isLoading = false;
                });
        },
        paymentCharge() {
            let self = this;
            //self.isLoading = true;

            //
            Swal.fire({
                title: "Are you sure you want to charge this student payment?",
                text:
                    "OR: " +
                    self.to_pay.or_log.last_or +
                    " Amount Tendered:" +
                    self.to_pay.amount,
                showCancelButton: true,
                confirmButtonText: "OK",
                cancelButtonText: "Cancel",
                showCloseButton: true,
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.value) {
                    //  self.isLoading = true;
                    document
                        .getElementById("btn_charge")
                        .classList.add("loading");

                    axios
                        .post("/cad/pay", {
                            cash: self.to_pay.amount,
                            or: self.to_pay.or_log.last_or,
                            cadid: self.$route.params.id,
                        })
                        .then(function (response) {
                            self.isLoading = false;
                            document
                                .getElementById("btn_charge")
                                .classList.remove("loading");

                            if (response.data.status == 1) {
                                //   self.paymentSummary();

                                window.open(
                                    "/cad/pay/print-charge?or=" +
                                        response.data.or +
                                        "&converted=" +
                                        response.data.converted +
                                        "&amount=" +
                                        response.data.amtPaid +
                                        "&cadid=" +
                                        self.$route.params.id,
                                    "_blank"
                                );
                            } else {
                                Swal.fire({
                                    title: "Oops!",
                                    text: response.data.message,
                                    icon: "error",
                                    footer: response.data.footer,
                                });
                            }
                        })
                        .catch(function (error) {
                            //   console.log(error.response.data);
                            document
                                .getElementById("btn_charge")
                                .classList.remove("loading");
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
                                        //window.location.reload();
                                    } //if approve
                                }); //then of swal with confirm
                            }
                            // self.isLoading = false;
                        });
                } //if approve
            }); //then of swal
            //
        },
        pressEnter() {
            document.getElementById("btn_charge").click();
        },
        toggle() {
            $("#myModalItem").modal("show");

            setTimeout(() => {
                //this.fucusme()
                this.$refs.focusThis.focus();
                this.$refs.focusThis.select();
            }, 50);

            console.log("item modal has opened");
        },

        formatPrice(value) {
            let val = (value / 1).toFixed(2).replace(".", ".");
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        onCancel() {
            console.log("User cancelled the loader.");
        },
    },

    components: {
        //Loading,
        // Datepicker,
    },
};
</script>
<style scoped>
.bg-div {
    background-image: url("/img/fem_bldg.jpg");
    background-size: cover;
    background-position: center;
    color: black;
}
</style>
