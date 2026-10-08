(function () {
  'use strict';

  // Copy link buttons
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-copy]');
    if (!b) { return; }
    var text = b.getAttribute('data-copy'), old = b.textContent;
    function done() { b.textContent = 'Պատճենված է'; setTimeout(function () { b.textContent = old; }, 1600); }
    function fallback() {
      var i = b.parentNode.querySelector('.link');
      i.focus(); i.select();
      try { document.execCommand('copy'); done(); } catch (err) {}
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, fallback);
    } else { fallback(); }
  });

  // Confirm before delete / reset
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); }
  });

  // Select link text on focus
  document.addEventListener('focusin', function (e) {
    if (e.target.classList && e.target.classList.contains('link')) { e.target.select(); }
  });

  // Busy-days calendar
  var grid = document.getElementById('busy-grid');
  var input = document.getElementById('busy-dates');
  if (!grid || !input) { return; }

  var months = ['Հունվար', 'Փետրվար', 'Մարտ', 'Ապրիլ', 'Մայիս', 'Հունիս', 'Հուլիս', 'Օգոստոս', 'Սեպտեմբեր', 'Հոկտեմբեր', 'Նոյեմբեր', 'Դեկտեմբեր'];
  var todayStr = grid.getAttribute('data-today');
  var tp = todayStr.split('-');
  var today = { y: +tp[0], m: +tp[1] - 1 };
  var view = { y: today.y, m: today.m };
  var MAX_AHEAD = 6;
  var set = {};
  input.value.split(',').forEach(function (d) { if (d) { set[d] = true; } });

  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function offset() { return (view.y - today.y) * 12 + (view.m - today.m); }
  function sync() {
    var list = Object.keys(set).sort();
    input.value = list.join(',');
    document.getElementById('busy-count').textContent = list.length ? 'Նշված է ' + list.length + ' օր' : 'Դեռ օր չի նշված';
  }
  function render() {
    document.getElementById('busy-month').textContent = months[view.m] + ' ' + view.y;
    grid.innerHTML = '';
    var first = (new Date(view.y, view.m, 1).getDay() + 6) % 7;
    var count = new Date(view.y, view.m + 1, 0).getDate();
    for (var d = 1; d <= count; d++) {
      (function (d) {
        var s = view.y + '-' + pad(view.m + 1) + '-' + pad(d);
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'day' + (set[s] ? ' blocked' : '') + (s === todayStr ? ' today' : '');
        b.textContent = d;
        if (d === 1) { b.style.gridColumnStart = first + 1; }
        if (s < todayStr) { b.disabled = true; }
        b.setAttribute('aria-pressed', set[s] ? 'true' : 'false');
        b.addEventListener('click', function () {
          if (set[s]) { delete set[s]; } else { set[s] = true; }
          sync(); render();
        });
        grid.appendChild(b);
      })(d);
    }
    document.getElementById('busy-prev').disabled = offset() <= 0;
    document.getElementById('busy-next').disabled = offset() >= MAX_AHEAD;
  }
  document.getElementById('busy-prev').addEventListener('click', function () {
    if (offset() <= 0) { return; }
    view.m--; if (view.m < 0) { view.m = 11; view.y--; }
    render();
  });
  document.getElementById('busy-next').addEventListener('click', function () {
    if (offset() >= MAX_AHEAD) { return; }
    view.m++; if (view.m > 11) { view.m = 0; view.y++; }
    render();
  });
  sync(); render();
})();
