(function () {
  'use strict';
  var root = document.getElementById('iwLiveInbox');
  if (!root || !root.dataset.endpoint) return;
  var endpoint = root.dataset.endpoint, csrf = root.dataset.csrf;
  var threadsNode = document.getElementById('iwLiveThreads');
  var messagesNode = document.getElementById('iwLiveMessages');
  var filterNode = document.getElementById('iwLiveFilter');
  var titleNode = document.getElementById('iwLiveChatHeading');
  var metaNode = document.getElementById('iwLiveChatMeta');
  var statusNode = document.getElementById('iwLiveStatus');
  var soundButton = document.getElementById('iwLiveSound');
  var sendButton = document.getElementById('iwLiveSend');
  var closeButton = document.getElementById('iwLiveClose');
  var replyForm = document.getElementById('iwLiveReplyForm');
  var replyInput = document.getElementById('iwLiveReply');
  var activeId = 0, lastMessageId = 0, allThreads = [], polled = false;
  var lastInbound = {}, soundEnabled = false, audioContext = null, pending = false;
  var pollTimer = null;

  function text(node, value) { if (node) node.textContent = String(value == null ? '' : value); }
  function el(name, cls, value) {
    var node = document.createElement(name);
    if (cls) node.className = cls;
    if (value != null) node.textContent = String(value);
    return node;
  }
  function beep() {
    if (!soundEnabled || !audioContext) return;
    try {
      if (audioContext.state === 'suspended') audioContext.resume();
      [0, 0.16].forEach(function (delay) {
        var oscillator = audioContext.createOscillator();
        var gain = audioContext.createGain();
        oscillator.type = 'sine';
        oscillator.frequency.value = delay === 0 ? 750 : 920;
        gain.gain.setValueAtTime(0.0001, audioContext.currentTime + delay);
        gain.gain.exponentialRampToValueAtTime(0.12, audioContext.currentTime + delay + 0.018);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + delay + 0.13);
        oscillator.connect(gain);
        gain.connect(audioContext.destination);
        oscillator.start(audioContext.currentTime + delay);
        oscillator.stop(audioContext.currentTime + delay + 0.14);
      });
    } catch (ignore) {}
  }
  soundButton.addEventListener('click', function () {
    if (!soundEnabled) {
      var AudioConstructor = window.AudioContext || window.webkitAudioContext;
      if (!AudioConstructor) { text(statusNode, 'Audio alerts are not supported in this browser.'); return; }
      try { audioContext = audioContext || new AudioConstructor(); audioContext.resume(); }
      catch (error) { text(statusNode, 'Browser audio is unavailable.'); return; }
    }
    soundEnabled = !soundEnabled;
    soundButton.textContent = soundEnabled ? 'Sound alerts on' : 'Enable sound alerts';
    soundButton.setAttribute('aria-pressed', String(soundEnabled));
    if (soundEnabled) beep();
  });

  async function api(action, args) {
    var data = new FormData();
    data.append('csrf_token', csrf);
    data.append('action', action);
    Object.keys(args || {}).forEach(function (key) { data.append(key, String(args[key])); });
    var result = await fetch(endpoint, {
      method: 'POST', credentials: 'same-origin', cache: 'no-store',
      headers: {'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'}, body: data
    });
    var body;
    try { body = await result.json(); }
    catch (ignore) { throw new Error('Unexpected server response. Refresh the inbox.'); }
    if (!result.ok || !body.ok) throw new Error(body.message || 'Unable to load chat.');
    return body;
  }

  function renderThreads() {
    var search = filterNode.value.trim().toLowerCase();
    var matches = allThreads.filter(function (thread) {
      return !search || [thread.name,thread.email,thread.id,thread.country_code]
        .some(function (x) { return String(x || '').toLowerCase().includes(search); });
    });
    threadsNode.replaceChildren();
    if (!matches.length) {
      threadsNode.appendChild(el('p','iw-live-muted', search ? 'No matching conversations.' : 'No chats yet. New messages appear automatically.'));
      return;
    }
    matches.forEach(function (thread) {
      var button = el('button','iw-live-thread');
      button.type='button';
      button.classList.toggle('selected', Number(thread.id) === activeId);
      button.setAttribute('aria-pressed',String(Number(thread.id) === activeId));
      var top = el('span','iw-live-thread-top');
      var name = el('strong','',thread.name || 'Visitor');
      var dot = el('i','iw-live-presence ' + (Number(thread.visitor_online) ? 'is-online':'is-offline'));
      dot.title = Number(thread.visitor_online) ? 'Visitor online' : 'Visitor offline';
      top.append(dot,name);
      if (Number(thread.unread) > 0) top.append(el('b','iw-live-unread',Math.min(Number(thread.unread),99)));
      var middle=el('span','iw-live-thread-preview',thread.preview || 'Awaiting first message');
      var bottom=el('span','iw-live-thread-bottom');
      bottom.append(el('span','', [thread.country_code || 'Location unavailable',thread.status].join(' · ')));
      bottom.append(el('span','', '#'+thread.id));
      button.append(top,middle,bottom);
      button.addEventListener('click',function () {
        if (Number(thread.id) === activeId) return;
        activeId = Number(thread.id);
        lastMessageId = 0;
        messagesNode.replaceChildren();
        renderThreads();
        populateActive();
        poll();
      });
      threadsNode.appendChild(button);
    });
  }

  function populateActive() {
    var thread = allThreads.find(function (x) { return Number(x.id) === activeId; });
    if (!thread) {
      text(titleNode,'Select a conversation');
      text(metaNode,'Visitor details will appear here.');
      replyInput.disabled = true; sendButton.disabled = true; closeButton.disabled = true;
      return;
    }
    text(titleNode,thread.name + ' · #' + thread.id);
    text(metaNode,thread.email + '  ·  ' +
       (thread.country_code ? 'Country hint: '+thread.country_code : 'Country unavailable') +
       '  ·  Network: '+(thread.ip_prefix || 'unknown') +
       '  ·  '+(Number(thread.visitor_online) ? 'Online' : 'Offline'));
    var open = thread.status === 'open';
    replyInput.disabled = !open; sendButton.disabled = !open; closeButton.disabled = !open;
    replyInput.placeholder = open ? 'Write a reply to this visitor…' : 'Conversation ended';
  }

  function renderMessages(newMessages) {
    if (!Array.isArray(newMessages)) return;
    newMessages.forEach(function (message) {
      var id=Number(message.id);
      if (!Number.isFinite(id) || id <= lastMessageId) return;
      lastMessageId=id;
      var node=el('div','iw-live-message ' + (message.sender === 'staff' ? 'is-staff':'is-visitor'));
      var author=el('span','iw-live-message-author',message.sender === 'staff' ? 'iWorks team':'Visitor');
      var body=el('p','',message.body);
      var time=el('time','',message.created_at);
      node.append(author,body,time);
      messagesNode.appendChild(node);
    });
    if (newMessages.length) messagesNode.scrollTop=messagesNode.scrollHeight;
  }

  var started = false;
  async function poll() {
    if (pending || document.visibilityState === 'hidden') return;
    pending = true;
    try {
      var data = await api('poll', {thread_id:activeId,after_id:lastMessageId});
      var newThreads = Array.isArray(data.threads) ? data.threads : [];
      var changes = false;
      newThreads.forEach(function (thread) {
        var id=String(thread.id), inbound=Number(thread.last_inbound_id || 0);
        if (started && (!Object.prototype.hasOwnProperty.call(lastInbound,id) ||
              inbound > Number(lastInbound[id] || 0))) changes = true;
        lastInbound[id] = Math.max(Number(lastInbound[id] || 0), inbound);
      });
      if (started && changes) beep();
      started=true;
      allThreads=newThreads;
      text(document.getElementById('iwLiveTotal'),newThreads.length);
      text(document.getElementById('iwLiveOpen'),newThreads.filter(function (x) { return x.status==='open'; }).length);
      text(document.getElementById('iwLiveOnline'),newThreads.filter(function (x) { return Number(x.visitor_online); }).length);
      text(document.getElementById('iwLiveAgents'),data.online_agents || 0);
      renderThreads();
      populateActive();
      renderMessages(data.messages || []);
      text(statusNode,'Connected · Checking for messages every 5 seconds');
    } catch (error) {
      text(statusNode,error.message || 'Connection lost. Retrying…');
    } finally { pending=false; }
  }

  replyForm.addEventListener('submit',async function (event) {
    event.preventDefault();
    var body=replyInput.value.trim();
    if (!activeId || !body || pending) return;
    sendButton.disabled=true;
    try {
      var data=await api('send',{thread_id:activeId,message:body,after_id:lastMessageId});
      replyInput.value='';
      renderMessages(data.messages || []);
      allThreads=data.threads || allThreads;
      renderThreads();
      text(statusNode,'Reply delivered.');
    } catch (error) { text(statusNode,error.message || 'Message not sent.'); }
    finally { sendButton.disabled=false; }
  });
  replyInput.addEventListener('keydown',function (event) {
    if (event.key==='Enter' && !event.shiftKey) {
      event.preventDefault(); replyForm.requestSubmit();
    }
  });
  closeButton.addEventListener('click',async function () {
    if (!activeId || !window.confirm('Close this conversation? The visitor will see it as ended.')) return;
    try {
      var data=await api('close',{thread_id:activeId});
      allThreads=data.threads || allThreads;
      renderThreads(); populateActive();
    } catch (error) { text(statusNode,error.message || 'Unable to close chat.'); }
  });
  filterNode.addEventListener('input',renderThreads);
  document.addEventListener('visibilitychange',function () { if (document.visibilityState==='visible') poll(); });
  poll();
  pollTimer=window.setInterval(poll,5000);
  window.addEventListener('pagehide',function () { if (pollTimer) window.clearInterval(pollTimer); });
})();
