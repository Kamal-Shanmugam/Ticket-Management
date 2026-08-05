<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ticket Management System')</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header class="app-header">
        <div class="header-brand">
            <i class="fa-solid fa-ticket-simple"></i> SupportSphere
        </div>
        
        <div class="header-nav">
            @if(Auth::guard('employee')->check())
                @php $emp = Auth::guard('employee')->user(); @endphp
                <span class="header-user-info">
                    <i class="fa-solid fa-user-tie"></i> {{ $emp->name }} 
                    <span class="badge" style="background-color: var(--bg-tertiary);">{{ $emp->role->name }}</span>
                    @if($emp->department)
                        <span class="badge" style="background-color: var(--bg-primary);">{{ $emp->department->name }}</span>
                    @endif
                </span>
                
                <a href="{{ route('employee.dashboard') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
                
                <form action="{{ route('employee.logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            @elseif(Auth::guard('customer')->check())
                @php $cust = Auth::guard('customer')->user(); @endphp
                <span class="header-user-info">
                    <i class="fa-solid fa-user"></i> {{ $cust->name }} (Customer)
                </span>
                
                <a href="{{ route('customer.dashboard') }}" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-home"></i> Home
                </a>
                
                <form action="{{ route('customer.logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-right-to-bracket"></i> Login Portal
                </a>
            @endif
        </div>
    </header>

    <main class="container">
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
