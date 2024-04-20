<template>
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-3 gap-4 mt-10">
            <div class="bg-cover"
                style="background-image: url(https://images.unsplash.com/photo-1611078489935-0cb964de46d6?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=774&q=80)">
            </div>
            <div class="bg-cover"
                style="background-image: url(https://images.unsplash.com/photo-1593642702821-c8da6771f0c6?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1032&q=80)">
            </div>
            <div class="bg-cover"
                style="background-image: url(https://images.unsplash.com/photo-1542393545-10f5cde2c810?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=465&q=80)">
            </div>
            <div class="bg-cover"
                style="background-image: url(https://images.unsplash.com/photo-1580927752452-89d86da3fa0a?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=870&q=80)">
            </div>
            <div class="bg-cover"
                style="background-image: url(https://images.unsplash.com/photo-1552857131-0afaed1953f5?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=870&q=80)">
            </div>
            <div class="bg-cover"
                style="background-image: url(https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=464&q=80)">
            </div>
        </div>
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
}

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
}</style>
