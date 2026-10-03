@extends('themes.classic-2-burger.layout')

@section('title', 'لوحة التوصيل والمندوبين | '.$restaurant->name)

@section('content')
    <x-driver.manager-dashboard :restaurant="$restaurant" :metrics="$metrics" :drivers="$drivers" :dispatched-orders="$dispatchedOrders" />
@endsection
