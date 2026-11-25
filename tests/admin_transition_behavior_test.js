/* Navigate between backend pages without hijacking browser behavior. */
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const src = fs.readFileSync(require('node:path').join(__dirname,'../assets/js/admin-transition.js'),'utf8');

function testScenario({ exists = true, reduced = false,
  pageUrl = 'https://portal.iworksgt.com/index.php?page=dashboard' } = {}) {
  const parsed = new URL(pageUrl);
  const classes = new Set(['iw-admin-transition-enter']);
  const listeners = {};
  const winListeners = {};
  const timers = new Map();
  let nextId = 1;
  const document = {
    readyState: 'loading',
    documentElement: {
      classList: {
        add(...names) { for (const n of names) classes.add(n); },
        remove(...names) { for (const n of names) classes.delete(n); }
      }
    },
    getElementById(id) { return exists && id === 'iwAdminTransition' ? {} : null; },
    addEventListener(name, callback) { listeners[name] = callback; }
  };
  const window = {
    location: { href: parsed.href, hostname: parsed.hostname, pathname: parsed.pathname,
      search: parsed.search, hash: parsed.hash },
    matchMedia() { return { matches: reduced }; },
    addEventListener(name, callback) { winListeners[name] = callback; },
    setTimeout(callback, ms) { const id = nextId++; timers.set(id, { callback, ms }); return id; },
    clearTimeout(id) { timers.delete(id); }
  };
  vm.runInNewContext(src, { window, document, URL, console }, { timeout: 1000 });
  function flush() {
    const queued = [...timers.values()];
    timers.clear();
    for (const t of queued) t.callback();
    return queued.map(x => x.ms);
  }
  function click(href, extras = {}) {
    const anchor = {
      href,
      getAttribute(name) {
        if (name === 'href') return href;
        if (name === 'target') return extras.target || '';
        return '';
      },
      hasAttribute(name) { return name === 'download' && !!extras.download; }
    };
    listeners.click({
      defaultPrevented: !!extras.prevented, button: extras.button ?? 0,
      metaKey: !!extras.metaKey, ctrlKey: !!extras.ctrlKey,
      shiftKey: !!extras.shiftKey, altKey: !!extras.altKey,
      target: { closest() { return anchor; } }
    });
  }
  return { classes, listeners, winListeners, timers, flush, click };
}
let a = testScenario();
assert.ok(a.listeners.DOMContentLoaded);
assert.equal(a.listeners.click, undefined, 'no outgoing animation or click interception');
a.listeners.DOMContentLoaded();
assert.deepEqual(a.flush(), [160]);
assert.equal(a.classes.has('iw-admin-transition-enter'), false);
a.classes.add('iw-admin-transition-enter');
a.winListeners.pageshow({persisted:true});
assert.equal(a.classes.has('iw-admin-transition-enter'), false);
a.classes.add('iw-admin-transition-enter');
a.winListeners.pagehide();
assert.equal(a.classes.has('iw-admin-transition-enter'), false);
const reduced = testScenario({reduced:true});
reduced.listeners.DOMContentLoaded();
assert.deepEqual(reduced.flush(),[0]);
const absent = testScenario({exists:false});
assert.deepEqual(Object.keys(absent.listeners),[]);
console.log('Single arrival navigation behavior passed');
