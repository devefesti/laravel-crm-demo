<script>
    var role = @json(Session::get('role'));

    if (role == 3 || role == 4 || role == 1){
        window.location.href = '/admin/orders';
    }else if (role == null){
        window.location.href = '/admin/login';
    }
    
</script>
@extends('admin::errors.illustrated-layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('You don\'t have necessary permissions to perform this action.'))
