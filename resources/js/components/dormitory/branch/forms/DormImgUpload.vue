<template>
    <div>
        <div class="flex flex-col w-full pt-6">
            <hr />

            <div class="filezone">
                <input type="file" id="files" ref="dormimg" multiple @change="handleFiles()" />
                <p>Drop your files here / Choose a file</p>
            </div>

            <!--  -->

            <div class="container flex flex-wrap mx-auto p-2">
                <div class="w-full p-2 rounded lg:w-1/4 md:w-1/2" v-for="(file, key) in docsimg" v-if="file.id == null">
                    <!-- <iframe
                        class="w-full"
                        :src="'/ViewerJS/?zoom=page-width#../' + pdfsource[key]"
                        height="275vh"
                        :id="'thumb' + key"
                        style="display: block; border: 1px solid black"
                        align="center"
                    ></iframe> -->
                    <div style="display: block; border: 1px solid black">
                        <img class="prev-img" v-bind:ref="'preview' + parseInt(key)" />
                    </div>
                    <a class="remove" @click="removeFile(key)">&times;</a>

                    <small>{{ file.name }}</small>
                </div>
            </div>

            <!--  -->
        </div>

        <div class="form-group">
            <div>
                <button class="btn btn-sm btn-primary" type="submit" :disabled="this.docsimg.length <= 0"
                    @click="uploadDocs">
                    Upload
                </button>
            </div>
        </div>

        <div class="divider"></div>
        <h2 class="card-title">Dorm Images</h2>
        <div class="overflow-x-auto w-full">
            <!--  -->
            <div class="container flex flex-wrap mx-auto p-2">
                <template v-for="(d, key) in dormimgs">
                    <div class="w-full p-2 rounded lg:w-1/4 md:w-1/2">
                        <div class="w-64 avatar thumbnail">
                            <img class="prev-img" :src="'/storage/dormimg/' + d.filesystem_name" />
                        </div>
                        <div class="flex items-center space-x-3">
                            <div>
                                <div class="font-bold">
                                    {{ d.file_name }}

                                    <a class="remove" @click="unlinkFile(d.id, key)">&times;</a>

                                </div>
                                <button class="btn btn-wide btn-xs text-sm opacity-50" @click="
                                    viewDoc(
                                        d.id,
                                        d.filesystem_name,
                                        d.file_name,
                                        key
                                    )
                                    ">
                                    View
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <!--  -->
        </div>
        <!-- modal progress -->
        <input type="checkbox" id="upload_img_status" class="modal-toggle" />
        <div class="modal">
            <div class="modal-box w-11/12 max-w-5xl">
                <h3 class="font-bold text-lg">Uploading Please Wait!</h3>
                <progress class="progress progress-primary w-full" value="0" max="100" id="progresoimg"></progress>
                <p class="text-center">
                    processed {{ processProgress }} % of {{ processMax }}
                </p>
                <div class="modal-action">
                    <label for="my-modal-5" class="btn">Yay!</label>
                </div>
            </div>
        </div>
        <!-- modal progress -->
        <!-- modal view pdf -->
        <input type="checkbox" id="upload_dimg_status" class="modal-toggle" />
        <div class="modal">
            <div class="modal-box w-11/12 max-w-5xl">
                <h3 class="font-bold text-lg">
                    <input type="text" placeholder="Type here" class="input input-bordered input-primary w-full"
                        v-model="edit_fname.file_name" disabled />
                </h3>
                <button class="btn btn-info" @click="setPrimaPhoto()">Primary</button>
                <div class="py-4">
                    <img class="prev-img" id="dormimgs" />
                </div>
                <div class="modal-action">
                    <label for="upload_dimg_status" class="btn">Close!</label>
                </div>
            </div>
        </div>
        <!-- modal view pdf -->
    </div>
</template>

<script>

import { useToast } from "vue-toastification";
import Swal from "sweetalert2/dist/sweetalert2.js";
export default {
    props: ["dormimgs", "dormdetails", "supportingget", "canedit"],
    data() {
        return {
            docsimg: [],
            file: "",
            processCurrent: 0,
            processMax: 0,
            processProgress: 0,

            prev: null,
            pdfsource: [],
            view_fname: "",
            edit_fname: {},
            edit_fname_key: null,
        };
    },
    mounted() { },
    setup() {
        const toast = useToast();

        return {
            toast,
        };
    },
    methods: {
        viewDoc(id, systemfile, filename, index) {
            this.edit_fname = this.dormimgs[index];
            this.edit_fname_key = index;

            document.getElementById("upload_dimg_status").checked = true;
            document.getElementById("dormimgs").src = `/storage/dormimg/${systemfile}`;
            systemfile;

        },
        setPrimaPhoto() {
            var id = this.$route.params.id;

            axios
                .post("/dorm/photo/set-primary", {
                    id: this.edit_fname.id,
                    branch_id: this.edit_fname.dorm_branch_id,

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
                        this.geEditData();
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
                    this.errors = [];


                });
        },
        unlinkFile(key, index) {
            if (this.dormdetails.status == 1 || this.dormdetails.status == 2) {
                this.toast.error("Unable to upload!", {
                    position: "top-right",
                    timeout: 5000,
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
                return;
            }
            //
            Swal.fire({
                title: "Confirm removal of document?",
                text: "You can't revert your action",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes!",
                cancelButtonText: "No, I" + "'" + "ve change my mind!",
                showCloseButton: true,
                showLoaderOnConfirm: true,
            }).then((result) => {
                if (result.value) {
                    axios
                        .post("/form/unlink/dorm-img", {
                            id: key,
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
                                //this.supportingdocs.splice(index, 1);
                                this.supportingget();
                            } else {
                                Swal.fire({
                                    title: "Oops!",
                                    text: response.data.message,
                                    icon: "error",
                                });
                            }
                        })
                        .catch((err) => {
                            if (err.response) {
                                console.log(err.response);
                                this.toast.error(err.response.data.message, {
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
                } //if approve
            }); //then of swal
            //
        },
        handleFiles() {
            let uploadedFiles = this.$refs.dormimg.files, sumofdoc = 0;

            // if (this.docs.length >= 5) {
            //     return;
            // }

            for (var i = 0; i < uploadedFiles.length; i++) {

                sumofdoc = this.docsimg.length + this.dormimgs.length;

                if (sumofdoc >= 10) {
                    break;
                } else {
                    this.docsimg.push(uploadedFiles[i]);
                }

            }
            this.getImagePreviews();
        },
        getImagePreviews() {
            for (let i = 0; i < this.docsimg.length; i++) {
                if (/\.(jpe?g|png|gif)$/i.test(this.docsimg[i].name)) {
                    let reader = new FileReader();
                    reader.addEventListener(
                        "load",
                        function () {
                            this.$refs["preview" + parseInt(i)][0].src = reader.result;
                        }.bind(this),
                        false
                    );
                    reader.readAsDataURL(this.docsimg[i]);
                } else {
                    this.$nextTick(function () {
                        this.$refs["preview" + parseInt(i)][0].src = "/img/generic.png";
                    });
                }
            }
        },
        removeFile(key) {
            this.docsimg.splice(key, 1);
            this.getImagePreviews();
        },
        formatFileSize(bytes, decimalPoint) {
            if (bytes == 0) return "0 Bytes";
            var k = 1000,
                dm = decimalPoint || 2,
                sizes = [
                    "Bytes",
                    "KB",
                    "MB",
                    "GB",
                    "TB",
                    "PB",
                    "EB",
                    "ZB",
                    "YB",
                ],
                i = Math.floor(Math.log(bytes) / Math.log(k));
            return (
                parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) +
                " " +
                sizes[i]
            );
        },
        uploadDocs: async function () {

            if (this.dormdetails.status == 1 || this.dormdetails.status == 2) {
                this.toast.error("Unable to upload!", {
                    position: "top-right",
                    timeout: 5000,
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
                return;
            }

            let currentObj = this,
                formData = new FormData();
            var i = 0,
                s = currentObj.docsimg.length,
                branch_id = this.$route.params.id;

            document.getElementById("upload_img_status").checked = true;

            document.getElementById("progresoimg").value = 0;
            formData.append("branch_id", branch_id);
            currentObj.processMax = currentObj.docsimg.length;

            //console.log(data);

            for (i; i < currentObj.docsimg.length; i++) {
                currentObj.processCurrent = (await i) + 1;
                formData.append("file", currentObj.docsimg[i]);

                //
                if (i >= 0) {
                    const config = {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                        onUploadProgress: function (progressEvent) {
                            var percentCompleted = Math.round(
                                (currentObj.processCurrent * 100) / s
                            );
                            currentObj.processProgress = percentCompleted;

                            document.getElementById("progresoimg").value =
                                Math.round(
                                    (currentObj.processCurrent * 100) / s
                                );
                        },
                    };
                    await axios
                        .post(
                            "/form/upload/dorm-img",
                            formData,
                            config
                        )
                        .then(async (res) => {
                            if (res.data.statcode == 1) {
                                // this.toast.success(res.data.message, {
                                //     position: "top-right",
                                //     timeout: 2000,
                                //     closeOnClick: true,
                                //     pauseOnFocusLoss: true,
                                //     pauseOnHover: true,
                                //     draggable: true,
                                //     draggablePercent: 0.6,
                                //     showCloseButtonOnHover: false,
                                //     closeButton: "button",
                                //     icon: true,
                                //     rtl: false,
                                // });
                            } else {


                                document.getElementById(
                                    "upload_img_status"
                                ).checked = false;

                            }
                            if (currentObj.processCurrent == s) {
                                document.getElementById(
                                    "upload_img_status"
                                ).checked = false;

                                currentObj.docsimg = [];
                                this.supportingget();
                                console.log("ok");
                            }
                        })
                        .catch(async (err) => {
                            if (err.response) {
                                console.log(err.response);
                                this.toast.error(err.response.data.message, {
                                    position: "top-right",
                                    timeout: 5000,
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

                                // document.getElementById(
                                //     "upload_status"
                                // ).checked = false;
                            }
                            if (currentObj.processCurrent == s) {
                                document.getElementById(
                                    "upload_img_status"
                                ).checked = false;
                                this.supportingget();
                                currentObj.docsimg = [];
                            }
                        });
                }
                //endfor
            }
            //
        },

        submitFname() {
            axios
                .post("/office/document/transaction-support-update-name", {
                    id: this.edit_fname.id,
                    fname: this.edit_fname.file_name,
                })
                .then((response) => {
                    //this.hasError = false;
                    if (response.data.statcode == 1) {
                        document.getElementById(
                            "view_pdf_modal"
                        ).checked = false;

                        setTimeout(() => {
                            this.viewDoc(
                                this.edit_fname.id,
                                this.edit_fname.file_system,
                                this.edit_fname.file_name,
                                this.edit_fname_key
                            );
                        }, 200);
                    } else {
                        Swal.fire({
                            title: "Oops!",
                            text: response.data.message,
                            icon: "error",
                        });
                    }
                })
                .catch((err) => {
                    if (err.response) {
                        console.log(err.response);
                        this.toast.error(err.response.data.message, {
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

                        // document.getElementById(
                        //     "upload_status"
                        // ).checked = false;
                    }
                });
        },
    },
    components: {

    },
    computed: {},
};
</script>

<style scoped>
input[type="file"] {
    opacity: 0;
    width: 100%;
    height: 200px;
    position: absolute;
    cursor: pointer;
}

.thumbnail {
    position: relative;
    padding: 0px;
    margin-bottom: 20px;
}

.responsive-iframe {
    display: block;
    border: 1px solid black;
    width: 100%;
}

.filezone {
    outline-offset: -10px;
    background: #ccc;
    color: dimgray;
    padding: 10px 10px;
    min-height: 200px;
    position: relative;
    cursor: pointer;
}

.filezone:hover {
    background: #c0c0c0;
}

.filezone p {
    font-size: 1.2em;
    text-align: center;
    padding: 50px 50px 50px 50px;
}

a.remove {
    cursor: pointer;
    font-weight: 700;
    color: red;
    background-color: #e1e1e1;
    padding: 2px 5px;
    border: none;
}

.container img {
    border: 1px solid #ddd;
    /* Gray border */
    border-radius: 4px;
    /* Rounded border */
    padding: 5px;
    /* Some padding */
    width: 150px;
    /* Set a small width */
    /* position: relative; */
}

.bb {
    overflow-wrap: break-word;
    /* apply background-image url inline on the element since it is part of the content */
    /* background: transparent url() no-repeat center center; */
}

.bb a {
    display: none;
    position: absolute;
    top: 0 px;
    /* position in bottom right corner, assuming image is 16x16 */
    left: 84px;
    width: 16px;
    height: 16px;
    /* background: transparent url(remove_button.gif) no-repeat 0 0; */
}

.bb:hover a {
    display: block;
}
</style>
