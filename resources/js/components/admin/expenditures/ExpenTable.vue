<template>
    <div class="p-0 w-full">
        <div class="card w-full bg-base-100 shadow-xl">
            <div class="card-body">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h2 class="card-title">Expenditures</h2>
                    </div>
                    <div class="card-actions justify-end">
                        <button
                            class="btn btn-sm btn-info"
                            @click="initNewExpen"
                        >
                            Add Expenditure +
                        </button>
                    </div>
                </div>
                <!--  -->
                <div class="grid grid-flow-row-dense grid-cols-3 grid-rows-1">
                    <div class="col-span-2 p-2">
                        <div class="form-control">
                            <label class="input-group input-group-sm">
                                <span>Search</span>
                                <input
                                    type="text"
                                    placeholder="Type here"
                                    class="input input-bordered input-primary w-full"
                                    id="filterbox"
                                    v-model="tbl.search"
                                    v-on:input="tblData(tbl.page)"
                                />
                            </label>
                        </div>
                    </div>
                    <div class="w-full p-2 text-right">
                        <div class="form-control justify-end">
                            <label class="input-group input-group-sm">
                                <span>entries</span>
                                <select
                                    class="select select-primary w-full max-w-xs"
                                    v-model="tbl.entries"
                                    @change="tblData()"
                                >
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
                                <th>Description</th>
                                <th>RCA Code</th>
                                <th>UACS Sub-Object Code</th>
                                <th>UACS Object Code</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="(x, index) in expenditures">
                                <tr class="hover">
                                    <td>{{ x.id }}</td>
                                    <td>{{ x.desc }}</td>
                                    <td>{{ x.rca_code }}</td>
                                    <td>{{ x.uacs_sub_object_code }}</td>
                                    <td>{{ x.uacs_object_code }}</td>
                                    <td style="width: 10%">
                                        <div class="dropdown dropdown-left">
                                            <label
                                                tabindex="0"
                                                class="btn btn-sm"
                                                >Action</label
                                            >
                                            <ul
                                                tabindex="0"
                                                class="dropdown-content menu shadow bg-base-100 p-2 rounded-box w-32"
                                            >
                                                <li>
                                                    <div class="p-2">
                                                        <button
                                                            class="link link-hover"
                                                            @click="
                                                                viewBudgetReq(
                                                                    x.id
                                                                )
                                                            "
                                                        >
                                                            View
                                                        </button>
                                                    </div>
                                                </li>
                                                <li>
                                                    <div class="p-2">
                                                        <button
                                                            class="link link-hover"
                                                            @click="
                                                                editExpen(x.id)
                                                            "
                                                        >
                                                            Edit
                                                        </button>
                                                    </div>
                                                </li>

                                                <li>
                                                    <div class="p-2">
                                                        <button
                                                            class="link link-hover"
                                                            @click="
                                                                deleteBudget(
                                                                    x.id
                                                                )
                                                            "
                                                            :disabled="
                                                                x.status_code ==
                                                                    2 ||
                                                                x.status_code ==
                                                                    4
                                                            "
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!--  -->
                <div
                    class="grid grid-flow-row-dense grid-cols-2 grid-rows-1 p-2"
                >
                    <div>
                        <div class="btn-group">
                            <!-- <template v-if="tbl.page >= 2"> -->
                            <button
                                class="btn btn-sm btn-outline"
                                @click="tblData(tbl.page - 1)"
                                :disabled="tbl.page <= 1"
                            >
                                Prev
                            </button>
                            <!-- </template> -->
                            <template v-for="p in tbl.pagLink">
                                <template v-if="p.status == 1">
                                    <button
                                        class="btn btn-sm btn-active"
                                        @click="tblData(p.page)"
                                    >
                                        {{ p.text }}
                                    </button>
                                </template>
                                <template v-else>
                                    <button
                                        class="btn btn-sm"
                                        @click="tblData(p.page)"
                                        :disabled="p.text == '...'"
                                    >
                                        {{ p.text }}
                                    </button>
                                </template>
                            </template>
                            <button
                                class="btn btn-sm btn-outline"
                                @click="tblData(++tbl.page)"
                                :disabled="tbl.page >= this.tbl.total_pages"
                            >
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
    <ModalInputForm :tblData="tblData"></ModalInputForm>
</template>

<script>
import Swal from "sweetalert2/dist/sweetalert2.js";
import ModalInputForm from "./ExpenAddForm.vue";
export default {
    data() {
        return {
            expenditures: [],

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
        this.tblData();
    },
    methods: {
        editExpen(id) {
            this.$router.push({
                path: `/admin/expenditures-update/${id}`,
            });
        },
        tblData(p) {
            //alert(p);
            if (p == null) {
                this.tbl.page = 1;
            } else {
                this.tbl.page = p;
            }
            axios
                .get(
                    "/admin/expen-table?page=" +
                        this.tbl.page +
                        "&entries=" +
                        this.tbl.entries +
                        "&search=" +
                        this.tbl.search
                )
                .then(({ data }) => {
                    this.expenditures = data.records;
                    this.tbl.pagLink = data.pagLink;
                    this.tbl.page = data.current_page;
                    this.tbl.all_records = data.all_records;
                    this.tbl.total_records = data.total_records;
                    this.tbl.total_pages = data.total_pages;
                });
        },

        initNewExpen() {
            document.getElementById("add_form_modal").checked = true;
            // setTimeout(() => {
            document.getElementById("form_add_ex").reset();
            document.getElementById("additem").focus();
            // }, 600);
        },
    },

    components: {
        ModalInputForm,
    },
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
