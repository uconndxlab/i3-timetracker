@extends('layouts.app')

@section('content')
<div class="app-page">
    <div class="app-page-hero">
        <h1 class="app-page-title">Register New User</h1>
        <p class="app-page-subtitle">Complete the required details to create access.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="app-panel">
                    <p class="text-muted mb-4">Please fill in the details below to register a new user.</p>

                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input id="name" class="form-control @error('name') is-invalid @enderror" 
                                    type="text" name="name" value="{{ old('name') }}" required autofocus />
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <input type="hidden" name="netid" value="{{ $netid }}">

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Register User
                            </button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>
@endsection