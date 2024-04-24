<template>
    <div>
        <dialog id="reserve_modal" class="modal">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Reservation Information!</h3>
                <div class="card bg-base-100" style="display: inline;" id="initial_form">
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
                    <form @submit="reserSubmit" id="form_add_dorm">
                        <!--info-->

                        <div class="card-body">

                            <div class="grid gap-4 grid-cols-2">

                                <div class="form-control w-full col-span-2" @click="verifyGencode">
                                    <label class="label">
                                        <span class="label-text">Name</span>
                                    </label>
                                    <input type="text" placeholder="Type here"
                                        class="input input-sm input-bordered w-full" v-model="reserva.name" />
                                </div>


                                <div class="form-control w-full col-span-2">
                                    <label class="label">
                                        <span class="label-text">Email</span>
                                    </label>
                                    <input type="text" placeholder="Type here"
                                        class="input input-sm input-bordered w-full" v-model="reserva.email" />
                                </div>
                                <div class="form-control w-full col-span-2" @click="verifyGencode">
                                    <label class="label">
                                        <span class="label-text">Contact</span>
                                    </label>
                                    <input type="text" placeholder="Type here"
                                        class="input input-sm input-bordered w-full" v-model="reserva.contact" />
                                </div>
                                <div class="form-control w-full col-span-2">
                                    <div class="label" v-if="created_vercode == 1">
                                        <span class="label-text-alt">Enter the verification code we've sent to
                                            {{ reserva.email }}to manage all your bookings in one place</span>
                                    </div>
                                    <input type="text" placeholder="Verification code"
                                        class="input input-lg input-bordered w-full" v-model="reserva.verification" />
                                </div>

                            </div>
                        </div>

                        <div class="card-actions">
                            <button class="btn btn-sm btn-primary" type="submit">
                                Submit
                            </button>

                        </div>
                    </form>
                </div>
                <!-- vercode -->
                <div class="card bg-base-100" style="display: none;" id="vercode">
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
                    <div>
                        <p>Verification code Sent. Check your inbox.</p>
                    </div>
                    <form @submit="reserVerify">
                        <!--info-->

                        <div class="card-body">

                            <div class="grid gap-4 grid-cols-2">

                                <div class="form-control w-full col-span-2">
                                    <label class="label">
                                        <span class="label-text">Verification Code.</span>
                                    </label>
                                    <input type="text" placeholder="Type here"
                                        class="input input-lg input-bordered w-full" v-model="reserva.verification" />
                                </div>



                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="btn btn-sm btn-primary" type="submit">
                                Verify
                            </button>

                        </div>
                    </form>
                </div>
                <!--  -->
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    </div>
</template>

<script>
import { ref } from "vue";
import { useToast } from "vue-toastification";
import Swal from "sweetalert2/dist/sweetalert2.js";
export default {
    props: ["reserve"],
    data() {
        return {
            reserva: {
                name: null,
                email: '',
                contact: null,
                verification: null
            },

            reservation_data: {},
            created_vercode: 0,

            errors: [],
        };
    },
    mounted() {

    },
    setup() {
        const date = ref(new Date()),
            toast = useToast();

        // In case of a range picker, you'll receive [Date, Date]
        const format = (date) => {
            const day = date.getDate();
            const month = date.getMonth() + 1;
            const year = date.getFullYear();

            return `${month}/${day}/${year}`;
        };

        return {
            date,
            format,
            toast,
        };
    },
    watch: {},
    methods: {
        reserSubmit(event) {
            event.preventDefault();
            // alert('wat da fuj');
            // return;
            this.errors = [];
            axios
                .post("/reservation/initial", {
                    dorm_id: this.reserve.dorm_id,
                    email: this.reserva.email,
                    name: this.reserva.name,
                    contact: this.reserva.contact,
                    vcode: this.reserva.verification,
                })
                .then((response) => {
                    //this.hasError = false;
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
                        this.errors = [];
                        reserve_modal.close();
                    } else {
                        this.toast.error(response.data.message, {
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


                    }
                })
                .catch((error) => {
                    this.errors = [];

                    console.log(error.response);
                    if (error.response.data.errors.email) {
                        this.errors.push(error.response.data.errors.email[0]);
                    }
                    if (error.response.data.errors.name) {
                        this.errors.push(
                            error.response.data.errors.name[0]
                        );
                    }

                    if (error.response.data.errors.contact) {
                        this.errors.push(error.response.data.errors.contact[0]);
                    }


                });
        },
        verifyGencode(event) {
            event.preventDefault();
            if (this.reserva.email == null || this.reserva.email == '') {
                return;
            }

            axios
                .post("/reservation/checkemail", {
                    dorm_id: this.reserve.dorm_id,
                    email: this.reserva.email,
                })
                .then((response) => {
                    //this.hasError = false;
                    if (response.data.status == 1) {

                        this.created_vercode = response.data.created_vercode;

                    }
                })
                .catch((error) => {



                });
        },
        reserVerify(event) {
            event.preventDefault();
            // alert('wat da fuj');
            // return;

            if (this.reservation_data.verrification_code != this.reserva.verification) {
                Swal.fire({
                    title: "Oops!",
                    text: 'Verification code did not match',
                    icon: "error",
                });
                return;
            }


            this.errors = [];
            axios
                .post("/reservation/verify", {
                    reservation_id: this.reservation_data.id,
                    vcode: this.reserva.verification,

                })
                .then((response) => {
                    //this.hasError = false;
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
                        this.errors = [];
                        reserve_modal.close();

                    } else {

                    }
                })
                .catch((error) => {
                    this.errors = [];

                    console.log(error.response);
                    if (error.response.data.errors.verification) {
                        this.errors.push(error.response.data.errors.verification[0]);
                    }


                });
        },
        formatNum(value) {
            let val = (value / 1).toFixed(2).replace(".", ".");
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
    },
    computed: {},
};
</script>

<style scoped>
table,
th,
td {
    /* border: 1px solid black; */
    border-collapse: collapse;
}

td {
    padding: 5px;
}

.border {
    border: 1px solid black;
}

.borderlr {
    border-left: 1px solid black;
    border-right: 1px solid black;
}

.borderl {
    border-left: 1px solid black;
}

.borderr {
    border-right: 1px solid black;
}

.borderlrb {
    border-left: 1px solid black;
    border-right: 1px solid black;
    border-bottom: 1px solid black;
}

.bordert {
    border-top: 1px solid black;
}

.bordertl {
    border-top: 1px solid black;
    border-left: 1px solid black;
}

a.remove {
    cursor: pointer;
    font-weight: 700;
    color: red;
    background-color: #e1e1e1;
    padding: 2px 5px;
    border: none;
}
</style>
