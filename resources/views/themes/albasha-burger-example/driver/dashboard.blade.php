@extends('themes.albasha-burger.layout')

@section('title', 'مركز قيادة التوصيل | '.$restaurant->name)

@section('content')
    <x-driver.manager-dashboard
        :restaurant="$restaurant"
        :metrics="$metrics"
        :drivers="$drivers"
        :active-drivers="$activeDrivers"
        :active-delivery-orders="$activeDeliveryOrders"
        :fleet-stats="$fleetStats"
        :map-routes="$mapRoutes"
        :map-drivers="$mapDrivers"
        :order-states="$orderStates"
    />
@endsection
