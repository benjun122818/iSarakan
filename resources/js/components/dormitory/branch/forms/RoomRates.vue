<template>
    <div class="pt-6 w-full">
        <h2 class="card-title">Room / Rates</h2>

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
        <form @submit="addRoomRate" id="form_add_dorm">
            <!--info-->
            <div class="grid gap-4 grid-cols-2">
                <div class="form-control w-full col-span-2">
                    <label class="label">
                        <span class="label-text">Name</span>
                    </label>
                    <input type="text" placeholder="Type here" class="input input-bordered w-full" v-model="rr.name" />
                </div>
                <div class="form-control w-full col-span-2">
                    <label class="label">
                        <span class="label-text">Description</span>
                    </label>
                    <input type="text" placeholder="Type here" class="input input-bordered w-full" v-model="rr.des" />
                </div>
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Rate</span>
                    </label>
                    <input type="number" placeholder="Type here" min="1" class="input input-bordered w-full"
                        v-model="rr.rate" />
                </div>
                <div class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Quantity</span>
                    </label>
                    <input type="number" placeholder="Type here" min="1" class="input input-bordered w-full"
                        v-model="rr.qty" />
                </div>
            </div>

            <!--info-->

            <br />
            <div class="card-actions">
                <button class="btn btn-sm btn-primary" type="submit">
                    Submit
                </button>
            </div>
        </form>
        <br />

        <div class="grid grid-cols-3 gap-4">
            <template v-for="a in amenities">
                <div role="alert" class="alert shadow-lg">
                    <vue-feather :type="a.icon" size="20"> </vue-feather>
                    <span> {{ a.description }}</span>
                    <div>
                        <button class="btn btn-sm" @click="removeAmenities(a.id)">
                            <vue-feather type="x-circle" size="20">
                            </vue-feather>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-xs">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Rate</th>
                        <th>Quantity</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(s, index) in roomrates">
                        <tr>
                            <th>{{ ++index }}</th>
                            <td>{{ s.name }}</td>
                            <td>{{ s.des }}</td>
                            <td>{{ formatNum(s.rate) }}</td>
                            <td>{{ s.quantity }}</td>
                            <td>
                                <button class="btn btn-sm" @click="removeRR(s.id)"><vue-feather type="x-circle"
                                        size="20">
                                    </vue-feather></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import { useToast } from "vue-toastification";
export default {
    data() {
        return {
            ame: {
                name: "",
                icon: "",
            },

            rr: {
                name: "",
                des: "",
                rate: 0,
                qty: 0,
            },

            formstate: null,
            roomrates: [],
            feather_icons: [],

            rcs: [],
            errors: [],
        };
    },
    mounted() {
        this.formstate = this.$route.params.state;
        setTimeout(() => {
            this.getRoomRates();
        }, 300);
    },
    setup() {
        // Get toast interface
        const toast = useToast();

        return { toast };
    },
    methods: {
        addRoomRate(event) {
            event.preventDefault();
            var id = this.$route.params.id;

            axios
                .post("/form/dorm-roomrate-create", {
                    dorm_branch_id: id,
                    name: this.rr.name,
                    des: this.rr.des,
                    rate: this.rr.rate,
                    qty: this.rr.qty,
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
                        this.getRoomRates();
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

                    if (error.response.data.errors.name) {
                        this.errors.push(error.response.data.errors.name[0]);
                    }
                    if (error.response.data.errors.des) {
                        this.errors.push(error.response.data.errors.des[0]);
                    }

                    if (error.response.data.errors.rate) {
                        this.errors.push(error.response.data.errors.rate[0]);
                    }

                    if (error.response.data.errors.qty) {
                        this.errors.push(error.response.data.errors.qty[0]);
                    }
                });
        },
        removeRR(id) {
            axios
                .post("/form/dorm-roomrate-remove", {
                    branch_id: id,
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
                        this.getRoomRates();
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


                });
        },
        getRoomRates() {
            var id = this.$route.params.id;

            axios.get(`/form/dorm-roomrate-get/${id}`).then(({ data }) => {
                this.roomrates = data;
            });
        },
        formatNum(value) {
            let val = (value / 1).toFixed(2).replace(".", ".");
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },

    },

    components: {},
    computed: {},
};
</script>
<style scoped></style>
