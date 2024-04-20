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
                            <h2 class="card-title">Online Payment Details</h2>

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
                                            <template v-if="enrollment != null">
                                                <h3
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                >
                                                    {{ or_record.or_number }}
                                                </h3>
                                            </template>
                                            <template v-else>
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
                                                            to_pay.or_log
                                                                .last_or
                                                        "
                                                    />
                                                </h3>
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
                                                Deposited:
                                            </h1>
                                        </td>
                                        <td
                                            width="50%"
                                            class="text-left"
                                            style="padding-left: 10px"
                                        >
                                            <template v-if="enrollment != null">
                                                <h3
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                >
                                                    {{
                                                        or_record == null
                                                            ? ""
                                                            : or_record.trans_ref_number
                                                    }}
                                                </h3>
                                            </template>
                                            <template v-else>
                                                <h3
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                >
                                                    <input
                                                        type="text"
                                                        placeholder="Enter transaction reference no."
                                                        class="input input-bordered input-md w-full max-w-xs"
                                                        v-model="to_pay.cash"
                                                        ref="firstinput"
                                                    />
                                                </h3>
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
                                                Payment Date:
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
                                                        or_record == null
                                                            ? ""
                                                            : or_record.created_at
                                                    }}
                                                </h1>
                                            </template>
                                            <template v-else>
                                                <Datepicker
                                                    v-model="to_pay.date"
                                                    autoApply
                                                    :closeOnAutoApply="true"
                                                    :enableTimePicker="false"
                                                ></Datepicker>
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
                                                Total Assesment:
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
                                        <td colspan="2">
                                            <button
                                                class="btn btn-secondary btn-block"
                                                @click="paymentCharge"
                                                id="btn_charge"
                                            >
                                                Acknowledgement Receipt
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
                cash: null,
                or_log: [],
                date: null,
                //date: null,
                fee_amt: null,
            },

            student_record: null,

            enrollment: null,
            fullpaymentFeeList: null,

            or_record: null,
        };
    },
    mounted() {
        var self = this;
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
                .post("/payment/enrollment/pay", {
                    pref: self.$route.params.pref,
                    srid: self.$route.params.srid,
                    mode: self.$route.params.mode,
                })
                .then(function (response) {
                    console.log(response.data.studentName);

                    self.pref_id = response.data.pref;

                    self.enrollment = response.data.enrollment;

                    self.payor.name = response.data.studentName;
                    self.payor.sno = response.data.studentNumber;
                    self.payor.college = response.data.college;
                    self.payor.degree = response.data.degree;
                    self.payor.scholarship = response.data.scholarship;
                    self.payor.yearAndSection = response.data.yearAndSection;

                    self.or_record = response.data.orRecord;
                    self.to_pay.or_log = response.data.myORLog;

                    self.payor.type = response.data.resultDecode.studentType;

                    self.fullpaymentFeeList = response.data.paymentFeesList;
                    self.to_pay.fee_amt = response.data.feeAmt;

                    self.isLoading = false;

                    setTimeout(() => {
                        //this.fucusme()
                        self.$refs.firstinput.focus();
                        // self.$refs.firstinput.select();
                    }, 500);
                })
                .catch(function (error) {
                    //   console.log(error.response.data);

                    Swal.fire({
                        title: "Oops!",
                        text: error,
                        icon: "error",
                    });

                    self.isLoading = false;
                });
        },
        paymentCharge() {
            let self = this;
            document.getElementById("btn_charge").classList.add("loading");

            axios
                .post("/payment/enrollment/charge/lbponline", {
                    pref: self.$route.params.pref,
                    cash: self.to_pay.cash,
                    or: self.to_pay.or_log.last_or,
                    deposited: self.to_pay.deposited,
                    srid: self.$route.params.srid,
                    mode: self.$route.params.mode,
                    created_at: self.to_pay.date,
                })
                .then(function (response) {
                    // self.isLoading = false;
                    document
                        .getElementById("btn_charge")
                        .classList.remove("loading");

                    if (response.data.status == 1) {
                        self.paymentSummary();

                        Swal.fire({
                            text: response.data.message,
                            icon: "success",
                        });

                        window.open(
                            "/payment/enrollment/print-charge-lbponline?or=" +
                                response.data.or +
                                "&or_id=" +
                                response.data.or_id +
                                "&converted=" +
                                response.data.converted +
                                "&amt_paid=" +
                                response.data.amtPaid +
                                "&mode=" +
                                self.$route.params.mode +
                                "&srid=" +
                                self.$route.params.srid,
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
                    if (error.response.data.errors.deposited) {
                        Swal.fire({
                            title: "Oops!",
                            text: error.response.data.errors.deposited[0],
                            icon: "error",
                        });
                    }
                    if (error.response.data.errors.created_at) {
                        Swal.fire({
                            title: "Oops!",
                            text: error.response.data.errors.created_at[0],
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
                    document
                        .getElementById("btn_charge")
                        .classList.remove("loading");
                });
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
