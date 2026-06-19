@extends('emails.layout', ['subject' => 'Wachtwoord resetten'])

@section('content')
    <h1 style="margin:0 0 12px;color:#0d3a3b;font-size:28px;line-height:1.15;font-weight:900;">Wachtwoord vergeten?</h1>
    <p style="margin:0 0 16px;color:#5f565d;font-size:16px;line-height:1.7;">
        We hebben een verzoek ontvangen om je wachtwoord opnieuw in te stellen. Kies via onderstaande knop een nieuw wachtwoord.
    </p>

    @include('emails.partials.button', ['url' => $url, 'label' => 'Nieuw wachtwoord kiezen'])

    <p style="margin:0 0 10px;color:#7d747a;font-size:14px;line-height:1.7;">
        Deze link verloopt over 60 minuten.
    </p>
    <p style="margin:0;color:#7d747a;font-size:14px;line-height:1.7;">
        Heb jij dit niet aangevraagd? Dan hoef je niets te doen.
    </p>
@endsection
