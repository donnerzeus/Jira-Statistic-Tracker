<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unified Analytics Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            /* Brand Colors - Sabancı University */
            --color-primary: #002D72;
            --color-primary-dark: #001f52;
            --color-primary-light: #0057B8;
            
            /* Semantic Colors */
            --color-success: #10B981;
            --color-success-light: #D1FAE5;
            --color-warning: #F59E0B;
            --color-warning-light: #FEF3C7;
            --color-danger: #EF4444;
            --color-danger-light: #FEE2E2;
            --color-info: #3B82F6;
            --color-info-light: #DBEAFE;
            
            /* Neutral Colors */
            --color-white: #FFFFFF;
            --color-gray-50: #F9FAFB;
            --color-gray-100: #F3F4F6;
            --color-gray-200: #E5E7EB;
            --color-gray-300: #D1D5DB;
            --color-gray-400: #9CA3AF;
            --color-gray-500: #6B7280;
            --color-gray-600: #4B5563;
            --color-gray-700: #374151;
            --color-gray-800: #1F2937;
            --color-gray-900: #111827;
            
            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-full: 9999px;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            /* Typography */
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-lg: 1.125rem;
            --font-size-xl: 1.25rem;
            --font-size-2xl: 1.5rem;
            --font-size-3xl: 1.875rem;
            --font-size-4xl: 2.25rem;
        }
        
        /* Utility Classes */
        .card {
            background-color: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            box-shadow: var(--shadow-xl);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background-color: var(--color-primary);
            color: var(--color-white);
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: var(--font-size-sm);
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background-color: var(--color-primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--color-white);
            color: var(--color-primary);
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-weight: 500;
            font-size: var(--font-size-sm);
            border: 1px solid var(--color-gray-300);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background-color: var(--color-gray-50);
        }
        
        .badge-success {
            background-color: var(--color-success-light);
            color: var(--color-success);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
        }
        
        .badge-warning {
            background-color: var(--color-warning-light);
            color: var(--color-warning);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
        }
        
        .badge-danger {
            background-color: var(--color-danger-light);
            color: var(--color-danger);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
        }
        
        .text-primary { color: var(--color-primary); }
        .text-success { color: var(--color-success); }
        .text-warning { color: var(--color-warning); }
        .text-danger { color: var(--color-danger); }
        .text-muted { color: var(--color-gray-500); }
        
        .bg-primary { background-color: var(--color-primary); }
        .bg-success { background-color: var(--color-success); }
        .bg-warning { background-color: var(--color-warning); }
        .bg-danger { background-color: var(--color-danger); }
        
        .cursor-pointer { cursor: pointer; }
        
        /* Dark Mode */
        [data-theme="dark"] {
            --color-gray-50: #1a1a1a;
            --color-gray-100: #2d2d2d;
            --color-gray-200: #404040;
            --color-gray-300: #525252;
            --color-gray-400: #737373;
            --color-gray-500: #a3a3a3;
            --color-gray-600: #d4d4d4;
            --color-gray-700: #e5e5e5;
            --color-gray-800: #f5f5f5;
            --color-gray-900: #ffffff;
            --color-white: #1a1a1a;
            --color-black: #ffffff;
        }
        
        [data-theme="dark"] body {
            background-color: #0f0f0f;
            color: #ffffff;
        }
        
        [data-theme="dark"] .card {
            background-color: #1a1a1a;
            border: 1px solid #2d2d2d;
        }
        
        [data-theme="dark"] .text-primary {
            color: #60a5fa !important;
        }
        
        /* Loading Skeleton */
        @keyframes skeleton-loading {
            0% { background-color: var(--color-gray-200); }
            50% { background-color: var(--color-gray-300); }
            100% { background-color: var(--color-gray-200); }
        }
        
        .skeleton {
            animation: skeleton-loading 1.5s ease-in-out infinite;
            border-radius: var(--radius-md);
        }
        
        /* Notification Animations */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* Print Styles */
        @media print {
            body { background-color: white !important; }
            .btn-primary, .btn-secondary { display: none !important; }
            .card { page-break-inside: avoid; }
            nav { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <span class="text-xl font-bold text-brand-primary">JiraPortal</span>
                        </div>
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('dashboard') ? 'border-brand-primary text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="{{ route('advanced') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('advanced') ? 'border-brand-primary text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                Advanced Analytics
                            </a>
                            <a href="{{ route('settings.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('settings.*') ? 'border-brand-primary text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium">
                                Settings
                            </a>
                            <button onclick="toggleDarkMode()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition">
                                <svg id="theme-toggle-dark-icon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                                <svg id="theme-toggle-light-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center">
                        @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-gray-700 hover:text-gray-900">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-gray-900">Login</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>
    <script>
        // Dark Mode Toggle
        function toggleDarkMode() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Toggle icons
            document.getElementById('theme-toggle-dark-icon').classList.toggle('hidden');
            document.getElementById('theme-toggle-light-icon').classList.toggle('hidden');
        }

        // Load saved theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            
            if (savedTheme === 'dark') {
                document.getElementById('theme-toggle-dark-icon').classList.remove('hidden');
                document.getElementById('theme-toggle-light-icon').classList.add('hidden');
            }
        });

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + D = Toggle Dark Mode
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                toggleDarkMode();
            }
            
            // Ctrl/Cmd + R = Refresh Data
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                if (typeof window.refreshData === 'function') {
                    window.refreshData();
                }
            }
        });
    </script>
</body>
</html>
