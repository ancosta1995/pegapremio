<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jogue Grátis - Pega Prêmio</title>
    
    <!-- Notiflix -->
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    
    <!-- Chart.js (se necessário) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Nosifer&family=Bungee&family=Righteous&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Onest', sans-serif; }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script>
        // Passa os caminhos dos assets para o Vue
        window.ASSETS_BASE_URL = '{{ asset('') }}';
        // Indica que está em modo presell
        window.PRESELL_MODE = true;
    </script>
</head>
<body>
    <div id="app"></div>
</body>
</html>

