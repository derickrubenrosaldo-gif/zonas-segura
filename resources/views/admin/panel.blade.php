<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>{{ $sections[$section] }} | Zona Segura</title>
  <link rel="stylesheet" href="/admin.css?v=1">
</head>
<body>
  <a class="skip-panel" href="#main-panel">Saltar al contenido</a>
  <header class="panel-header">
    <strong>Zona Segura <small>Administración</small></strong>
    <div class="header-actions">
      <a href="/">Ver sitio</a>
      <form action="{{ route('panel.logout') }}" method="post">@csrf<button type="submit">Cerrar sesión</button></form>
    </div>
  </header>

  <div class="panel-frame">
    <nav aria-label="Secciones del panel" class="panel-nav">
      @foreach ($sections as $key => $label)
        <a href="{{ route('panel.section', $key) }}" @if($section === $key) aria-current="page" @endif>{{ $label }}</a>
      @endforeach
    </nav>
    <main class="panel-main" id="main-panel" tabindex="-1">
      <h1>{{ $sections[$section] }}</h1>
      @if (session('saved')) <p class="notice" role="status">{{ session('saved') }}</p> @endif
      @if ($errors->any())
        <div class="notice error" role="alert"><strong>Revisa los datos del formulario.</strong>
          <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif

      @if ($section !== 'portada')
        <div class="panel-list">
          <h2>Registros</h2>
          @forelse ($rows as $row)
            <a class="record-link" href="{{ route('panel.section', [$section, $row->id]) }}">
              <strong>{{ $row->name ?? $row->title ?? $row->neighborhood }}</strong>
              <span>
                @if ($section === 'reportes')
                  {{ ['in_review' => 'Sin confirmar', 'active' => 'En seguimiento', 'resolved' => 'Cerrado', 'pending' => 'Oculto'][$row->status] ?? $row->status }}
                @elseif ($section === 'estaciones')
                  {{ $row->station_code }} · {{ $row->is_active ? 'Visible' : 'Oculta' }}
                @else
                  {{ $row->area }} · {{ $row->published ? 'Publicación habilitada' : 'Borrador' }}
                @endif
              </span>
            </a>
          @empty
            <p>Todavía no hay registros.</p>
          @endforelse
          @if ($rows->hasPages())
            <nav class="page-links" aria-label="Páginas de registros">
              @if ($rows->previousPageUrl()) <a href="{{ $rows->previousPageUrl() }}">Anterior</a> @endif
              <span>{{ $rows->currentPage() }} de {{ $rows->lastPage() }}</span>
              @if ($rows->nextPageUrl()) <a href="{{ $rows->nextPageUrl() }}">Siguiente</a> @endif
            </nav>
          @endif
        </div>
      @endif

      <section class="panel-editor">
        <div class="section-heading">
          <h2>{{ $section === 'portada' ? 'Contenido público' : ($item ? 'Editar registro' : 'Nuevo registro') }}</h2>
          @if ($item)<a href="{{ route('panel.section', $section) }}#editor">Agregar otro</a>@endif
        </div>
        @php
          $fields = match ($section) {
            'reportes' => [
              'category' => ['Tipo de afectación', ['flood' => 'Inundación', 'fire' => 'Incendio', 'traffic' => 'Accidente vial', 'fallen_tree' => 'Árbol caído', 'other' => 'Otro riesgo']],
              'neighborhood' => ['Colonia', 'text'],
              'source_type' => ['Dependencia', ($record?->source_type === 'community' ? ['community' => 'Comunidad'] : []) + ['civil_protection' => 'Protección Civil', 'firefighters' => 'Bomberos', 'public_security' => 'Seguridad Pública']],
              'source_name' => ['Nombre de la fuente (opcional)', 'text'],
              'source_url' => ['Enlace de la fuente (opcional)', 'url'],
              'latitude' => ['Latitud', 'number'], 'longitude' => ['Longitud', 'number'],
              'occurred_at' => ['Fecha y hora del incidente', 'datetime-local'],
              'status' => ['Estado', ['in_review' => 'Sin confirmar', 'active' => 'En seguimiento', 'resolved' => 'Cerrado', 'pending' => 'Oculto']],
              'description' => ['Descripción', 'textarea'],
            ],
            'estaciones' => [
              'station_code' => ['Código de estación', 'text'],
              'name' => ['Nombre', 'text'], 'neighborhood' => ['Colonia', 'text'],
              'latitude' => ['Latitud', 'number'], 'longitude' => ['Longitud', 'number'],
              'sensor_type' => ['Sensor', ['rain' => 'Lluvia', 'water_level' => 'Nivel de agua', 'temperature' => 'Temperatura', 'air_quality' => 'Calidad del aire', 'other' => 'Otro']],
              'metric_name' => ['Medición (ej. Precipitación)', 'text'], 'metric_unit' => ['Unidad (ej. mm/h)', 'text'],
              'warning_threshold' => ['Umbral de advertencia (opcional)', 'number'],
              'critical_threshold' => ['Umbral crítico (opcional)', 'number'],
            ],
            'avisos' => [
              'title' => ['Título del boletín', 'text'], 'area' => ['Zona afectada', 'text'],
              'issued_at' => ['Emisión', 'datetime-local'], 'expires_at' => ['Vigente hasta', 'datetime-local'],
              'url' => ['Enlace original del boletín', 'url'],
            ],
            default => ['title' => ['Título de la página', 'text'], 'location' => ['Subtítulo de ubicación', 'text'], 'notice' => ['Aviso comunitario (opcional)', 'textarea']],
          };
          $optional = ['source_name', 'source_url', 'warning_threshold', 'critical_threshold', 'notice'];
        @endphp
        @if ($section === 'avisos')<p>Incluye únicamente boletines publicados por las autoridades. Se muestran entre su emisión y vencimiento.</p>@endif
        @if ($section === 'portada')<p>El aviso comunitario aparece debajo del buscador.</p>@endif
        <form id="editor" action="{{ route('panel.save', [$section, $item]) }}" method="post" class="editor-form">
          @csrf
          @foreach ($fields as $key => [$label, $type])
            @php
              $value = $record?->{$key} ?? '';
              if ($type === 'datetime-local' && $value) {
                  $value = \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d\TH:i');
              }
              $value = old($key, $value);
            @endphp
            <div class="field @if($type === 'textarea' || $type === 'url') field-wide @endif">
              <label for="field-{{ $key }}">{{ $label }}</label>
              @if (is_array($type))
                <select id="field-{{ $key }}" name="{{ $key }}" required>
                  @foreach ($type as $option => $caption)<option value="{{ $option }}" @selected($value === $option)>{{ $caption }}</option>@endforeach
                </select>
              @elseif ($type === 'textarea')
                <textarea id="field-{{ $key }}" name="{{ $key }}" rows="4" maxlength="{{ $key === 'notice' ? 240 : 800 }}" @required(!in_array($key,$optional))>{{ $value }}</textarea>
              @else
                <input id="field-{{ $key }}" name="{{ $key }}" type="{{ $type }}" value="{{ $value }}" @required(!in_array($key,$optional)) @if($type === 'number') step="any" @endif @readonly($key === 'station_code' && $item)>
              @endif
            </div>
          @endforeach
          @if ($section === 'estaciones' || $section === 'avisos')
            @php($flag = $section === 'estaciones' ? 'is_active' : 'published')
            <input name="{{ $flag }}" type="hidden" value="0">
            <label class="field-wide checkbox"><input name="{{ $flag }}" type="checkbox" value="1" @checked(old($flag, $record?->{$flag} ?? ($section === 'estaciones')))>
              {{ $section === 'estaciones' ? 'Mostrar estación en el mapa' : 'Publicar boletín durante su vigencia' }}
            </label>
          @endif
          <div class="field-wide"><button type="submit">Guardar cambios</button></div>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
