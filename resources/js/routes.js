import adminRoute from "./composables/adminRoute";
import dormitoryRoute from "./composables/dormitoryRoute";
//import enrollmentRoute from "./composables/enrollmetRoute";
import CompaniesIndex from "./components/companies/CompaniesIndex";
import PubIndex from "./components/companies/PubIndex";
import Asuggest from "./components/companies/Asuggest";

export const routes = [
    ...adminRoute,
    ...dormitoryRoute,
    //...enrollmentRoute,
    {
        path: "/",
        name: "Pub",
        component: PubIndex,
    },
    {
        path: "/dashboard",
        name: "HOME",
        component: CompaniesIndex,
    },
    {
        path: "/test-suggest",
        name: "Suggest",
        component: Asuggest,
    },
];
