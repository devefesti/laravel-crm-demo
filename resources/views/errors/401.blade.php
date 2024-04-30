@extends('errors::minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('Unauthorized'))

<script>
    var role = @json(Session::get('role'));

    if (role == 3 || role == 4 || role == 1){
        window.location.href = '/admin/orders';
    /* else if (role == 1){

    } */
    }else if (role == null){
        window.location.href = '/admin/login';
    }
    
</script>