(function () {
  'use strict';
  var root = document.getElementById('iwLiveOverview');
  if (!root) return;
  var metrics = root.querySelectorAll('[data-live-metric]');
  var bars = document.getElementById('iwLiveOverviewBars');
  var recent = document.getElementById('iwLiveOverviewRecent');
  var running = false;
  function node(tag, className, value) {
    var item = document.createElement(tag);
    if (className) item.className = className;
    if (value !== undefined) item.textContent = String(value);
    return item;
  }
  function update(summary) {
    metrics.forEach(function (field) {
      var key = field.getAttribute('data-live-metric');
      field.textContent = String(Math.max(0, Number(summary[key]) || 0));
    });
    if (bars) {
      var byDay = {};
      (Array.isArray(summary.activity) ? summary.activity : []).forEach(function (item) {
        byDay[String(item.day)] = Math.max(0, Number(item.total) || 0);
      });
      var days = [];
      for (var i = 6; i >= 0; i--) {
        var date = new Date();
        date.setHours(12,0,0,0);
        date.setDate(date.getDate() - i);
        var stamp = [date.getFullYear(),String(date.getMonth()+1).padStart(2,'0'),
          String(date.getDate()).padStart(2,'0')].join('-');
        days.push({day:stamp,count:byDay[stamp] || 0});
      }
      var peak = Math.max(1,...days.map(function (d) { return d.count; }));
      var fragment = document.createDocumentFragment();
      days.forEach(function (entry) {
        var bar = node('span','iw-live-overview-bar');
        bar.title = entry.day + ': ' + entry.count + ' messages';
        var fill = node('i');
        fill.style.height = Math.max(4,Math.round(entry.count/peak*100))+'%';
        bar.append(fill,node('small','',entry.day.slice(5)));
        fragment.appendChild(bar);
      });
      bars.replaceChildren(fragment);
      bars.setAttribute('aria-label','Chat messages in the last seven days: '+
        days.map(function (entry) { return entry.day+': '+entry.count; }).join(', '));
    }
    if (recent) {
      var items = Array.isArray(summary.recent) ? summary.recent : [];
      var fragmentRecent = document.createDocumentFragment();
      items.forEach(function (visitor) {
        var line = node('div');
        var dot = node('span','iw-live-overview-presence'+(visitor.online?' is-online':''));
        dot.setAttribute('aria-hidden','true');
        line.append(dot,node('strong','',visitor.name||'Visitor'),
          node('small','',visitor.page||'Page not shared'));
        fragmentRecent.appendChild(line);
      });
      if (!items.length) fragmentRecent.appendChild(node('p','','No support conversations yet.'));
      recent.replaceChildren(fragmentRecent);
    }
  }
  async function refresh() {
    if (running || document.visibilityState === 'hidden') return;
    running = true;
    try {
      var fields = new FormData();
      fields.set('csrf_token',root.dataset.csrf);
      fields.set('action','summary');
      var response = await fetch(root.dataset.endpoint,{
        method:'POST',credentials:'same-origin',cache:'no-store',
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        body:fields
      });
      var result = await response.json();
      if (response.ok && result.ok && result.summary) update(result.summary);
    } catch (ignore) {
      // Initial server-rendered numbers remain visible during connectivity loss.
    } finally { running = false; }
  }
  refresh();
  var interval = window.setInterval(refresh,15000);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') refresh();
  });
  window.addEventListener('pagehide',function () { window.clearInterval(interval); });
})();
