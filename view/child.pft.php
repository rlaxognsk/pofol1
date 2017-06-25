@extends('parent')

@section('header')
<h1>Header</h1>
<span>{{ $data1 }}</span>
@endsection

@section('section')
<p>Section</p>
<span>{{ $data2 }}</span>
@endsection

@section('footer')
<h3>Footer</h3>
<span>{{ $data3 }}</span>
@endsection