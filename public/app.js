(() => {
  const categories = { flood: 'Inundación', fire: 'Incendio', traffic: 'Accidente vial', fallen_tree: 'Árbol caído', other: 'Otro riesgo' };
  const states = { active: 'En seguimiento', in_review: 'Sin confirmar', resolved: 'Cerrado', normal: 'Normal', attention: 'Lectura elevada', alert: 'Límite del sensor superado', offline: 'Sin señal' };
  const sources = { community: 'Comunidad', firefighters: 'Bomberos', public_security: 'Seguridad Pública', civil_protection: 'Protección Civil' };
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const leaflet = window.L;
  const incidentLayer = leaflet?.layerGroup();
  const stationLayer = leaflet?.layerGroup();
  const map = leaflet?.map('map', {
    scrollWheelZoom: false,
    zoomControl: false,
    zoomAnimation: !reducedMotion.matches,
    fadeAnimation: !reducedMotion.matches
  }).setView([18.147, -94.42], 11);
  let needsInitialView = true;
  if (map) {
    map.once('movestart', () => { needsInitialView = false; });
    leaflet.control.zoom({ position: 'topright', zoomInTitle: 'Acercar mapa', zoomOutTitle: 'Alejar mapa' }).addTo(map);
    leaflet.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(map);
  }
  const markers = new Map();
  const list = document.getElementById('incident-list');
  const stationsList = document.getElementById('station-list');
  const summary = document.getElementById('results-summary');
  const emptyTitle = document.getElementById('map-empty-title');
  const emptyCopy = document.getElementById('map-empty-copy');
  const dialog = document.getElementById('report-dialog');
  let incidents = [];
  let stations = [];
  const mapParams = new URLSearchParams(window.location.search);
  const realOnly = mapParams.get('vista') === 'real';
  const openExamples = !realOnly && mapParams.get('demo') === '1';
  let demoView = false;
  let reportsUnavailable = false;
  let stationsUnavailable = false;
  let activeLayer = 'all';

  const demoStart = Date.now();
  const exampleTime = minutes => new Date(demoStart - minutes * 60000).toISOString();
  const demoIncidents = [
    { id: 'demo-r1', category: 'flood', neighborhood: 'Centro · Coatzacoalcos', description: 'Reporte de prueba por encharcamiento en calles del Centro.', latitude: 18.148, longitude: -94.416, status: 'active', source_type: 'civil_protection', affected_area: { type: 'Polygon', coordinates: [[
      [-94.421, 18.150], [-94.414, 18.151], [-94.412, 18.145], [-94.419, 18.144], [-94.421, 18.150]
    ]] }, occurred_at: exampleTime(60), updated_at: exampleTime(38) },
    { id: 'demo-r2', category: 'traffic', neighborhood: 'Malecón · Coatzacoalcos', description: 'Aviso vial de prueba por un accidente en la zona del Malecón.', latitude: 18.145, longitude: -94.424, status: 'in_review', source_type: 'public_security', occurred_at: exampleTime(180), updated_at: exampleTime(165) },
    { id: 'demo-r3', category: 'fallen_tree', neighborhood: 'Las Gaviotas · Coatzacoalcos', description: 'Reporte de prueba por una rama caída en Las Gaviotas.', latitude: 18.155, longitude: -94.407, status: 'resolved', source_type: 'firefighters', occurred_at: exampleTime(2880), updated_at: exampleTime(2778) },
    { id: 'demo-r4', category: 'fire', neighborhood: 'Villa Allende · Coatzacoalcos', description: 'Reporte de prueba por presencia de humo en Villa Allende.', latitude: 18.155, longitude: -94.384, status: 'in_review', source_type: 'firefighters', occurred_at: exampleTime(7200), updated_at: exampleTime(7187) }
  ];
  const demoStations = [
    { id: 'demo-i1', station_code: 'DEMO-01', name: 'Pluviómetro · Centro', neighborhood: 'Centro · Coatzacoalcos', latitude: 18.151, longitude: -94.413, sensor_type: 'Lluvia', metric_name: 'Precipitación', metric_unit: 'mm/h', reading_value: 18.4, reading_status: 'attention', read_at: exampleTime(5) },
    { id: 'demo-i2', station_code: 'DEMO-02', name: 'Nivel de canal · Gaviotas', neighborhood: 'Las Gaviotas · Coatzacoalcos', latitude: 18.157, longitude: -94.404, sensor_type: 'Nivel de agua', metric_name: 'Nivel', metric_unit: 'cm', reading_value: 34, reading_status: 'normal', read_at: exampleTime(6) },
    { id: 'demo-i3', station_code: 'DEMO-03', name: 'Pluviómetro · Allende', neighborhood: 'Villa Allende · Coatzacoalcos', latitude: 18.153, longitude: -94.381, sensor_type: 'Lluvia', metric_name: 'Precipitación', metric_unit: 'mm/h', reading_value: null, reading_status: 'offline', read_at: null }
  ];

  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[c]);
  const localTime = new Intl.DateTimeFormat('es-MX', {
    timeZone: 'America/Mexico_City', dateStyle: 'medium', timeStyle: 'short'
  });
  function dateLabel(value) {
    if (!value) return 'Sin registro';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? 'Sin registro' : localTime.format(date);
  }
  const sourceLabel = report => report.source_name || sources[report.source_type] || 'Origen sin identificar';
  function getIncidents() {
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    const period = document.getElementById('date-filter').value;
    const cut = period ? Date.now() - ({'24h':86400000, '7d':604800000, '30d':2592000000}[period]) : 0;
    return incidents.filter(r => (!category || r.category === category) && (!status || r.status === status) && (!cut || new Date(r.occurred_at).getTime() >= cut));
  }
  function incidentIcon(report) {
    const type = report.category === 'flood' ? 'agua' : report.category === 'fire' ? 'fuego' : report.category === 'traffic' ? 'vial' : report.category === 'fallen_tree' ? 'arbol' : 'otro';
    return leaflet.divIcon({ className: '', html: `<span class="map-report ${type}${demoView ? ' map-marker-demo' : ''}" aria-hidden="true"><i></i></span>`, iconSize:[30,30], iconAnchor:[15,15] });
  }
  function stationIcon(station) {
    const offline = station.reading_status === 'offline' || !station.read_at;
    return leaflet.divIcon({ className:'', html:`<span class="map-station ${offline ? 'offline' : esc(station.reading_status || 'normal')}${demoView ? ' map-marker-demo' : ''}"><i></i></span>`, iconSize:[30,30], iconAnchor:[15,15] });
  }
  function renderMap(visible) {
    if (map.hasLayer(incidentLayer)) map.removeLayer(incidentLayer);
    if (map.hasLayer(stationLayer)) map.removeLayer(stationLayer);
    markers.clear(); incidentLayer.clearLayers(); stationLayer.clearLayers();
    let hasAreas = false;
    visible.forEach(report => {
      const area = report.affected_area;
      const hasArea = report.status === 'active'
        && area?.type === 'Polygon' && Array.isArray(area.coordinates);
      if (hasArea) {
        leaflet.geoJSON(area, {
          style: { color: '#842448', weight: 2, fillOpacity: .16, dashArray: demoView ? '6 5' : null },
          interactive: false
        }).addTo(incidentLayer);
        hasAreas = true;
      }
      const source = !demoView && hasArea && /^https:\/\//i.test(report.source_url || '')
        ? `<a href="${esc(report.source_url)}" target="_blank" rel="noopener">Fuente del perímetro</a>` : '';
      const category = categories[report.category] || 'Afectación';
      const isExample = demoView;
      const title = isExample ? `Ejemplo de prueba: ${category}` : category;
      const origin = isExample ? 'Ejemplo de prueba · ' + sourceLabel(report) : `Reporte · ${sourceLabel(report)}`;
      const marker = leaflet.marker([report.latitude, report.longitude], { icon: incidentIcon(report), title })
        .bindPopup(`<div class="map-popup"><span class="popup-kicker">${esc(origin)}</span><strong>${esc(category)}</strong><span>${esc(report.neighborhood)}</span><p>${esc(report.description)}</p><small>${esc(states[report.status] || report.status)} · observado ${esc(dateLabel(report.occurred_at))}</small><small>Actualizado ${esc(dateLabel(report.updated_at || report.occurred_at))}</small>${source}</div>`)
        .addTo(incidentLayer);
      markers.set(`report:${report.id}`, marker);
    });
    stations.forEach(station => {
      const state = station.reading_status === 'offline' || !station.read_at ? 'offline' : (station.reading_status || 'normal');
      const value = state === 'offline' || station.reading_value === null || station.reading_value === undefined ? 'Sin lectura actual' : `${Number(station.reading_value).toLocaleString('es-MX')} ${station.metric_unit || ''}`;
      const title = demoView ? `Ejemplo de prueba: ${station.name}` : station.name;
      const marker = leaflet.marker([station.latitude, station.longitude], { icon: stationIcon(station), title }).bindPopup(`<div class="map-popup station-popup"><span class="popup-kicker">${demoView ? 'Ejemplo de prueba' : 'Estación de monitoreo'} · ${esc(station.station_code)}</span><strong>${esc(station.name)}</strong><span>${esc(station.neighborhood)}</span><p>${esc(station.metric_name || station.sensor_type)}: <b>${esc(value)}</b></p><small>${esc(states[state] || state)} · última lectura: ${esc(dateLabel(station.read_at))}</small><small>${demoView ? 'Estación de prueba' : 'Estación registrada'}</small>${demoView ? '' : `<div class="station-history"><button type="button" data-history="${esc(station.station_code)}">Ver historial</button><div role="status"></div></div>`}</div>`).addTo(stationLayer);
      markers.set(`station:${station.id}`, marker);
    });
    const areaLegend = document.getElementById('area-legend');
    areaLegend.hidden = !hasAreas || activeLayer === 'stations';
    areaLegend.querySelector('span').textContent = demoView ? 'Zona de prueba' : 'Perímetro informado';
    if (activeLayer !== 'stations') incidentLayer.addTo(map);
    if (activeLayer !== 'reports') stationLayer.addTo(map);
  }
  function render() {
    const visible = getIncidents();
    const reportCount = activeLayer === 'stations' ? 0 : visible.length;
    const stationCount = activeLayer === 'reports' ? 0 : stations.length;
    if (map) renderMap(visible);
    document.querySelectorAll('[data-layer]').forEach(btn => { const selected = btn.dataset.layer === activeLayer; btn.classList.toggle('selected', selected); btn.setAttribute('aria-pressed', String(selected)); });
    const reportLabel = reportCount === 1 ? '1 reporte' : `${reportCount} reportes`;
    const stationLabel = stationCount === 1 ? '1 estación' : `${stationCount} estaciones`;
    const label = activeLayer === 'reports' ? reportLabel
      : activeLayer === 'stations' ? stationLabel : `${reportLabel} · ${stationLabel}`;
    if (summary.textContent !== label) summary.textContent = label;
    renderIncidents(visible);
    renderStations();
    document.getElementById('map-empty').hidden = Boolean(map && reportCount + stationCount);
    const incomplete = reportsUnavailable || stationsUnavailable;
    emptyTitle.textContent = !map ? 'Mapa no disponible' : incomplete ? 'Consulta incompleta' : 'Sin puntos en esta vista';
    emptyCopy.textContent = !map
      ? 'Consulta las listas de reportes y estaciones debajo del mapa.'
      : incomplete ? 'Intenta actualizar los datos.' : 'Puedes cambiar la capa o los filtros.';
  }
  function renderIncidents(rows) {
    if (reportsUnavailable) { list.textContent = 'No se pudieron consultar los reportes. Intenta actualizar el mapa.'; return; }
    if (!rows.length) { list.innerHTML = '<div class="empty-row"><strong>Sin reportes para estos filtros.</strong><span>La ausencia de un reporte no indica que la zona esté libre de peligro.</span></div>'; return; }
    list.innerHTML = rows.map(report => {
      const example = demoView;
      const category = categories[report.category] || 'Afectación';
      return `<details class="report-entry">
        <summary class="data-row">
          <span class="row-main">
            <strong>${esc(category)}${example ? '<span class="demo-tag">Ejemplo de prueba</span>' : ''}</strong>
            <small>${esc(report.neighborhood)} · ${esc(dateLabel(report.occurred_at))}</small>
          </span>
          <span class="row-state ${esc(report.status)}">${esc(states[report.status] || report.status)}</span>
        </summary>
        <div class="report-entry-content">
          <p>${esc(report.description)}</p>
          ${example ? '' : `<p>Fuente: ${esc(sourceLabel(report))}</p>`}
          <button class="report-map-link" type="button" data-marker="report:${esc(report.id)}">Ver en el mapa</button>
        </div>
      </details>`;
    }).join('');
    list.querySelectorAll('[data-marker]').forEach(row => row.addEventListener('click', () => focusMarker(row.dataset.marker)));
  }
  function renderStations() {
    if (stationsUnavailable) { stationsList.textContent = 'No se pudieron consultar las estaciones. Intenta actualizar el mapa.'; return; }
    if (!stations.length) { stationsList.innerHTML = '<div class="empty-row"><strong>No hay estaciones conectadas.</strong><span>Las estaciones aparecerán aquí cuando envíen su primera lectura.</span></div>'; return; }
    stationsList.innerHTML = stations.map(s => { const offline = s.reading_status === 'offline' || !s.read_at; const value = offline || s.reading_value == null ? '—' : `${Number(s.reading_value).toLocaleString('es-MX')} ${esc(s.metric_unit || '')}`; return `<button type="button" class="data-row station-row" data-marker="station:${esc(s.id)}"><span class="row-main"><strong>${esc(s.name)}<i class="demo-tag ${demoView ? '' : 'live-tag'}">${demoView ? 'Ejemplo de prueba' : 'IoT'}</i></strong><small>${esc(s.station_code)} · ${esc(s.neighborhood)}</small><span>${esc(s.metric_name || s.sensor_type)} · ${offline ? 'sin señal' : `recibida ${esc(dateLabel(s.read_at))}`}</span></span><span class="reading-value ${offline ? 'offline' : esc(s.reading_status)}">${offline ? '—' : value}<small>${esc(states[s.reading_status] || s.reading_status || 'Sin señal')}</small></span></button>`; }).join('');
    stationsList.querySelectorAll('[data-marker]').forEach(row => row.addEventListener('click', () => focusMarker(row.dataset.marker)));
  }
  function focusMarker(id) {
    let marker = markers.get(String(id));
    if (!marker) return;
    if (!map.hasLayer(marker)) {
      activeLayer = 'all';
      render();
      marker = markers.get(String(id));
    }
    document.getElementById('mapa').scrollIntoView({ block: 'start' });
    map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14), { animate: false });
    marker.openPopup();
  }

  function moveMap(coordinates, zoom) {
    map.setView(coordinates, zoom, { animate: !reducedMotion.matches, duration: 0.32 });
  }
  const refreshButton = document.getElementById('refresh-map');
  const updateLabel = document.getElementById('map-update-label');

  async function readLayer(path) {
    const response = await fetch(path, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
      signal: AbortSignal.timeout(10000)
    });
    if (!response.ok) throw new Error('Servicio no disponible');
    const body = await response.json();
    if (!Array.isArray(body?.data)) throw new Error('Respuesta no válida');
    return body.data;
  }

  function fitVisibleMarkers() {
    if (!map || !needsInitialView || !markers.size) return;
    const points = [...markers.values()].filter(marker => map.hasLayer(marker)).map(marker => marker.getLatLng());
    if (!points.length) return;
    needsInitialView = false;
    map.fitBounds(points, { padding: [35, 35], maxZoom: 15, animate: false });
  }

  const offlineNotice = document.getElementById('map-unavailable');
  const demoLink = document.getElementById('demo-view-link');

  function setMapMode(showExamples) {
    if (demoView !== showExamples) needsInitialView = true;
    demoView = showExamples;
    document.body.classList.toggle('demo-mode', demoView);
    document.getElementById('demo-note').hidden = !demoView;
    document.getElementById('demo-mode-label').hidden = !demoView;
    demoLink.href = demoView ? '/?vista=real#mapa' : '/?demo=1#mapa';
    demoLink.textContent = demoView ? 'Ver solo datos reales' : 'Ver datos de prueba';
  }

  function showExampleData() {
    setMapMode(true);
    incidents = demoIncidents;
    stations = demoStations;
    updateLabel.textContent = 'Datos de prueba';
    render();
    fitVisibleMarkers();
  }

  async function loadData() {
    refreshButton.disabled = true;
    updateLabel.textContent = 'Consultando fuentes…';
    try {
      const [reportsResult, stationsResult] = await Promise.allSettled([
        readLayer('/api/incidents'), readLayer('/api/stations')
      ]);
      reportsUnavailable = reportsResult.status !== 'fulfilled';
      stationsUnavailable = stationsResult.status !== 'fulfilled';
      incidents = reportsUnavailable ? [] : reportsResult.value;
      stations = stationsUnavailable ? [] : stationsResult.value;
      const unavailable = reportsUnavailable || stationsUnavailable;
      offlineNotice.hidden = !unavailable;
      if (unavailable) {
        offlineNotice.textContent = reportsUnavailable && stationsUnavailable
          ? 'No pudimos consultar los reportes ni las estaciones. Intenta actualizar.'
          : reportsUnavailable ? 'No pudimos consultar los reportes.' : 'No pudimos consultar las estaciones.';
      }
      if (!realOnly && !unavailable && incidents.length === 0 && stations.length === 0) {
        showExampleData();
        return;
      }
      setMapMode(false);
      updateLabel.textContent = unavailable ? 'Consulta incompleta' : 'Consulta: ' + dateLabel(new Date()) + ' (hora local)';
      render();
      fitVisibleMarkers();
    } finally {
      refreshButton.disabled = false;
    }
  }
  document.getElementById('map').addEventListener('click', async event => {
    const button = event.target.closest('button[data-history]');
    if (!button) return;
    const output = button.nextElementSibling;
    button.disabled = true;
    output.textContent = 'Consultando lecturas…';
    try {
      const response = await fetch(`/api/stations/${encodeURIComponent(button.dataset.history)}/history`, {
        headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(10000)
      });
      if (!response.ok) throw new Error('Historial no disponible');
      const { data, unit } = await response.json();
      if (!data.length) {
        output.textContent = 'Sin lecturas en las últimas 24 horas.';
        return;
      }
      output.innerHTML = `<strong>Últimas 24 horas</strong><ul>${data.map(reading =>
        `<li>${esc(dateLabel(reading.read_at))} · ${esc(reading.value)} ${esc(unit)}</li>`
      ).join('')}</ul>`;
      button.hidden = true;
    } catch {
      output.textContent = 'No se pudo consultar el historial.';
    } finally {
      button.disabled = false;
    }
  });
  refreshButton.addEventListener('click', () => {
    loadData();
    loadBulletins();
  });
  document.querySelectorAll('[data-layer]').forEach(btn => btn.addEventListener('click', () => { activeLayer = btn.dataset.layer; render(); }));
  document.querySelectorAll('#category-filter,#status-filter,#date-filter').forEach(el => el.addEventListener('change', render));
  document.getElementById('reset-filters').addEventListener('click', () => { ['category-filter','status-filter','date-filter'].forEach(id => document.getElementById(id).value = ''); render(); });
  const locationFeedback = document.getElementById('location-feedback');
  const locationInput = document.getElementById('location-query');
  const locationResultLink = document.getElementById('location-result-link');
  document.getElementById('location-search').addEventListener('submit', async event => {
    event.preventDefault();
    if (!map) {
      locationFeedback.textContent = 'El mapa no está disponible. Consulta la lista de reportes.';
      return;
    }
    const query = locationInput.value.trim();
    if (!query) {
      locationFeedback.textContent = 'Escribe el nombre de tu colonia.';
      locationInput.focus();
      return;
    }

    const button = event.currentTarget.querySelector('button');
    button.disabled = true;
    locationResultLink.hidden = true;
    locationFeedback.textContent = 'Buscando colonia…';
    try {
      const url = new URL('https://nominatim.openstreetmap.org/search');
      url.search = new URLSearchParams({ q: query + ', Coatzacoalcos, Veracruz, México', format: 'jsonv2', limit: '1', addressdetails: '1', countrycodes: 'mx' });
      const response = await fetch(url, { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error('Búsqueda no disponible');
      const [place] = await response.json();
      if (!place) {
        locationFeedback.textContent = 'No encontramos esa colonia. Prueba con otra referencia.';
        return;
      }
      moveMap([Number(place.lat), Number(place.lon)], 14);
      const name = place.name || place.display_name?.split(',')[0];
      const area = place.address?.city || place.address?.town || place.address?.municipality;
      const label = [...new Set([name, area].filter(Boolean))].join(', ');
      locationFeedback.textContent = label ? `Ubicación encontrada: ${label}.` : 'Ubicación encontrada.';
      locationResultLink.hidden = false;
    } catch {
      locationFeedback.textContent = 'No se pudo hacer la búsqueda. Intenta de nuevo.';
    } finally {
      button.disabled = false;
    }
  });

  document.getElementById('locate-me').addEventListener('click', event => {
    if (!map) {
      locationFeedback.textContent = 'El mapa no está disponible. Consulta la lista de reportes.';
      return;
    }
    if (!navigator.geolocation) {
      locationFeedback.textContent = 'Tu navegador no puede compartir tu ubicación.';
      return;
    }
    const button = event.currentTarget;
    button.disabled = true;
    locationResultLink.hidden = true;
    locationFeedback.textContent = 'Buscando tu ubicación…';
    navigator.geolocation.getCurrentPosition(
      ({ coords }) => {
        moveMap([coords.latitude, coords.longitude], 14);
        locationFeedback.textContent = 'Mapa centrado en tu ubicación.';
        locationResultLink.hidden = false;
        button.disabled = false;
      },
      () => {
        locationFeedback.textContent = 'No pudimos usar tu ubicación. Puedes buscar tu colonia.';
        button.disabled = false;
      },
      { timeout: 10000 }
    );
  });
  document.querySelectorAll('[data-open-report]').forEach(b=>b.addEventListener('click',()=>{dialog.showModal();}));
  document.querySelector('.dialog-close').addEventListener('click',()=>dialog.close()); document.querySelector('.dialog-cancel').addEventListener('click',()=>dialog.close());
  async function loadBulletins() {
    const container = document.getElementById('local-bulletins');
    try {
      const entries = await readLayer('/api/bulletins');
      const active = entries.filter(entry => Date.parse(entry.expires_at) > Date.now());
      if (!active.length) {
        container.textContent = 'Todavía no hay boletines locales publicados aquí.';
        return;
      }
      const list = document.createElement('ul');
      for (const entry of active) {
        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = entry.url;
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = entry.title;
        const details = document.createElement('p');
        details.textContent = `${entry.area} · Emitido: ${dateLabel(entry.issued_at)} · Vigente hasta: ${dateLabel(entry.expires_at)}`;
        item.append(link, details);
        list.append(item);
      }
      container.replaceChildren(list);
    } catch {
      container.textContent = 'No se pudo consultar el listado local. Abre el boletín oficial.';
    }
  }

  loadBulletins();
  if (openExamples) {
    refreshButton.hidden = true;
    showExampleData();
  } else {
    loadData();
  }
})();
