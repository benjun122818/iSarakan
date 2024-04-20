import BranchesTbl from "../components/dormitory/branch/DormitoriesTable";
import BranchDataForm from "../components/dormitory/branch/BranchDataForm";
import BranchUpdateForm from "../components/dormitory/branch/BranchUpdateForm";
import ReservationTable from "../components/dormitory/reservation/ReservationTable";

// import TrantypeTable from "../components/admin/transaction/TrantypeTable";

export default [
    {
        path: "/dorm/index",
        name: "User Dorms",
        component: BranchesTbl,
    },
    {
        path: "/dorm/reservation/index",
        name: "Dorm Reservation",
        component: ReservationTable,
    },
    {
        path: "/dorm/form/:state",
        name: "User Branch Data Form",
        component: BranchDataForm,
    },
    {
        path: "/dorm/:id/:state",
        name: "User Branch Data Edit Form",
        component: BranchUpdateForm,
    },
];
