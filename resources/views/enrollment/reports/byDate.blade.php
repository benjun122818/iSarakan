@extends('layouts.app')

@section('content')

    <div class="container">
        <div class="row">

            <div class="panel panel-primary">
                <div class="panel-heading">Generate Transactions by Date</div>
                <div class="panel-body">

                    <div class="panel panel-default">
                        <div class="panel-body text-right">

                            {!! Form::open(['class'=>'form-inline', 'method'=>'get']) !!}
                            <div class="form-group">
                                <label for="">Select Date Range</label>
                                {!! Form::text('rFrom', null, ['class'=>'form-control', 'id'=>'start', 'placeholder'=>'Report Start...']) !!}
                                {!! Form::text('rTo', null, ['class'=>'form-control', 'id'=>'end', 'placeholder'=>'Report End...']) !!}
                                {!! Form::select('mode', [1=>'Account Report', '2'=>'Branch Report'], $mode, ['class'=>'form-control']) !!}
                                {!! Form::button('Generate', ['class'=>'btn btn-primary', 'type'=>'submit']) !!}
                            </div>
                            {!! Form::close() !!}

                        </div>
                    </div>

                    @if($orRecords->count() > 0)
                        <div class="text-right btn-group">
                            <a class="btn btn-primary" href="{{ route('enrReportByDatePrint', ['fr'=>$from, 'to'=>$to, 'mode'=>$mode]) }}">Download</a>
                            <a class="btn btn-warning" href="{{ route('cashreceiptrecordprint', ['fr'=>$from, 'to'=>$to, 'mode'=>$mode]) }}">Cash receipt record</a>
                        </div>

                        <br>

                        <table class="table table-condensed table-hover">
                            <thead>
                            <tr>
                                <th width="10">&nbsp;</th>
                                <th width="10">ID</th>
                                <th>OR</th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th class="text-right">Amount</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($orRecords as $or)
                                <tr>
                                    <td>{{ $reportCtr++ }}</td>
                                    <td>{{ $or->id }}</td>
                                    <td>{{ $or->or_number }}</td>
                                    <td>{{ \Carbon\Carbon::parse($or->created_at)->format('Y-m-d') }}</td>
                                   
                                    <td>{{ (!is_null($or->studentInfo) ? $or->studentInfo->surname.', '.$or->studentInfo->firstname : $or->payor_name) }}</td>
                                   
                                    <td>
                                    @if($or->source == 0)

                                        @if($or->status == 0)
                                            Cancelled
                                        @elseif($or->status == 1)
                                            Active
                                        @elseif($or->status == 3)
                                            Transfered
                                        @endif

                                    @else
                                       ------                     
                                    @endif
                                    </td>
                                    <td>{{($or->source == 0 ? 'ENROLLMENT' : 'NONENROLLMENT')}}</td>
                                    <td class="text-right">
                                        @if($or->status == 1)
                                            &#8369; {{ number_format($or->amount, 2) }}
                                        @else
                                        &#8369; {{ number_format(0, 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="8" class="text-right"><strong>Total</strong> &#8369; {{ $totalcash }}</td>
                            </tr>
                            </tfoot>
                        </table>

                        <div class="text-right btn-group">
                            <a class="btn btn-primary" href="{{ route('enrReportByDatePrint', ['fr'=>$from, 'to'=>$to, 'mode'=>$mode]) }}">Download</a>
                            <a class="btn btn-warning" href="{{ route('cashreceiptrecordprint', ['fr'=>$from, 'to'=>$to, 'mode'=>$mode]) }}">Cash receipt record</a>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>

@endsection

@section('js')
    <script>
        $(function(){
            var checkin = $('#start').datepicker({
                format: 'yyyy-mm-dd'
            }).on('changeDate', function() {
                $('#start').datepicker('hide');
            }).data('datepicker');

            var checkout = $('#end').datepicker({
                format: 'yyyy-mm-dd'
            }).on('changeDate', function(ev) {
                $('#end, #start').datepicker('hide');
            }).on('onRender', function(ev){
                $('#start').datepicker('hide');
            }).data('datepicker');
        })
    </script>
@endsection
