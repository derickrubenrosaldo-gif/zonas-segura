<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Administración | Zona Segura</title>
  <link rel="stylesheet" href="/admin.css?v=1">
</head>
<body>
  <header class="panel-header"><strong>Zona Segura</strong><a href="/">Volver al sitio</a></header>
  <main class="login">
    <h1>Administración</h1>
    <p>Gestiona reportes, estaciones, avisos y la portada.</p>
    @if ($configured)
      <form method="post" action="{{ route('panel.login') }}">
        @csrf
        @if (session('login_error'))
          <p class="notice error" role="alert">{{ session('login_error') }}</p>
        @endif
        <label for="username">Usuario</label>
        <input id="username" name="username" type="text" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" maxlength="80" required>
        <label for="password">Contraseña</label>
        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" required>
        <button type="submit">Iniciar sesión</button>
      </form>
    @else
      <div class="notice"><strong>Acceso pendiente de configuración</strong><p>La administración se habilita al configurar la cuenta y conectar la base de datos.</p></div>
    @endif
  </main>
</body>
</html>
