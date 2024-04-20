//import Registrar from '../registrar/Registrar';
import enrollmetIndex from "../components/enrollment/payment/PaymentIndex";
import Summary from "../components/enrollment/payment/Summary";
import Pay from "../components/enrollment/payment/Pay";
import payOnline from "../components/enrollment/payment/payOnline";
import payOnlineLBP from "../components/enrollment/payment/payOnlineLBP";
import SummaryCad from "../components/enrollment/cad/Summary";

export default [
    {
        path: "/enrollment-index",
        name: "Enrollment",
        component: enrollmetIndex,
    },
    {
        path: "/payment/enrollment/summary/:pref/:sn",
        name: "Payment Summary",
        component: Summary,
    },
    {
        path: "/payment/enrollment/pay/:mode/:srid/:pref",
        name: "Payment Details ",
        component: Pay,
    },
    {
        path: "/payment/enrollment/pay-online/:mode/:srid/:pref",
        name: "Online Payment Details",
        component: payOnline,
    },
    {
        path: "/payment/enrollment/pay-online-lbp/:mode/:srid/:pref",
        name: "LBP Deposit Details",
        component: payOnlineLBP,
    },
    {
        path: "/cad/summary/:id",
        name: "CAD Payment Summary",
        component: SummaryCad,
    },
];
