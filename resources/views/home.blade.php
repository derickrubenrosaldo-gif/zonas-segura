<!doctype html>
<html lang="es-MX">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#421228">
  <meta name="description" content="Zona Segura organiza reportes y lecturas de estaciones para prevención comunitaria, con fuentes identificadas y enlaces a alertas oficiales de Veracruz.">
  <title>Mapa de afectaciones | Zona Segura</title>
  <link rel="icon" type="image/png" sizes="48x48" href="/logos/zona-segura-icon.png?v=croquis-mexico">
  <script>
    (() => {
      const root = document.documentElement;
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
      const reveal = () => root.classList.remove('launching');
      root.classList.add('launching');
      window.setTimeout(reveal, 650);
      document.addEventListener('DOMContentLoaded', reveal, { once: true });
      document.addEventListener('keydown', event => {
        if (event.key === 'Tab' || event.key === 'Escape') reveal();
      });
    })();
  </script>
  <link rel="preconnect" href="https://unpkg.com"><link rel="preconnect" href="https://tile.openstreetmap.org">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
  <link rel="stylesheet" href="/styles.css?v=ejemplos-1">
</head>
<body>
  <div class="startup-screen">
    <div class="startup-brand" role="status">
      <div class="startup-emblem" aria-hidden="true">
        <img src="/logos/zona-segura.png?v=croquis-mexico" alt="" width="84" height="69" fetchpriority="high">
      </div>
      <strong>Zona Segura</strong>
      <span>Cargando la página…</span>
    </div>
  </div>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>
  <header class="topbar">
    <a class="brand" href="/" aria-label="Zona Segura, inicio"><span class="brand-mark" aria-hidden="true"><img src="/logos/zona-segura.png?v=croquis-mexico" alt="" width="60" height="50"></span><span class="brand-lockup"><strong>Zona Segura</strong><small>Prevención comunitaria</small></span></a>
    <nav class="portal-nav" aria-label="Navegación principal">
      <a href="#mapa" aria-current="location">Mapa</a>
      <a href="#afectaciones">Reportes</a>
      <a href="#estaciones">Estaciones</a>
      <a href="#alertas">Alertas</a>
    </nav>
    <a class="header-emergency" href="tel:911"><span>Emergencias</span> <strong>911</strong></a>
  </header>

  <div class="institution-strip">
    <p>Proyecto comunitario independiente</p>
    <nav class="institution-logos header-institutions" aria-label="Enlaces institucionales de referencia">
      <a class="institution-link institution-mexico" href="https://www.gob.mx/" target="_blank" rel="noopener">
        <span class="institution-mark"><img src="/logos/gobierno-mexico.png" alt="Gobierno de México" decoding="async"></span>
      </a>
      <a class="institution-link institution-itesco" href="https://itesco.edu.mx/" target="_blank" rel="noopener">
        <span class="institution-mark"><img src="/logos/itesco.png" alt="ITESCO" decoding="async"></span>
      </a>
    </nav>
  </div>

  <main id="contenido" tabindex="-1">
    <header class="intro" aria-labelledby="page-title">
      <div class="intro-weather" aria-hidden="true">
        <div class="weather-rain"></div>
        <div class="rain-surface"></div>
        <span class="weather-sun intro-sun"></span>
        <span class="weather-lightning"></span>
      </div>
      <div class="intro-main">
        <div class="intro-heading">
          <div class="intro-context">
            <p class="eyebrow" id="portal-location">Coatzacoalcos, Veracruz</p>
            <span id="demo-mode-label" class="demo-mode-label" hidden>Datos de prueba</span>
          </div>
          <h1 id="page-title">Mapa de afectaciones</h1>
        </div>
        <div class="intro-tools">
          <div class="portal-search">
            <form id="location-search" class="location-search" role="search">
              <label for="location-query">Busca tu colonia</label>
              <div class="search-controls">
                <input id="location-query" name="q" type="search" placeholder="Ej. Centro o Las Gaviotas" autocomplete="off" required aria-describedby="location-feedback">
                <button type="submit">Buscar</button>
              </div>
            </form>
            <button id="locate-me" class="locate-button" type="button">Usar mi ubicación</button>
            <div class="portal-search-result">
              <p id="location-feedback" class="location-feedback" role="status"></p>
              <a href="#mapa" id="location-result-link" hidden>Ver en el mapa</a>
            </div>
          </div>
          <div class="intro-actions">
            <button class="text-button" type="button" data-open-report>Dónde reportar</button>
            <a href="#telefonos">Teléfonos de ayuda</a>
            <button id="weather-motion" class="text-button" type="button" hidden>Pausar animación del clima</button>
          </div>
        </div>
      </div>
    </header>

    <p id="portal-notice" class="portal-notice" hidden></p>

    <div class="consultation-grid">
      <section data-scroll-reveal tabindex="-1" id="mapa" class="map-section" aria-label="Mapa de afectaciones y estaciones de monitoreo">
        <div id="demo-note" class="demo-note" role="note" hidden><b>Datos de prueba</b><span>Reportes, zonas y lecturas de ejemplo.</span></div>
        <p id="map-unavailable" class="demo-note" role="status" hidden></p>
        <div class="map-options">
          <div class="layer-switch" role="group" aria-label="Qué mostrar en el mapa">
            <button type="button" data-layer="all" class="selected" aria-pressed="true">Todo</button>
            <button type="button" data-layer="reports" aria-pressed="false">Reportes</button>
            <button type="button" data-layer="stations" aria-pressed="false">Estaciones</button>
          </div>
          <a id="demo-view-link" href="/?demo=1#mapa">Ver datos de prueba</a>
          <details class="filter-panel">
            <summary>Filtros</summary>
            <div class="map-filters"><label>Tipo de reporte<select id="category-filter"><option value="">Todos</option><option value="flood">Inundación</option><option value="fire">Incendio</option><option value="traffic">Accidente vial</option><option value="fallen_tree">Árbol caído</option><option value="other">Otro riesgo</option></select></label><label>Estado<select id="status-filter"><option value="">Todos</option><option value="active">En seguimiento</option><option value="in_review">Sin confirmar</option><option value="resolved">Cerrado</option></select></label><label>Fecha<select id="date-filter"><option value="">Cualquier fecha</option><option value="24h">Últimas 24 horas</option><option value="7d">Últimos 7 días</option><option value="30d">Últimos 30 días</option></select></label><button id="reset-filters" type="button">Limpiar filtros</button></div>
          </details>
        </div>
        <div class="map-canvas">
          <div class="map-shell"><div id="map" role="application" aria-label="Mapa interactivo de reportes y estaciones de monitoreo"></div><div id="map-empty" class="map-empty" hidden><strong id="map-empty-title">Preparando el mapa</strong><span id="map-empty-copy"></span></div></div>
          <div class="map-foot"><span id="results-summary" role="status">Consultando información</span><aside class="map-legend" aria-label="Leyenda"><span><i class="legend-pin report-pin"></i>Reporte</span><span><i class="legend-ring station-ring"></i>Estación de monitoreo</span><span>Ubicaciones aproximadas</span><span id="area-legend" hidden><i class="legend-area"></i><span>Perímetro informado</span></span></aside><div class="map-sync"><span id="map-update-label" role="status"></span><button type="button" id="refresh-map">Actualizar datos</button></div></div>
        </div>
        <nav class="map-list-access" aria-label="Consulta sin usar el mapa">
          <a href="#afectaciones">Lista de reportes</a>
          <a href="#estaciones">Lista de estaciones</a>
        </nav>
      </section>
      <aside class="local-rail" aria-label="Información local">
        <section data-scroll-reveal id="alertas" tabindex="-1" class="alert-guide" aria-labelledby="alert-guide-title">
          <h2 id="alert-guide-title">Alertas de Protección Civil</h2>
          <div id="local-bulletins" class="local-bulletins"><p>Consulta el boletín oficial para conocer las condiciones de tu zona.</p></div>
          <a class="official-link" href="https://www.veracruz.gob.mx/proteccioncivil/servicio/boletin-meteorologico-2/" target="_blank" rel="noopener">Abrir boletín oficial <span aria-hidden="true">↗</span></a>
          <details class="phase-details"><summary>Fases de alerta en Veracruz (SIAT‑VER)</summary><ol class="alert-levels alert-phases" aria-label="Fases oficiales del SIAT-VER">
            <li class="alert-level phase-follow"><span class="level-light" aria-hidden="true">1</span><div><strong>Seguimiento</strong><small>4–5 días antes</small></div></li>
            <li class="alert-level phase-watch"><span class="level-light" aria-hidden="true">2</span><div><strong>Vigilancia</strong><small>3 días antes</small></div></li>
            <li class="alert-level phase-organize"><span class="level-light" aria-hidden="true">3</span><div><strong>Organización</strong><small>48 horas antes</small></div></li>
            <li class="alert-level phase-action"><span class="level-light" aria-hidden="true">4</span><div><strong>Acción</strong><small>24 horas antes</small></div></li>
          </ol></details>
        </section>
        <div class="weather-brief" id="local-conditions">
          <div class="weather-scene" aria-hidden="true">
            <span class="weather-sun"></span>
            <span class="weather-cloud"></span>
            <div class="weather-rain"></div>
          </div>
          <p class="weather-caption">Clima local</p>
          <p class="weather-reading" id="weather-reading" role="status">Consultando condiciones…</p>
          <div class="weather-meta">
            <a href="https://open-meteo.com/" rel="noopener" target="_blank">Open-Meteo</a>
          </div>
        </div>
      </aside>
    </div>

    <section data-scroll-reveal class="data-sections" aria-label="Información del mapa">
      <article class="data-panel" id="afectaciones" tabindex="-1"><div class="panel-heading"><div><h2>Reportes por zona</h2></div></div><div id="incident-list" class="data-list"><p class="list-placeholder">Buscando reportes publicados…</p></div></article>
      <article class="data-panel" id="estaciones" tabindex="-1"><div class="panel-heading"><div><h2>Estaciones</h2></div></div><div id="station-list" class="data-list"><p class="list-placeholder">Buscando estaciones conectadas…</p></div><p class="sensor-footnote">Los umbrales del sensor no son alertas oficiales.</p></article>
    </section>

    <section data-scroll-reveal id="telefonos" tabindex="-1" class="phone-directory" aria-labelledby="phone-title">
      <div class="phone-heading">
        <h2 id="phone-title">Teléfonos de ayuda</h2>
        <p><a href="tel:911">Emergencias <strong>911</strong></a></p>
      </div>
      <div class="phone-carousel" aria-label="Contactos de Coatzacoalcos y líneas nacionales">
        <ul class="phone-track" id="phone-track" tabindex="0" aria-label="Teléfonos de ayuda. Desliza o usa las flechas del teclado para consultar más contactos.">
          <li class="phone-item">
            <a class="phone-logo" href="https://procuraduria.hidalgo.gob.mx/assets/imgs/Noticias/Diferencias%200-89%20y%209-11.jpg" target="_blank" rel="noopener"><img src="/logos/contactos/911.webp" alt="Distintivo oficial 911" loading="lazy" decoding="async"></a>
            <div class="phone-description"><h3>Emergencias</h3><p>Atención inmediata para emergencias médicas, incendios y seguridad pública.</p></div>
            <a class="phone-number" href="tel:911" aria-label="Llamar al 911, emergencias"><span class="phone-action-text" aria-hidden="true">Llamar</span><strong>911</strong></a>
          </li>
          <li class="phone-item">
            <a class="phone-logo" href="https://www.seguridadbc.gob.mx/images/top/089M.png" target="_blank" rel="noopener"><img src="/logos/contactos/089.webp" alt="Distintivo oficial 089" loading="lazy" decoding="async"></a>
            <div class="phone-description"><h3>Denuncia anónima</h3><p>Denuncia delitos de forma anónima. Si hay peligro inmediato, llama al 911.</p></div>
            <a class="phone-number" href="tel:089" aria-label="Llamar al 089, denuncia anónima"><span class="phone-action-text" aria-hidden="true">Llamar</span><strong>089</strong></a>
          </li>
          <li class="phone-item">
            <a class="phone-logo" href="https://tramites.coatzacoalcos.gob.mx/tramites/23/" target="_blank" rel="noopener"><img src="/logos/contactos/coatzacoalcos.webp" alt="Ayuntamiento de Coatzacoalcos" loading="lazy" decoding="async"></a>
            <div class="phone-description"><h3>Protección Civil y Bomberos</h3><p>Consultas y trámites · Lun–vie, 8:30–15:00.</p></div>
            <a class="phone-number" href="tel:+529212131806" aria-label="Llamar a Protección Civil y Bomberos, 921 213 1806"><span class="phone-action-text" aria-hidden="true">Llamar</span><strong>921 213 1806</strong></a>
          </li>
          <li class="phone-item">
            <a class="phone-logo" href="https://siac.coatzacoalcos.gob.mx/" target="_blank" rel="noopener"><img src="/logos/contactos/coatzacoalcos.webp" alt="Ayuntamiento de Coatzacoalcos" loading="lazy" decoding="async"></a>
            <div class="phone-description"><h3>Atención ciudadana municipal</h3><p>Servicios públicos y reportes · Lun–vie, 8:00–19:00.</p></div>
            <a class="phone-number" href="tel:+529212110800" aria-label="Llamar a Atención Ciudadana, 921 211 0800"><span class="phone-action-text" aria-hidden="true">Llamar</span><strong>921 211 0800</strong></a>
          </li>
        </ul>
      </div>
    </section>

    <nav data-scroll-reveal class="prevention-links" aria-labelledby="prevention-title">
      <h2 id="prevention-title">Guías de prevención y mapas de riesgo</h2>
      <ul>
        <li><a href="https://www.veracruz.gob.mx/proteccioncivil/analisis-de-riesgos/" target="_blank" rel="noopener">Atlas de Riesgos de Veracruz</a></li>
        <li><a href="https://www.cenapred.gob.mx/es/Publicaciones/archivos/259-INFOGRAFAENCASODEINUNDACIN.PDF" target="_blank" rel="noopener">Qué hacer ante una inundación (PDF)</a></li>
        <li><a href="https://preparados.gob.mx/SIAT-CT/consulta.php" target="_blank" rel="noopener">Avisos de ciclones</a></li>
      </ul>
    </nav>
  </main>

  <nav class="mobile-nav" aria-label="Secciones de Zona Segura">
    <a href="#mapa" aria-current="location">Mapa</a>
    <a href="#afectaciones">Reportes</a>
    <a href="#estaciones">Estaciones</a>
    <a href="#alertas">Alertas</a>
  </nav>

  <footer class="footer" id="informacion">
    <div class="footer-grid">
      <section class="footer-identity" aria-label="Zona Segura">
        <a class="brand brand-footer" href="/"><span class="brand-mark" aria-hidden="true"><img src="/logos/zona-segura.png?v=croquis-mexico" alt="" width="60" height="50"></span><span class="brand-lockup"><strong>Zona Segura</strong><small>Una iniciativa Betanzo</small></span></a>
        <p>Proyecto comunitario independiente de Coatzacoalcos.</p>
        <a class="footer-emergency" href="#telefonos">Directorio telefónico</a>
      </section>
      <nav class="footer-links" id="marco-legal" aria-labelledby="footer-legal">
        <h2 id="footer-legal">Marco legal</h2>
        <a href="https://www.veracruz.gob.mx/infraestructura/wp-content/uploads/sites/17/2026/06/LEY-DE-PROTECCION-CIVIL-Y-LA-REDUCCION-DEL-RIESGO-DE-DESASTRES-PARA-EL-ESTADO-DE-VERACRUZ-DE-IGNACIO-DE-LA-LLAVE.pdf" target="_blank" rel="noopener">Ley 856 de Veracruz</a><a href="https://www.diputados.gob.mx/LeyesBiblio/pdf/LGPC.pdf" target="_blank" rel="noopener">Ley General de Protección Civil</a><a href="https://www.veracruz.gob.mx/proteccioncivil/analisis-de-riesgos/" target="_blank" rel="noopener">Atlas y análisis de riesgos de Veracruz</a>
        <a href="https://www.diputados.gob.mx/LeyesBiblio/pdf/LFPDPPP.pdf" target="_blank" rel="noopener">Ley de protección de datos personales</a>
      </nav>
      <nav class="footer-links" aria-labelledby="footer-official">
        <h2 id="footer-official">Plataformas oficiales</h2>
        <a href="https://www.veracruz.gob.mx/proteccioncivil/" target="_blank" rel="noopener">Protección Civil Veracruz</a>
        <a href="https://www.veracruz.gob.mx/proteccioncivil/programas-de-proteccion-civil/" target="_blank" rel="noopener">Programas de prevención de Veracruz</a>
        <a href="https://www.gob.mx/cenapred" target="_blank" rel="noopener">CENAPRED</a>
        <a href="https://www.atlasnacionalderiesgos.gob.mx/" target="_blank" rel="noopener">Atlas Nacional de Riesgos</a>
        <a href="https://smn.conagua.gob.mx/" target="_blank" rel="noopener">Servicio Meteorológico Nacional</a>
        <a href="https://preparados.gob.mx/SIAT-CT/consulta.php" target="_blank" rel="noopener">Boletín de ciclones SIAT‑CT</a>
        <a href="https://www.gob.mx/911" target="_blank" rel="noopener">Información del 911</a>
      </nav>
      <section class="footer-links footer-site" aria-labelledby="footer-site">
        <h2 id="footer-site">Sobre el sitio</h2>
        <details id="privacidad">
          <summary>Privacidad y ubicación</summary>
          <p>La búsqueda envía el texto ingresado a OpenStreetMap. Al elegir «Usar mi ubicación», tu navegador pide permiso y usa las coordenadas para centrar el mapa. Los servicios de mapas, clima y alojamiento pueden recibir datos técnicos, como la dirección IP. El clima se consulta a Open-Meteo con coordenadas fijas de Coatzacoalcos.</p>
          <p>La recepción de reportes ciudadanos está desactivada. Consulta la <a href="https://osmfoundation.org/wiki/Privacy_Policy" target="_blank" rel="noopener">política de privacidad de OpenStreetMap</a>.</p>
        </details>
        <details id="condiciones">
          <summary>Condiciones de uso</summary>
          <p>Los datos marcados como prueba se muestran como ejemplos de uso. Para decisiones de emergencia, consulta los avisos de Protección Civil y llama al 911 si hay peligro inmediato.</p>
        </details>
        <button type="button" class="footer-report" data-open-report>Dónde reportar</button>
      </section>
    </div>
    <div class="footer-bottom">
      <nav class="institution-logos" aria-label="Enlaces institucionales de referencia">
        <a class="institution-link institution-mexico" href="https://www.gob.mx/" target="_blank" rel="noopener">
          <span class="institution-mark"><img src="/logos/gobierno-mexico.png" alt="Gobierno de México" loading="lazy" decoding="async"></span>
        </a>
        <a class="institution-link institution-itesco" href="https://itesco.edu.mx/" target="_blank" rel="noopener">
          <span class="institution-mark"><img src="/logos/itesco.png" alt="ITESCO" loading="lazy" decoding="async"></span>
        </a>
      </nav>
      <span>© 2026 Zona Segura</span>
    </div>
  </footer>

  <dialog id="report-dialog" class="report-dialog" aria-labelledby="report-title"><div class="report-form"><div class="dialog-heading"><div><h2 id="report-title">Dónde reportar</h2></div><button class="dialog-close" type="button" aria-label="Cerrar">Cerrar</button></div><p class="dialog-intro">Zona Segura todavía no recibe reportes.</p>
    <div class="report-channels">
      <div><strong>Emergencia o peligro inmediato</strong><a href="tel:911">Llamar al 911</a></div>
      <div><strong>Problemas con servicios públicos municipales</strong><a href="https://siac.coatzacoalcos.gob.mx/registrapeticion.php?tipo=5" target="_blank" rel="noopener">Enviar un reporte al Ayuntamiento</a></div>
      <div><strong>Información preventiva</strong><a href="https://www.veracruz.gob.mx/proteccioncivil/" target="_blank" rel="noopener">Consultar Protección Civil Veracruz</a></div>
    </div><div class="dialog-actions"><a href="#telefonos" onclick="this.closest('dialog').close()">Ver directorio telefónico</a> <button class="button button-outline dialog-cancel" type="button">Cerrar</button></div></div></dialog>
  <script src="/ambiance.js?v=clima-escena-3" defer></script>
  <script src="/contactos.js?v=5" defer></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
  <script src="/app.js?v=ejemplos-2" defer></script>
</body>
</html>
