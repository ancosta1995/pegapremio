<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pega Prêmio - Natal</title>
    
    <!-- Notiflix -->
    <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
    
    <!-- Chart.js (se necessário) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Nosifer&family=Bungee&family=Righteous&display=swap" rel="stylesheet">
    
    <!-- Font Personalizada - Escolha uma das opções abaixo -->
    <style>
        /* Opção 1: Creepster - Horror mas legível */
        /* body { font-family: 'Creepster', 'Onest', sans-serif; } */
        
        /* Opção 2: Nosifer - Assustador mas legível */
        /* body { font-family: 'Nosifer', 'Onest', sans-serif; } */
        
        /* Opção 3: Bungee - Bold e impactante */
        /* body { font-family: 'Bungee', 'Onest', sans-serif; } */
        
        /* Opção 4: Righteous - Retro mas legível */
        /* body { font-family: 'Righteous', 'Onest', sans-serif; } */
        
        /* Por enquanto, mantendo Onest que é mais legível */
        body { font-family: 'Onest', sans-serif; }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Script para capturar kwai_click_id -->
    <script src="{{ asset('js/kwai.js') }}"></script>
    
    <script>
        // Passa os caminhos dos assets para o Vue
        window.ASSETS_BASE_URL = '{{ asset('') }}';
    </script>
</head>
<body>
    <div id="app"></div>
</body>
</html>


