<template>
    <div class="p-0 w-full">

        <div class="card w-full bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h2 class="card-title">Reservations</h2>
                    </div>
                    <div class="card-actions justify-end">
                        <!-- <button class="btn btn-sm btn-info" @click="buttonNewBranch">
                            Create branch+
                        </button> -->
                    </div>
                </div>
                <!--  -->
                <div class="grid grid-flow-row-dense grid-cols-3 grid-rows-1">
                    <div class="col-span-2 p-2">
                        <div class="form-control">
                            <label class="input-group input-group-sm">
                                <span>Search</span>
                                <input type="text" placeholder="Type here"
                                    class="input input-bordered input-primary w-full" id="filterbox"
                                    v-model="tbl.search" v-on:input="getReservations(tbl.page)" />
                            </label>
                        </div>
                    </div>
                    <div class="w-full p-2 text-right">
                        <div class="form-control justify-end">
                            <label class="input-group input-group-sm">
                                <span>entries</span>
                                <select class="select select-primary w-full max-w-xs" v-model="tbl.entries"
                                    @change="getReservations()">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto p-2">
                    <table class="table table-compact w-full" id="item-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Dorm Branch</th>
                                <th>Room/Rate</th>
                                <th>From-to</th>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Reserved</th>
                                <th>Approved</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(s, index) in users">
                                <tr>
                                    <td>{{ ++index }}</td>
                                    <td>{{ s.dormitory }}</td>
                                    <td>{{ s.room_rate }}</td>
                                    <td>
                                        <template v-if="s.datefrom != null">
                                            {{ formatDate(s.datefrom) }} - {{ formatDate(s.dateto) }}
                                        </template>
                                    </td>
                                    <td>{{ s.name }}</td>
                                    <td>
                                        {{ s.email }}
                                    </td>
                                    <td>
                                        {{ s.contact }}
                                    </td>
                                    <td>{{ (s.status == 0 ? 'Email not verified' : s.status == 1 ? 'Verified' :
                                        'reservation Approved') }}</td>
                                    <td> {{ formatDate(s.created_at) }}</td>

                                    <td>{{ (s.aprroved == null ? '' : formatDate(s.aprroved)) }} </td>
                                    <td style="width: 10%">
                                        <div class="join">
                                            <button class="btn btn-info btn-sm join-item" name="confirm"
                                                @click="iniUpdate(s.id)" :disabled="s.status == 2">
                                                <vue-feather type="edit" size="15"></vue-feather>
                                            </button>
                                            <button class="btn btn-error btn-sm join-item" @click="iniArchive(s.id)">
                                                <vue-feather type="trash-2" size="15"></vue-feather>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!--  -->
                <div class="grid grid-flow-row-dense grid-cols-2 grid-rows-1 p-2">
                    <div>
                        <div class="join">
                            <!-- <template v-if="tbl.page >= 2"> -->
                            <button class="btn btn-sm btn-outline join-item" @click="getReservations(tbl.page - 1)"
                                :disabled="tbl.page <= 1">
                                Prev
                            </button>
                            <!-- </template> -->
                            <template v-for="p in tbl.pagLink">
                                <template v-if="p.status == 1">
                                    <button class="btn btn-sm btn-active join-item" @click="getReservations(p.page)">
                                        {{ p.text }}
                                    </button>
                                </template>
                                <template v-else>
                                    <button class="btn btn-sm join-item" @click="getReservations(p.page)"
                                        :disabled="p.text == '...'">
                                        {{ p.text }}
                                    </button>
                                </template>
                            </template>
                            <button class="btn btn-sm btn-outline join-item" @click="getReservations(++tbl.page)"
                                :disabled="tbl.page >= this.tbl.total_pages">
                                Next
                            </button>
                        </div>
                    </div>
                    <div class="w-full p-4 text-right">
                        Showing {{ tbl.total_records }} of
                        {{ tbl.all_records }} entries
                    </div>
                </div>
                <!--  -->
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
            users: [],

            tbl: {
                entries: 10,
                pagLink: [],
                page: 1,
                search: "",
                all_records: 0,
                total_records: 0,
                total_pages: 0,
            },
        };
    },
    mounted() {
        this.getReservations();
        this.automaticArchive();
    },
    methods: {
        getReservations(p) {
            //alert(p);
            if (p == null) {
                this.tbl.page = 1;
            } else {
                this.tbl.page = p;
            }
            axios
                .get(
                    "/dorm/reservations/data-table?page=" +
                    this.tbl.page +
                    "&entries=" +
                    this.tbl.entries +
                    "&search=" +
                    this.tbl.search
                )
                .then(({ data }) => {
                    this.users = data.records;
                    this.tbl.pagLink = data.pagLink;
                    this.tbl.page = data.current_page;
                    this.tbl.all_records = data.all_records;
                    this.tbl.total_records = data.total_records;
                    this.tbl.total_pages = data.total_pages;
                });
        },
        automaticArchive() {

            axios.get("/dorm/archive/reservations")
                .then(({ data }) => {

                });
        },
        iniUpdate(id) {
            Swal.fire({
                title: "Youre about to confirm reservation?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, confirm it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.confirmReservation(id);
                }
            });
        },
        iniArchive(id) {
            Swal.fire({
                title: "Youre about to archive record?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, confirm it!",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.archiveR(id);
                }
            });
        },
        confirmReservation(id) {
            //event.preventDefault();

            axios
                .post("/dorm/reservations/confirm", {
                    reservation_id: id,

                })
                .then((response) => {
                    //this.hasError = false;
                    if (response.data.statcode == 1) {

                        Swal.fire({
                            title: "Great!",
                            text: response.data.message,
                            icon: "success",
                        });
                        //this.errors = [];
                        this.getReservations();
                    }
                    else {
                        Swal.fire({
                            title: "Oops!",
                            text: response.data.message,
                            icon: "error",
                        });
                    }
                })
                .catch((error) => {
                    // this.errors = [];

                    // if (error.response.data.errors.name) {
                    //     this.errors.push(error.response.data.errors.name[0]);
                    // }
                    // if (error.response.data.errors.description) {
                    //     this.errors.push(
                    //         error.response.data.errors.description[0]
                    //     );
                    // }

                    // if (error.response.data.errors.contact) {
                    //     this.errors.push(error.response.data.errors.contact[0]);
                    // }


                });
        },
        archiveR(id) {
            axios
                .post("/dorm/reservations/archive", {
                    id: id,
                })
                .then((response) => {
                    //this.hasError = false;
                    if (response.data.statcode == 1) {

                        Swal.fire({
                            title: "Succes!",
                            text: response.data.message,
                            icon: "success",
                        });
                        this.getReservations();
                    } else {
                        Swal.fire({
                            title: "Oops!",
                            text: response.data.message,
                            icon: "error",
                        });
                    }
                })
                .catch((error) => {
                    //  this.errors = [];


                });
        },
        formatDate(date) {
            let objectDate = new Date(date);

            let day = objectDate.getDate(),
                month = objectDate.getMonth() + 1,
                year = objectDate.getFullYear();
            // let dformat = year + "-" + month + "-" + day;
            let dformat = month + "/" + day + "/" + year;

            return dformat;
        },
        buttonNewBranch() {
            this.$router.push({
                path: `/dorm/form/create`,
            });
        },
    },

    components: {},
    computed: {},
};
</script>
<style scoped>
.bg-div {
    background: white;
    /* url("/img/fem_bldg.jpg"); */
    /* background-image: url("/img/fem_bldg.jpg"); */
    background-size: cover;
    color: black;
}
</style>
