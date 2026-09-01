{{--
    業者への通知メール本文。現行 felix_total の mail/required_file/body.blade.php を踏襲する。

    @var string $companyName 宛名（会社名）
    @var string $keisho      敬称（空なら「御中」）
    @var string $name        物件名（空なら会社差出の文面になる）
    @var string $exp         依頼文（例: 見積内容のご確認と発注承諾をお願い致します。）
    @var string $url         項目名＋リンクのブロック（項目数ぶん改行で連結済み）
--}}
{{ $companyName }}@if ($keisho !== '') {{ $keisho }} @else 御中 @endif<br>
<br>
いつもお世話になっております。<br>
@if ($name !== '')
フィリックス不動産事業部です。<br>
<br>
[{{ $name }}]について{{ $exp }}<br>
@else
フィリックス株式会社です。<br>
{{ $exp }}<br>
@endif

<br>

{!! nl2br(e($url)) !!}

<br>
※URLがクリックできない場合はコピーしてChromeやEdgeといったブラウザを起動し、URL欄に貼り付けしてEnterを押し開いて下さい。<br>
<br>

以上、何卒宜しくお願い致します。<br>
<br>
@include('mail.partials.foot')
