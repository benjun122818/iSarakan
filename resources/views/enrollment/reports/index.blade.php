@extends('layouts.app')

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-primary">
                    <div class="panel-heading">Enrollment Payment Summary</div>
                    <div class="panel-body">
                        <fieldset>
                            <legend>Daily Cash Transaction <span class="text-danger"><strong>&#8369;  {{ number_format($totalTransactionsToday, 2) }}</strong></span></legend>
                            <legend>Total Semester Transaction <span class="text-success"><strong>&#8369;  {{ number_format($totalSemesterTransactions->total, 2) }}</strong></span></legend>
                        </fieldset>
                    </div>
                </div>

                <div class="panel panel-primary">
                    <div class="panel-heading">Generate Report</div>
                    <div class="panel-body">
                        <fieldset>
                            <legend>Transactions</legend>
                            <ul class="nav nav-pills">
                                <li role="presentation"><a href="{{ route('enrReportByDate') }}">Generate by Date</a></li>
                                <li role="presentation"><a href="#">Generate Overall</a></li>
                            </ul>
                        </fieldset>
                        <br>
                        <fieldset>
                            <legend>Enrollment</legend>

                            <ul class="nav nav-pills">
                                <li role="presentation"><a href="#">Officially Enrolled</a></li>
                            </ul>
                        </fieldset>
                     
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection