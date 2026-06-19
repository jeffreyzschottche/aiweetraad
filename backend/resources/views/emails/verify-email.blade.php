@extends('emails.layout', ['subject' => 'Bevestig je e-mailadres'])

@section('content')
    <h1 style="margin:0 0 12px;color:#0d3a3b;font-size:28px;line-height:1.15;font-weight:900;">Welkom bij AI Weet Raad</h1>
    <p style="margin:0 0 16px;color:#5f565d;font-size:16px;line-height:1.7;">
        Bevestig je e-mailadres om je account volledig actief te maken. Daarna kun je vragen stellen, antwoorden vergelijken en stemmen op wat echt helpt.
    </p>

    @include('emails.partials.button', ['url' => $url, 'label' => 'E-mailadres bevestigen'])

    <p style="margin:0;color:#7d747a;font-size:14px;line-height:1.7;">
        Heb jij dit account niet aangemaakt? Dan kun je deze e-mail negeren.
    </p>
@endsection
