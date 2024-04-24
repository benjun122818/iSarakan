<template>
    <div class="p-0 w-full">
        <div class="card w-full bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h2 class="card-title">Branches</h2>
                    </div>
                    <div class="card-actions justify-end">
                        <button class="btn btn-sm btn-info" @click="buttonNewBranch">
                            Create branch+
                        </button>
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
                                    v-model="tbl.search" v-on:input="getUsers(tbl.page)" />
                            </label>
                        </div>
                    </div>
                    <div class="w-full p-2 text-right">
                        <div class="form-control justify-end">
                            <label class="input-group input-group-sm">
                                <span>entries</span>
                                <select class="select select-primary w-full max-w-xs" v-model="tbl.entries"
                                    @change="getUsers()">
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
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Contact</th>
                                <th>Type</th>
                                <th>Availability</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(s, index) in users">
                                <tr>
                                    <td>{{ s.id }}</td>
                                    <td>{{ s.name }}</td>
                                    <td>
                                        <div v-html="s.description"></div>
                                    </td>
                                    <td>
                                        <div v-html="s.contact"></div>
                                    </td>
                                    <td>{{ (s.type == 1 ? 'Main' : 'Branch') }}</td>

                                    <td>
                                        <template v-if="s.availability == 1">
                                            <input type="checkbox" class="toggle"
                                                @click="updateAvailability(s.id, s.availability)" checked />
                                        </template>
                                        <template v-else>
                                            <input type="checkbox" class="toggle"
                                                @click="updateAvailability(s.id, s.availability)" />
                                        </template>

                                    </td>
                                    <td>
                                        {{ setStatus(s.status) }}
                                    </td>
                                    <td style="width: 10%">
                                        <div class="join">
                                            <button class="btn btn-info btn-sm join-item" @click="iniUpdate(s.id)">
                                                <vue-feather type="edit" size="15"></vue-feather>
                                            </button>
                                            <button class="btn btn-error btn-sm join-item" @click="deletescholar(s.id)">
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
                            <button class="btn btn-sm btn-outline join-item" @click="getUsers(tbl.page - 1)"
                                :disabled="tbl.page <= 1">
                                Prev
                            </button>
                            <!-- </template> -->
                            <template v-for="p in tbl.pagLink">
                                <template v-if="p.status == 1">
                                    <button class="btn btn-sm btn-active join-item" @click="getUsers(p.page)">
                                        {{ p.text }}
                                    </button>
                                </template>
                                <template v-else>
                                    <button class="btn btn-sm join-item" @click="getUsers(p.page)"
                                        :disabled="p.text == '...'">
                                        {{ p.text }}
                                    </button>
                                </template>
                            </template>
                            <button class="btn btn-sm btn-outline join-item" @click="getUsers(++tbl.page)"
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
        this.getUsers();
    },
    methods: {
        getUsers(p) {
            //alert(p);
            if (p == null) {
                this.tbl.page = 1;
            } else {
                this.tbl.page = p;
            }
            axios
                .get(
                    "/dorm/branches/user-table?page=" +
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
        updateAvailability(id, state) {
            // alert(id)
            // return;
            axios
                .post("/dorm/update-available", {
                    id: id,
                    state: state

                })
                .then((response) => {
                    //this.hasError = false;

                    this.getUsers();

                })
                .catch((error) => {
                    this.errors = [];

                    if (error.response.data.errors.name) {
                        this.errors.push(error.response.data.errors.name[0]);
                    }

                });
        },
        setStatus(r) {
            var role = '';

            switch (r) {
                case 0:
                    role = 'unconfirmed';
                    break;
                case 1:
                    role = 'confirmed';
                    break;
                case 2:
                    role = 'For eavaluation ';
                    break;

            }

            return role;
        },
        iniUpdate(id) {
            this.$router.push({
                path: `/dorm/${id}/edit`,
            });
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
