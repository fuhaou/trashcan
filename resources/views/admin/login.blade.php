@extends('layouts.default')
@section('title', 'Đăng nhập')
@section('content')
<style>
    input, button {
        font-size: 24px !important;
    }
</style>
    <div class="card text-center align-middle p-3" style="max-width:480px; margin: auto">
        <form action="/admin/login" method="post">
            <h1>Đăng nhập</h1>
            <label class="m-1">
                <input name="username" type="text" class="form-control shadow-none" placeholder="Username"/>
            </label><br>
            <label class="m-1">
                <input name="password" type="password" class="form-control shadow-none" placeholder="Password"/>
            </label><br>
            <button class="form-control btn btn-primary m-1" type="submit">Đăng nhập</button>
        </form>
    </div>


@endsection
