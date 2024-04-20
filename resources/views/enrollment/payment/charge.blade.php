@extends('layouts.print')

@section('content')
<div style="font-family: Arial, Arial monospace sans-serif; margin-top: 160px; max-width: 400px;">

    <div>
        <table width="100%" style="font-size: 12px; text-align: right;" cellpadding="0" cellspacing="0">
            <tr>
                <td width="100">&nbsp;</td>
            </tr>
        </table>
    </div>

    <div style="margin-bottom: 25px; margin-top: -8px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="text-align: left; font-size: 14px; padding-left: 40px;">164</td>
                <td style="font-size: 12px;">{{ $or }}</td>
            </tr>
            <tr>
                <td style="text-align: left; font-size: 12px; padding-left: 40px; padding-top: 5px;" valign="top">
                    {{ $studentInfo->surname }}, {{ $studentInfo->firstname }} {{ $studentInfo->middlename }}
                    <br>
                    <span style="font-size: 9px;">{{ $studentInfo->student_number }} /
                        {{ $student_record->degree->abbr }}</span>
                </td>
                <td style="font-size: 12px; padding-top: 8px;" width="90" valign="top">
                    {{ \Carbon\Carbon::parse(date('Y-m-d'))->format('m/d/Y') }}
                </td>
            </tr>
        </table>
    </div>

    <div style="min-height: 180px; max-height: 200px; margin-top: 10px;">
        <table style="font-size: 9px; position: absolute;" width="380" cellpadding="0" cellspacing="0">

            <tbody>
                @foreach($payList as $payment)
                @php
                $payment = (object) $payment;
                @endphp
                <tr>
                    <td width="70" valign="top">{{ $payment->fund }}</td>
                    <td width="200" valign="top">{{ $payment->fund_desc }}</td>
                    <td width="100" style="text-align: right;" valign="top">
                        {{ number_format($payment->amount, 2, '.', ' ') }}
                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>
    </div>

    <table width="100%" style="margin-top: 10px;">
        <tr>
            <td>&nbsp;</td>
            <td style="font-size: 14px;" width="90">{{ number_format($amtPaid, 2) }}</td>
        </tr>
    </table>

    <table width="100%" style="margin-bottom: 25px;">
        <tr>
            <td width="110">&nbsp;</td>
            <td style="font-size: 10px;">{{ $converted }} Only</td>
        </tr>
    </table>

    <div style="margin-top: -10px;">&#10003;</div>

    <table width="100%" style="margin-top:75px;">
        <tr>
            <td style="text-align: left; font-size: 10px; padding-left: 220px;" width="50%">AMELITA U. PUNGTILAN</td>
        </tr>
    </table>

    <div style="margin-top: 20px; text-align: right; font-size: 8px;">{{ Auth::user()->name }}</div>
</div>
@stop

@section('js')
<script type="text/javascript">
    window.print();
    window.close();
</script>
@stop