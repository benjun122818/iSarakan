import UserTable from "../components/admin/user/UserTable";
import SubsciberTbl from "../components/admin/subscriber/SubsciberTbl";
import AddUserForm from "../components/admin/user/UserForm";
import AdminBranchesTbl from "../components/admin/branch/DormitoriesTable";
import AdminBranchUpdateForm from "../components/admin/branch/BranchUpdateForm";

// import ExpenTable from "../components/admin/expenditures/ExpenTable";
// import ExpenUpdate from "../components/admin/expenditures/UpdateForm";

// import TrantypeTable from "../components/admin/transaction/TrantypeTable";

export default [
    {
        path: "/admin/users",
        name: "Manage User",
        component: UserTable,
    },
    {
        path: "/admin/subscribers",
        name: "Manage subscriber",
        component: SubsciberTbl,
    },
    {
        path: "/admin/add-users",
        name: "Create User",
        component: AddUserForm,
    },

    {
        path: "/admin/dorm/index",
        name: "Admin User Dorms",
        component: AdminBranchesTbl,
    },
    {
        path: "/admin/dorm/:id/:state",
        name: "Admin Branch Data",
        component: AdminBranchUpdateForm,
    },
];
