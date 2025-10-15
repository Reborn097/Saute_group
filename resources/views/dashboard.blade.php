@extends('layouts.app')

@section('content')
<div style="background-color:#FAEBDD; min-height:100vh; display:flex; justify-content:center; align-items:center; flex-direction:column;">
    <h1 style="color:#A72920; font-family:'Figtree', sans-serif;">Bienvenido al Panel Sauté Group</h1>
    <p style="color:#333;">Has iniciado sesión correctamente</p>
    <a href="{{ route('dashboard.admin') }}" 
       style="background-color:#A72920; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; margin-top:20px;">
       Ir al menú principal
    </a>
</div>
@endsection
