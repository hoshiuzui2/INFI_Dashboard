<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
     <!-- ✅ CSRF Token (GLOBAL, REQUIRED for AJAX) -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Executive Dashboard')</title>

   <!-- ✅ Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- ✅ Bootstrap Icons (REQUIRED for KPI icons) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<!-- ✅ Font Awesome (PROFESSIONAL ICONS) -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
/>

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fafc 0%, #e8f0ff 100%);
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
        }

        .container { flex: 1; }

        footer {
            margin-top: auto;
            text-align: center;
            color: #6c757d;
            padding: 20px 0;
        }

        .dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .logo-area {
            display: flex;
            align-items: center;
        }

        .logo-area img {
            height: 70px;
            width: auto;
            margin-right: 15px;
            border-radius: 6px;
        }

        .title-group h2 {
            font-weight: 700;
            margin: 0;
        }

        .title-group small {
            color: #6c757d;
        }

        .badge {
            transition: all 0.2s ease-in-out;
        }

        .badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .metric { font-size: 2rem; font-weight: 700; }
        .text-green { color: #198754; }
        .text-red { color: #dc3545; }
        .text-blue { color: #0d6efd; }

        .badge.bg-transparent {
            backdrop-filter: blur(2px);
        }

        .card canvas {
            width: 100% !important;
            height: 120px !important;
        }
    </style>
    

    {{-- ✅ Page-specific styles (if needed) --}}
    @yield('styles')
</head>

<body class="p-4">
    <div class="container blur-dim">

        {{-- 🧭 Navbar (always visible) --}}
        @include('partials.navbar')

        {{-- 🎯 Main content area --}}
        @yield('content')

    </div>

    <footer>
        &copy; {{ date('Y') }} Infinus Corporation | Executive Dashboard
    </footer>
    

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <!-- ✅ Load JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 <!-- PATCH: Disable Bootstrap modal focus enforcement (prevents flicker) -->
<script>
(function () {
  function patchBootstrapModal() {
    try {
      if (!window.bootstrap || !bootstrap.Modal) return;
      if (bootstrap.Modal.prototype.__flickerPatched) return;

      // Stop the problematic automatic refocusing
      bootstrap.Modal.prototype._enforceFocus = function () {};

      bootstrap.Modal.prototype.__flickerPatched = true;
      console.debug("Modal flicker patch applied.");
    } catch (e) {
      console.warn("Modal patch failed", e);
    }
  }

  if (document.readyState === "complete" || document.readyState === "interactive") {
    patchBootstrapModal();
  } else {
    document.addEventListener("DOMContentLoaded", patchBootstrapModal, { once: true });
  }
})();
</script>

    <!-- ✅ Load Chart.js globally -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- ✅ Chart.js DataLabels Plugin (for % labels on pie charts) -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

    <!-- ✅ Chart.js Annotation Plugin (for yearly total line) -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.4.0"></script>

    <!-- ✅ Page-specific scripts (injected by child views like sales.blade.php) -->
    
    @stack('scripts')
</body>
</html>
