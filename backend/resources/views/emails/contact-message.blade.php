@extends('emails.layout', ['subject' => 'Nieuw contactbericht'])

@section('content')
    <h1 style="margin:0 0 12px;color:#0d3a3b;font-size:28px;line-height:1.15;font-weight:900;">Nieuw contactbericht</h1>
    <p style="margin:0 0 18px;color:#5f565c;font-size:16px;line-height:1.7;">
        Er is een bericht verstuurd via het contactformulier van AI Weet Raad.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px;border-collapse:collapse;">
        <tr>
            <td style="padding:10px 0;color:#7d747a;font-size:13px;font-weight:700;width:110px;">Naam</td>
            <td style="padding:10px 0;color:#0d3a3b;font-size:15px;font-weight:800;">{{ $contactMessage->name }}</td>
        </tr>
        <tr>
            <td style="padding:10px 0;color:#7d747a;font-size:13px;font-weight:700;">E-mail</td>
            <td style="padding:10px 0;color:#0d3a3b;font-size:15px;font-weight:800;">{{ $contactMessage->email }}</td>
        </tr>
        <tr>
            <td style="padding:10px 0;color:#7d747a;font-size:13px;font-weight:700;">Onderwerp</td>
            <td style="padding:10px 0;color:#0d3a3b;font-size:15px;font-weight:800;">{{ $contactMessage->subject ?: 'Geen onderwerp' }}</td>
        </tr>
    </table>

    <div style="border:1px solid #d2f0f1;border-radius:18px;background:#f5ffff;padding:18px;color:#3e363c;font-size:15px;line-height:1.7;white-space:pre-wrap;">{{ $contactMessage->message }}</div>
@endsection
