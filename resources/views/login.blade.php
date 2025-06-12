@extends('layouts.app')
@section('title', 'Login')

{{-- "borrowed" from honeycrisp :) --}}

@section('content')
<div class="py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Internal Login</h4>
                    </div>
                    <div class="card-body">
                        {{-- @if ( $errors->any() )
                            <div class="alert alert-danger">
                                @foreach ( $errors->all() as $error )
                                    <p class="mb-0">{{ $error }}</p>
                                @endforeach
                            </div>
                        @endif --}}
        
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <form method="POST" action="{{ route('login.submit') }}">
                                    @csrf
                        
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                                    </div>
                        
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                    </div>
                
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>
                        
                                    <button type="submit" class="btn btn-primary">Login</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>UConn Login</h4>
                    </div>
                    <div class="card-body">
                        <p class="mb-4">UConn users can login using their UConn NetID and password.</p>
                        <a href="{{ route('cas.login.trigger') }}" class="btn btn-primary">Login with NetID</a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

@endsection