<template>
    <div class="overflow-hidden overflow-x-auto min-w-full align-middle sm:rounded-md">
        <div class="flex w-full">
            <div class="card w-full bg-base-100 shadow-xl">
                <div class="card-body text-center">
                    <div class="flex justify-center">
                        <form @submit="searchSubmit">
                            <ul class="menu xl:menu-horizontal lg:min-w-max rounded-box shadow-xl">
                                <li>
                                    <div class="form-control">
                                        <!--  -->
                                        <label class="input-group input-group-sm">
                                            <span>Preferred Location</span>
                                            <select class="select select-primary w-full max-w-xs" v-model="select_loc"
                                                @change="selectChange">
                                                <option disabled selected>
                                                    Select Location
                                                </option>
                                                <template v-for="m in munis">
                                                    <option :value="m.loc_code">
                                                        {{ m.citymunDesc }}
                                                    </option>
                                                </template>
                                            </select>
                                            <!--  -->

                                            <!-- Vue Search List End-->
                                        </label>
                                    </div>
                                </li>
                                <li>
                                    <div class="form-control">
                                        <label class="input-group input-group-sm">
                                            <span>Dorm Type</span>
                                            <select class="select select-primary w-full"
                                                v-model="final_search.dorm_type">
                                                <template v-for="dt in dorm_type">
                                                    <option :value="dt.id">
                                                        {{ dt.des }}
                                                    </option>
                                                </template>
                                                <option :value="0">ALL</option>
                                            </select>
                                        </label>
                                    </div>
                                </li>
                                <li>
                                    <div class="form-control mt-4">
                                        <button class="btn btn-primary">
                                            Search
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </form>
                    </div>
                    <!-- card -->

                    <div class="flex place-content-center bg-base-100" v-if="dormfind.length > 0">
                        <div class="grid grid-cols-1 gap-4 w-5/6">
                            <template v-for="df in dormfind">
                                <div class="rounded-md w-full shadow-xl">
                                    <div class="card card-side bg-base-100 w-full">
                                        <figure>
                                            <img class="object-none h-48 w-96" :src="'/storage/dormimg/' +
                            df.filesystem_name
                            " alt="Movie" />
                                        </figure>
                                        <div class="card-body">

                                            <h2 class="card-title">
                                                {{ df.name }}
                                            </h2>

                                            <div class="flex justify-start">
                                                <strong>{{ df.dorm_type }}</strong>
                                            </div>

                                            <div class="flex justify-start">
                                                {{ df.address }}
                                            </div>
                                            <div class="text-wrap p-4" style="
                                                    text-align: justify;
                                                    text-justify: inter-word;
                                                " v-html="df.contact">
                                            </div>
                                            <template v-if="df.roomrates.length > 0">

                                                <div class="stats shadow">
                                                    <template v-for="rr in df.roomrates">
                                                        <div class="stat">
                                                            <div class="stat-title">
                                                                {{ rr.name }}
                                                            </div>
                                                            <div class="stat-value">₱ {{ formatPrice(rr.rate) }}</div>
                                                            <div class="stat-desc">{{ rr.total_avialable }} Unit
                                                                avialable, Good for {{ rr.persons }}</div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <div class="card-actions justify-end">
                                                <button class="btn btn-primary" @click="showTab(df.id)">
                                                    More
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div :id="df.id" class="w-full" style="display: none">
                                        <div role="tablist" class="tabs tabs-bordered">
                                            <a role="tab" :id="`info_tab${df.id}`" class="tab tab-active">Info</a>
                                            <!-- <a role="tab" :id="`photo_tab${df.id}`" class="tab"
                                                @click="clickTabs(df.id, 2)">Photos</a> -->
                                            <!-- <a role="tab" :id="`rev_tab${df.id}`" class="tab">Reviews</a> -->
                                        </div>
                                        <div class="flex" :id="`descon${df.id}`" v-if="currentTab == 1">
                                            <div class="text-wrap p-4" style="
                                                    text-align: justify;
                                                    text-justify: inter-word;
                                                " v-html="df.description"></div>
                                        </div>
                                        <div class="flex" :id="`descon${df.id}`" v-if="currentTab == 1">
                                            <div class="text-wrap p-4">
                                                <template v-if="df.availability == 1">
                                                    <button class="btn btn-neutral" @click="
                            reserveModal(df.id, df.roomrates)
                            ">
                                                        Reserve Now
                                                    </button>
                                                </template>
                                                <template v-else>
                                                    <button class="btn btn-error">
                                                        Not Available for
                                                        reservation
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                        <!--amenities  -->

                                        <div class="flex w-full">
                                            <!--  -->
                                            <div class="container mx-auto px-4">
                                                <h2 class="card-title">Amenities</h2>
                                                <div class="grid grid-cols-3 gap-4 mt-10">
                                                    <template v-for="a in df.amenities">
                                                        <div role="alert" class="alert shadow-lg"> <vue-feather
                                                                :type="a.icon" size="20"> </vue-feather>
                                                            <span> {{ a.description }}</span>

                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <!--  -->
                                        </div>
                                        <!-- amenities -->
                                        <div class="flex w-full" :id="`photocon${df.id}`">
                                            <!--  -->
                                            <div class="container mx-auto px-4">
                                                <div class="grid grid-cols-3 gap-4 mt-10">
                                                    <template v-for="p in df.photos">
                                                        <div class="bg-cover" @click="
                            viewImg(
                                p.id,
                                p.filesystem_name,
                                df.name
                            )
                            " v-bind:style="{
                            'background-image':
                                'url(/storage/dormimg/' +
                                p.filesystem_name +
                                ')',
                        }"></div>
                                                    </template>
                                                </div>
                                            </div>
                                            <!--  -->
                                        </div>
                                    </div>
                                </div>
                                <!--  -->

                                <!--  -->
                            </template>
                        </div>
                    </div>
                    <div class="flex place-content-center" v-if="statcode != 0">
                        <div role="alert" class="alert alert-error">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>No result found!</span>
                        </div>
                    </div>
                    <!-- card -->
                    <!--  -->
                    <template v-if="dormfind.length == 0">
                        <div class="carousel w-50">
                            <template v-for="fi in feature_img">
                                <div :id="`item${fi.id}`" class="carousel-item w-full">
                                    <img :src="`/storage/dormimg/${fi.filesystem_name}`"
                                        style="width: 100%; height: 450px" height="600" />
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-center w-full py-2 gap-2">
                            <template v-for="(fib, ind) in feature_img">
                                <a :href="`#item${fib.id}`" class="btn btn-xs">{{
                            ++ind
                        }}</a>
                            </template>
                        </div>
                    </template>
                    <!--  -->
                </div>
                <ReserveModal :reserve="reserve"></ReserveModal>
                <!-- footer -->
                <PubFooter></PubFooter>
                <!-- footer -->
                <!-- modal view img -->
                <input type="checkbox" id="view_pimg_status" class="modal-toggle" />
                <div class="modal">
                    <div class="modal-box w-11/12 max-w-5xl">
                        <h3 class="font-bold text-lg">
                            {{ dormname }}
                        </h3>

                        <div class="py-4">
                            <img class="prev-img" id="dormimgp" />
                        </div>
                        <div class="modal-action">
                            <label for="view_pimg_status" class="btn">Close!</label>
                        </div>
                    </div>
                </div>
                <!-- modal view img -->
            </div>
        </div>
    </div>
</template>

<script>
import { useToast } from "vue-toastification";
import Swal from "sweetalert2/dist/sweetalert2.js";
import PhotoGallery from "./PhotoGallery.vue";
import PubFooter from "./PubFooter.vue";
import ReserveModal from "./ReserveModal.vue";
export default {
    data() {
        return {
            reserve: {
                dorm_id: "",
                rates: []
            },
            sterm: "",

            posts: "",
            search: "",
            limitationList: 5,
            dorm_type: [],
            dormname: "",
            munis: [],
            final_search: {
                location_id: null,
                src: null,
                dorm_type: 0,
            },
            select_loc: null,
            dormimage: null,
            currentTab: 1,
            dormfind: [],
            feature_img: [],
            statcode: 0,
        };
    },
    mounted() {
        // this.searcAutosug();
        // setTimeout(() => {
        //     //this.fucusme()
        //     this.$refs.firstinput.focus();
        // }, 500);
        this.getDormType();
        this.getMuni();
        this.getFeatured();
    },
    setup() {
        const toast = useToast();

        // In case of a range picker, you'll receive [Date, Date]

        return {
            toast,
        };
    },
    // watch: {
    //     sterm: function (val) {
    //         this.sterm = val;

    //         if (this.sterm == "") {
    //             document.getElementById("widgets").style.display =
    //                 "list-item";
    //         }
    //         this.getPosts();

    //     },
    // },
    methods: {
        viewImg(id, systemfile, dorm) {
            document.getElementById("view_pimg_status").checked = true;
            document.getElementById(
                "dormimgp"
            ).src = `/storage/dormimg/${systemfile}`;
            this.dormname = dorm;
            systemfile;
        },
        searchSubmit(event) {
            event.preventDefault();
            this.statcode = 0;

            axios
                .post("/accommodationSearchQuery", {
                    datas: this.final_search,
                })
                .then((response) => {
                    //this.hasError = false;
                    if (response.data.statcode == 1) {
                        this.dormfind = response.data.result;
                        this.statcode = 0;
                    } else {
                        this.statcode = 1;
                        this.dormfind = [];
                    }
                })
                .catch((error) => {
                    this.errors = [];

                    console.log(error.response);
                });
        },
        selectChange() {
            var id = this.select_loc;
            var result = this.munis.find((item) => item.loc_code === id);

            this.final_search.location_id = result.loc_code;
            this.final_search.src = result.src;
        },
        //autosuggest start
        searcAutosug() {
            //start
            self = this;

            // get width of search input for vue search widget on initial load
            this.width = document.getElementById("firstinput").offsetWidth;
            // get width of search input for vue search widget when page resize
            window.addEventListener("resize", function (event) {
                self.width = document.getElementById("firstinput").offsetWidth;
            });

            // To clear vue search widget when click on body
            document.body.addEventListener("click", function (e) {
                self.clearData(e);
            });

            document
                .getElementById("firstinput")
                .addEventListener("keydown", function (e) {
                    // check whether arrow keys are pressed
                    if (_.includes([37, 38, 39, 40, 13], e.keyCode)) {
                        if (e.keyCode === 38 || e.keyCode === 40) {
                            // To prevent cursor from moving left or right in text input
                            document.getElementById("widgets").style.display =
                                "list-item";
                            e.preventDefault();
                        }

                        if (e.keyCode === 40 && self.posts == "") {
                            // If post list is cleared and search input is not empty
                            // then call ajax again on down arrow key press
                            document.getElementById("widgets").style.display =
                                "list-item";
                            // self.getPosts();

                            return;
                        }

                        self.selectPost(e.keyCode);
                    } else {
                        document.getElementById("widgets").style.display =
                            "list-item";
                        // self.getPosts();
                    }
                });
            //end
        },
        getPosts() {
            this.posts = "";
            this.count = 0;
            self = this;
            //this.isLoading = true;

            //     if (this.payment.student_number.trim() != "") {
            // this.isLoading = false;

            axios
                .post("/load-suggest", {
                    search: this.sterm,
                })
                .then(function (response) {
                    self.posts = response.data;
                })
                .catch(function (error) {
                    console.log(error);
                });
            //  }
        },
        selectPost: function (keyCode) {
            // If down arrow key is pressed

            if (keyCode == 40 && this.count < this.posts.length) {
                this.count++;
            }
            // If up arrow key is pressed
            if (keyCode == 38 && this.count > 1) {
                this.count--;
            }
            // If enter key is pressed
            if (keyCode == 13) {
                // Go to selected post
                document.getElementById(this.count).childNodes[0].click();
            }
        },
        clearData: function (e) {
            if (e.target.id != "firstinput") {
                (this.posts = ""), (this.count = 0);
            }
        },
        suggestClick(a, index) {
            this.errors = [];
            //alert(a);
            this.sterm = a;
            this.final_search.location_id = this.posts[index].loc_code;
            this.final_search.src = this.posts[index].src;
            document.getElementById("widgets").style.display = "none";
            setTimeout(() => {
                //this.fucusme()
                this.$refs.firstinput.focus();
            }, 100);
        },
        //end

        reserveModal(id, rates) {
            this.reserve.dorm_id = id;
            this.reserve.rates = rates;
            reserve_modal.showModal();
        },

        showTab(id) {
            var state = document.getElementById(id).style.display;

            if (state == "inline-block") {
                document.getElementById(id).style.display = "none";
            } else {
                document.getElementById(id).style.display = "inline-block";
            }

            // console.log(document.getElementById(id).style.display);
        },
        getFeatured() {
            // `/form/common-refprovince?page=${this.tbl.page}`

            axios.get(`/pub/dorm-featured`).then(({ data }) => {
                this.feature_img = data;
            });
        },
        getMuni() {
            // `/form/common-refprovince?page=${this.tbl.page}`

            axios.get(`/pub/muni-get`).then(({ data }) => {
                this.munis = data;
            });
        },
        getDormType() {
            // `/form/common-refprovince?page=${this.tbl.page}`

            axios.get(`/pub/dorm-type-get`).then(({ data }) => {
                this.dorm_type = data;
            });
        },

        formatPrice(value) {
            let val = (value / 1).toFixed(2).replace(".", ".");
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
    },

    components: {
        PubFooter,
        PhotoGallery,
        ReserveModal,
    },
    computed: {},
};
</script>
<style scoped>
/* Animations */
@keyframes fadeIn {
    0% {
        opacity: 0;
    }

    100% {
        opacity: 1;
    }
}

@keyframes slideIn {
    0% {
        transform: translateY(50px);
        opacity: 0;
    }

    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

.bg-gray-100 {
    background-color: #f7fafc;
}

.h-screen {
    height: 100vh;
}

.container {
    width: 100%;
    padding-right: 1rem;
    padding-left: 1rem;
    margin-right: auto;
    margin-left: auto;
}

.mt-10 {
    margin-top: 2.5rem;
}

/* 
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    grid-gap: 1rem;
    justify-items: center;
}

.grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.gap-4 {
    gap: 1rem;
} */

.bg-cover {
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    height: 200px;
    width: 100%;
    cursor: pointer;
}

.bg-cover:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease-in-out;
}

/* ani */
.bg-div {
    background-image: url("/img/fem_bldg.jpg");
    background-size: cover;
    color: black;
    /* position: relative; */
}

[v-cloak] {
    display: none;
}

.widget {
    border: 1px solid #c5c5c5;
    background: white;
    list-style: none;
}

.list_item_container {
    top: 51%;
    width: 93%;
    position: fixed;
    float: left;
    overflow: auto;
}

.vs__search {
    position: static !important;
}
</style>
