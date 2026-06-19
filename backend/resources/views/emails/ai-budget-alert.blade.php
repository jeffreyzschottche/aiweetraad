@extends('emails.layout', ['subject' => $subjectText])

@section('content')
    <h1 style="margin:0 0 12px;color:#0d3a3b;font-size:28px;line-height:1.15;font-weight:900;">AI-provider alert</h1>
    <p style="margin:0 0 16px;color:#5f565d;font-size:16px;line-height:1.7;">
        De AI-generatie heeft aandacht nodig. Hieronder staat de technische status zodat je snel kunt bijsturen.
    </p>

    <pre style="margin:22px 0 0;padding:18px;background:#0d3a3b;color:#e8fbfb;border-radius:18px;white-space:pre-wrap;font-size:13px;line-height:1.55;font-family:Menlo,Consolas,monospace;">{{ $bodyText }}</pre>
@endsection
