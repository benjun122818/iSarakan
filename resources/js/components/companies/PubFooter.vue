<template>
    <div class="flex flex-col w-full">
        <footer class="footer p-10 bg-base-200 text-base-content">
            <form @submit="userSubmit">
                <h6 class="footer-title">Be a Dorm provider in mmsu</h6>
                <fieldset class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Enter your email address</span>
                    </label>
                    <div class="join">
                        <input type="text" placeholder="username@site.com" class="input input-bordered join-item"
                            v-model="user.email" />

                    </div>
                </fieldset>
                <fieldset class="form-control w-full">
                    <label class="label">
                        <span class="label-text">Enter your name</span>
                    </label>
                    <div class="join">
                        <input type="text" placeholder="Your Name" class="input input-bordered join-item"
                            v-model="user.name" />
                        <button class="btn btn-primary join-item" type="submit">Subscribe</button>
                    </div>

                </fieldset>
            </form>

            <nav>
                <h6 class="footer-title">Company</h6>
                <a href="https://www.mmsu.edu.ph/" class="link link-hover">Mariano Marcos State University</a>

            </nav>


        </footer>
    </div>
</template>

<script>
import { ref } from "vue";
import { useToast } from "vue-toastification";
import Swal from "sweetalert2/dist/sweetalert2.js";
export default {
    props: ["dvget", "dvdetails", "soetotal", "validateTran", "canedit"],
    data() {
        return {
            user: {
                email: null,
                name: null,

            },



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
        userSubmit(event) {
            event.preventDefault();

            axios
                .post("/user/subscribe", {
                    email: this.user.email,
                    name: this.user.name,
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

                    console.log(error.response);


                    if (error.response.data.errors.email) {
                        //  this.errors.push(error.response.data.errors.parti[0]);
                        this.toast.error(error.response.data.errors.email[0], {
                            position: "top-right",
                            timeout: 3000,
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
                    if (error.response.data.errors.name) {
                        this.toast.error(error.response.data.errors.name[0], {
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
