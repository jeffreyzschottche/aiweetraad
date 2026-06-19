@extends('emails.layout', ['subject' => 'Je wachtwoord is gewijzigd'])

@section('content')
    <h1 style="margin:0 0 12px;color:#0d3a3b;font-size:28px;line-height:1.15;font-weight:900;">Je wachtwoord is gewijzigd</h1>
    <p style="margin:0 0 16px;color:#5f565d;font-size:16px;line-height:1.7;">
        We bevestigen dat het wachtwoord van je AI Weet Raad-account zojuist is aangepast.
    </p>
    <p style="margin:0 0 16px;color:#5f565d;font-size:16px;line-height:1.7;">
        Was jij dit niet? Reset dan meteen opnieuw je wachtwoord en neem contact met ons op.
    </p>

    @include('emails.partials.button', ['url' => $url, 'label' => 'Inloggen'])
@endsection
