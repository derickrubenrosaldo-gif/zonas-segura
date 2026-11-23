(() => {
  const track = document.getElementById('phone-track');
  const total = track.children.length;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  let timer;
  let interacting = false;
  let inView = !('IntersectionObserver' in window);

  function currentIndex() {
    return Math.round(track.scrollLeft / (track.clientWidth || 1));
  }

  function show(index, wrap = false) {
    track.scrollTo({
      left: index * track.clientWidth,
      behavior: reduced.matches || wrap ? 'instant' : 'smooth'
    });
  }

  function schedule() {
    window.clearTimeout(timer);
    if (interacting || reduced.matches || !inView || document.hidden || total < 2) return;
    timer = window.setTimeout(() => {
      const next = (currentIndex() + 1) % total;
      show(next, next === 0);
      schedule();
    }, 7000);
  }

  function stop() {
    interacting = true;
    window.clearTimeout(timer);
  }

  track.addEventListener('pointerdown', stop);
  track.addEventListener('focusin', stop);
  track.addEventListener('wheel', event => {
    if (event.deltaX) stop();
  }, { passive: true });
  track.addEventListener('keydown', event => {
    if (event.target !== track || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
    event.preventDefault();
    const step = event.key === 'ArrowLeft' ? -1 : 1;
    show(Math.max(0, Math.min(total - 1, currentIndex() + step)));
  });
  document.addEventListener('visibilitychange', schedule);
  reduced.addEventListener('change', schedule);

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(([entry]) => {
      inView = entry.intersectionRatio >= 0.25;
      schedule();
    }, { threshold: 0.25 });
    observer.observe(track);
  }
  schedule();
})();
