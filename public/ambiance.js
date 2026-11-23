(() => {
  const brief = document.getElementById('local-conditions');
  const intro = document.querySelector('.intro');
  const weatherSurfaces = [brief, intro];
  const reading = document.getElementById('weather-reading');
  const pause = document.getElementById('weather-motion');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const sectionLinks = document.querySelectorAll('.portal-nav a, .mobile-nav a');

  function updateSectionLinks() {
    const section = window.location.hash || '#mapa';
    for (const link of sectionLinks) {
      if (link.hash === section) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    }
  }
  updateSectionLinks();
  window.addEventListener('hashchange', updateSectionLinks);

  const weatherUrl = new URL('https://api.open-meteo.com/v1/forecast');
  weatherUrl.search = new URLSearchParams({
    latitude: '18.147', longitude: '-94.42',
    current: 'temperature_2m,is_day,weather_code,rain,showers',
    timezone: 'America/Mexico_City'
  });

  function describeWeather(current) {
    const code = current.weather_code;
    if ([95, 96, 97, 99].includes(code)) return ['storm', 'Tormenta eléctrica'];
    if (current.rain > 0 || current.showers > 0 || [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82].includes(code)) {
      return ['rain', 'Lluvia'];
    }
    if (code === 0 || code === 1) {
      return current.is_day === 1 ? ['sun', 'Soleado'] : ['night', 'Despejado'];
    }
    if ([45, 48].includes(code)) return ['overcast', 'Niebla'];
    if ([2, 3].includes(code)) return ['overcast', code === 2 ? 'Parcialmente nublado' : 'Nublado'];
    return ['neutral', 'Condiciones locales'];
  }

  function prepareRain(rain, count, size) {
    if (rain.childElementCount === count) return;
    const drops = [];
    for (let index = 0; index < count; index += 1) {
      const depth = index % 3;
      const duration = 0.52 + (2 - depth) * 0.23 + Math.random() * 0.22;
      const drop = document.createElement('i');
      drop.style.setProperty('--drop-x', 100 * (index + Math.random()) / count + '%');
      drop.style.setProperty('--drop-time', duration + 's');
      drop.style.setProperty('--drop-delay', -Math.random() * duration + 's');
      drop.style.setProperty('--drop-length', (11 + depth * 10 + Math.random() * 9) * size + 'px');
      drop.style.setProperty('--drop-width', 0.72 + depth * 0.34 + 'px');
      drop.style.setProperty('--drop-opacity', 0.29 + depth * 0.18 + Math.random() * 0.07);
      drops.push(drop);
    }
    rain.replaceChildren(...drops);
  }

  function prepareSplashes(surface, count) {
    if (surface.childElementCount === count) return;
    const splashes = [];
    for (let index = 0; index < count; index += 1) {
      const splash = document.createElement('span');
      splash.className = 'rain-impact';
      splash.style.setProperty('--impact-x', (index + 0.35 + Math.random() * 0.4) * 100 / count + '%');
      splash.style.setProperty('--impact-y', 4 + Math.random() * 22 + 'px');
      splash.style.setProperty('--impact-interval', 2.1 + Math.random() * 2.6 + 's');
      splash.style.setProperty('--impact-delay', -Math.random() * 4.7 + 's');
      splashes.push(splash);
    }
    surface.replaceChildren(...splashes);
  }

  let paused = false;
  function applyWeather(type, code) {
    const wet = type === 'rain' || type === 'storm';
    const heavy = wet && (type === 'storm' || [65, 67, 82].includes(code));
    if (wet) {
      const light = [51, 53, 56, 61, 80].includes(code);
      prepareRain(brief.querySelector('.weather-rain'), heavy ? 16 : light ? 8 : 12, 0.48);
      prepareRain(intro.querySelector('.weather-rain'), heavy ? 64 : light ? 28 : 44, 1);
      prepareSplashes(intro.querySelector('.rain-surface'), heavy ? 12 : light ? 5 : 8);
    }
    intro.classList.toggle('weather-heavy', heavy);
    weatherSurfaces.forEach(surface => { surface.dataset.weather = type; });
  }

  function updateMotion() {
    const animated = ['rain', 'storm', 'sun'].includes(brief.dataset.weather);
    pause.hidden = !animated || reducedMotion.matches;
    weatherSurfaces.forEach(surface => { surface.classList.toggle('weather-paused', paused); });
    pause.textContent = paused ? 'Reanudar animación del clima' : 'Pausar animación del clima';
  }
  pause.addEventListener('click', () => {
    paused = !paused;
    updateMotion();
  });
  reducedMotion.addEventListener('change', updateMotion);

  async function loadWeather() {
    try {
      const response = await fetch(weatherUrl, { signal: AbortSignal.timeout(7500) });
      if (!response.ok) throw new Error('Clima no disponible');
      const { current } = await response.json();
      if (!Number.isFinite(current?.temperature_2m) || !Number.isFinite(current?.weather_code)) {
        throw new Error('Lectura incompleta');
      }
      const [type, label] = describeWeather(current);
      applyWeather(type, current.weather_code);
      reading.textContent = `${Math.round(current.temperature_2m)} °C · ${label}`;
    } catch {
      applyWeather('neutral');
      reading.textContent = 'Clima no disponible';
    }
    updateMotion();
  }

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      for (const { target, isIntersecting } of entries) {
        if (!isIntersecting) continue;
        if (!reducedMotion.matches) target.classList.add('scroll-arrival');
        observer.unobserve(target);
      }
    }, { threshold: 0.12 });
    document.querySelectorAll('[data-scroll-reveal]').forEach(section => observer.observe(section));
    const weatherObserver = new IntersectionObserver(entries => {
      for (const { target, isIntersecting } of entries) {
        target.classList.toggle('weather-offscreen', !isIntersecting);
      }
    });
    weatherSurfaces.forEach(surface => weatherObserver.observe(surface));
  }

  fetch('/api/site-settings', { signal: AbortSignal.timeout(5000) })
    .then(response => response.ok ? response.json() : null)
    .then(payload => {
      const settings = payload?.data;
      if (!settings) return;
      if (typeof settings.title === 'string' && settings.title) {
        document.getElementById('page-title').textContent = settings.title;
      }
      if (typeof settings.location === 'string' && settings.location) {
        document.getElementById('portal-location').textContent = settings.location;
      }
      if (typeof settings.notice === 'string' && settings.notice) {
        const notice = document.getElementById('portal-notice');
        notice.textContent = settings.notice;
        notice.hidden = false;
      }
    })
    .catch(() => {
      // The public page already includes default text.
    });

  loadWeather();
  window.setInterval(() => {
    if (document.visibilityState === 'visible') loadWeather();
  }, 10 * 60 * 1000);
})();
