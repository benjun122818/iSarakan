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
                        <div id="pay_det">
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
                            <!-- detail paymeny -->

                            <!-- payment summary -->
                            <h2 class="card-title">Payment Summary</h2>

                            <table class="table table-compact w-full">
                                <tbody>
                                    <template v-if="payor.type == 3">
                                        <tr>
                                            <td colspan="3" class="text-center">
                                                <h3
                                                    style="
                                                        margin-top: 6px;
                                                        margin-bottom: 6px;
                                                    "
                                                    class="text-primary"
                                                >
                                                    All amounts stated below are
                                                    in US $.
                                                </h3>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr>
                                        <td width="50%" class="text-right">
                                            <h3
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                                class="text-primary"
                                            >
                                                Full Payment:
                                            </h3>
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
                                                {{
                                                    payor.type == 3
                                                        ? "US $"
                                                        : "₱"
                                                }}
                                                {{
                                                    formatPrice(
                                                        payments.fullPayment
                                                    )
                                                }}
                                            </h3>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="50%" class="text-right">
                                            <h3
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                                class="text-primary"
                                            >
                                                Down Payment:
                                            </h3>
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
                                                {{
                                                    payor.type == 3
                                                        ? "US $"
                                                        : "₱"
                                                }}
                                                {{
                                                    formatPrice(
                                                        payments.downPayment
                                                    )
                                                }}
                                            </h3>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="50%" class="text-right">
                                            <h3
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                                class="text-primary"
                                            >
                                                Second Payment:
                                            </h3>
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
                                                {{
                                                    payor.type == 3
                                                        ? "US $"
                                                        : "₱"
                                                }}
                                                {{
                                                    formatPrice(
                                                        payments.secondPayment
                                                    )
                                                }}
                                            </h3>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="50%" class="text-right">
                                            <h3
                                                style="
                                                    margin-top: 6px;
                                                    margin-bottom: 6px;
                                                "
                                                class="text-primary"
                                            >
                                                Third Payment:
                                            </h3>
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
                                                {{
                                                    payor.type == 3
                                                        ? "US $"
                                                        : "₱"
                                                }}
                                                {{
                                                    formatPrice(
                                                        payments.thirdPayment
                                                    )
                                                }}
                                            </h3>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- payment summary -->

                            <!-- paymentmode -->
                            <template v-if="enrollment == null">
                                <div class="flex flex-col w-full lg:flex-row">
                                    <template
                                        v-if="scholarship_details != null"
                                    >
                                        <template
                                            v-if="
                                                scholarship_details.chargedfull ==
                                                    1 && freeTuition == 0
                                            "
                                        >
                                            <div
                                                class="grid place-items-center"
                                            >
                                                <button
                                                    class="btn btn-primary btn-sm"
                                                    @click="scholarshipCharge"
                                                    id="btn_submit_pay"
                                                >
                                                    Full Scholarship Grantee
                                                </button>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <template
                                                v-if="payments.fullPayment > 0"
                                            >
                                                <div
                                                    class="grid place-items-center"
                                                >
                                                    <button
                                                        class="btn btn-primary btn-sm"
                                                        @click="
                                                            paymentMode(
                                                                'full',
                                                                'i'
                                                            )
                                                        "
                                                    >
                                                        Full Payment
                                                    </button>
                                                </div>

                                                <div
                                                    class="divider lg:divider-horizontal"
                                                >
                                                    &nbsp;
                                                </div>
                                            </template>
                                            <template
                                                v-if="payments.downPayment > 0"
                                            >
                                                <div
                                                    class="grid place-items-center"
                                                >
                                                    <button
                                                        class="btn btn-primary btn-sm"
                                                        @click="
                                                            paymentMode(
                                                                'down',
                                                                'i'
                                                            )
                                                        "
                                                    >
                                                        Down Payment
                                                    </button>
                                                </div>

                                                <div
                                                    class="divider lg:divider-horizontal"
                                                >
                                                    &nbsp;
                                                </div>

                                                <div
                                                    class="grid place-items-center"
                                                >
                                                    <button
                                                        class="btn btn-primary btn-sm"
                                                        @click="
                                                            paymentMode(
                                                                'down-and-second',
                                                                'i'
                                                            )
                                                        "
                                                    >
                                                        Down & Second Payment
                                                    </button>
                                                </div>
                                            </template>
                                            <!-- online payment -->
                                            <template
                                                v-if="payments.fullPayment > 0"
                                            >
                                                <div
                                                    class="divider lg:divider-horizontal"
                                                >
                                                    &nbsp;
                                                </div>
                                                <div
                                                    class="grid place-items-center"
                                                >
                                                    <button
                                                        class="btn btn-secondary btn-sm"
                                                        @click="
                                                            paymentMode(
                                                                'online',
                                                                'i'
                                                            )
                                                        "
                                                    >
                                                        Online Full Payment
                                                    </button>
                                                </div>

                                                <div
                                                    class="divider lg:divider-horizontal"
                                                >
                                                    &nbsp;
                                                </div>
                                                <div
                                                    class="grid place-items-center"
                                                >
                                                    <button
                                                        class="btn btn-secondary btn-sm"
                                                        @click="
                                                            paymentMode(
                                                                'online',
                                                                'lbp'
                                                            )
                                                        "
                                                    >
                                                        LBP Payment
                                                        Acknowledgement
                                                    </button>
                                                </div>
                                            </template>
                                            <!-- online payment -->
                                        </template>
                                    </template>
                                </div>
                            </template>

                            <!-- payment mode -->
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
            btn_disable: false,
            pref_id: null,

            payments: {
                fullPayment: 0,
                downPayment: 0,
                secondPayment: 0,
                thirdPayment: 0,
            },

            payor: {
                name: "",
                sno: "",
                college: "",
                degree: "",
                scholarship: "",
                yearAndSection: "",
                type: "",
            },

            student_record: null,

            scholarship_details: null,
            freeTuition: null,
            enlisted: null,

            enrollment: null,
            fullpaymentFeeList: null,
        };
    },
    mounted() {
        this.paymentSummary();

        const element = document.getElementById("pay_det");
        element.scrollIntoView();
    },
    methods: {
        paymentSummary() {
            let self = this;
            self.isLoading = true;
            axios
                .post("/payment/enrollment/summary-data", {
                    pref: self.$route.params.pref,
                    sno: self.$route.params.sn,
                })
                .then(function (response) {
                    console.log(response.data.studentName);

                    self.pref_id = response.data.pref_id;

                    self.enrollment = response.data.enrollment;

                    self.payor.name = response.data.studentName;
                    self.payor.sno = response.data.studentNumber;
                    self.payor.college = response.data.college;
                    self.payor.degree = response.data.degree;
                    self.payor.scholarship = response.data.scholarship;
                    self.payor.yearAndSection = response.data.yearAndSection;

                    self.student_record = response.data.student_record;

                    self.payor.type = response.data.resultDecode.studentType;

                    self.fullpaymentFeeList =
                        response.data.resultDecode.paymentsList.fullpaymentFeeList;

                    self.scholarship_details =
                        response.data.resultDecode.scholarshipDetails;

                    self.freeTuition = response.data.resultDecode.freeTuition;
                    self.enlisted = response.data.enlisted;

                    self.payments.fullPayment =
                        response.data.resultDecode.fullPayment;
                    self.payments.downPayment =
                        response.data.resultDecode.downPayment;
                    self.payments.secondPayment =
                        response.data.resultDecode.secondPayment;
                    self.payments.thirdPayment =
                        response.data.resultDecode.thirdPayment;

                    self.isLoading = false;
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
                                window.location.reload();
                            } //if approve
                        }); //then of swal with confirm
                    }

                    self.isLoading = false;
                });
        },
        scholarshipCharge() {
            let self = this;

            var srid = self.student_record.id;
            var pref = self.pref_id;

            //self.isLoading = true;
            document.getElementById("btn_submit_pay").classList.add("loading");
            axios
                .post("/payment/enrollment/charge/scholar", {
                    pref: pref,
                    srid: srid,
                })
                .then(function (response) {
                    //   self.pref_id = response.data.pref_id;
                    //  self.isLoading = false;
                    document
                        .getElementById("btn_submit_pay")
                        .classList.remove("loading", "btn-disabled");
                    self.paymentSummary();

                    Swal.fire({
                        title: response.data.message,
                        icon: "success",
                        confirmButtonText: "OK",
                        showLoaderOnConfirm: true,
                    }).then((result) => {
                        if (result.value) {
                            window.location.href = "/enrollment-index";
                        } //if approve
                    }); //then of swal with confirm
                    //
                    //

                    // setTimeout(() => {
                    //     window.location.href = "/enrollment-index";
                    // }, 500);
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
                            // footer: "Click OK to reload page.",
                        }).then((result) => {
                            if (result.value) {
                                window.location.reload();
                            } //if approve
                        }); //then of swal with confirm
                    }
                    document
                        .getElementById("btn_submit_pay")
                        .classList.remove("loading", "btn-disabled");
                    // self.isLoading = false;
                });
        },
        paymentMode(mode, v) {
            var srid = this.student_record.id;
            var pref = this.pref_id;

            if (mode == "online") {
                if (v == "i") {
                    this.$router.push({
                        path: `/payment/enrollment/pay-online/${mode}/${srid}/${pref}`,
                    });
                } else {
                    this.$router.push({
                        path: `/payment/enrollment/pay-online-lbp/${mode}/${srid}/${pref}`,
                    });
                }
            } else {
                this.$router.push({
                    path: `/payment/enrollment/pay/${mode}/${srid}/${pref}`,
                });
            }
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
    },
    computed: {},
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
