/* Motor Parts Market — chat shop (vanilla JS, no build step) */
(() => {
  'use strict';
  const M = window.MPM = window.MPM || {};
  M.products = M.products || {};
  const $ = (s, r = document) => r.querySelector(s);
  const csrf = $('meta[name="csrf"]')?.content || '';
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // ── Theme ──────────────────────────────────────────────
  document.addEventListener('click', e => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    const root = document.documentElement;
    const dark = root.dataset.theme ? root.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    root.dataset.theme = dark ? 'light' : 'dark';
    try { localStorage.setItem('mpm-theme', root.dataset.theme); } catch (_) {}
  });

  const post = (url, data) => fetch(M.base + '/' + url, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrf, 'Accept': 'application/json' },
    body: new URLSearchParams(data),
  }).then(async r => {
    const j = await r.json().catch(() => null);
    if (!j) throw new Error('The connection dropped. Please try again.');
    return j;
  });

  // ── Sheet (details + enquiry) ──────────────────────────
  const sheet = $('#sheet'), sheetBody = $('#sheet-body');
  const openSheet = html => { if (!sheet) return; sheetBody.innerHTML = html; sheet.showModal ? sheet.showModal() : sheet.setAttribute('open', ''); sheet.querySelector('.sheet-in').scrollTop = 0; };
  const closeSheet = () => { if (!sheet) return; sheetBody.innerHTML = ''; sheet.close ? sheet.close() : sheet.removeAttribute('open'); };
  sheet?.addEventListener('click', e => { if (e.target === sheet || e.target.closest('[data-sheet-close]')) closeSheet(); });
  sheet?.addEventListener('close', () => { sheetBody.innerHTML = ''; });

  const gallery = p => {
    const items = [...p.images.map(i => ({ t: 'img', src: i.src, th: i.thumb })), ...p.videos.map(v => ({ t: 'vid', src: v.src, th: v.poster }))];
    if (!items.length) return `<div class="pd-main"><img src="${esc(p.thumb)}" alt="${esc(p.title)}"></div>`;
    const main = it => it.t === 'img'
      ? `<img src="${esc(it.src)}" alt="${esc(p.title)}">`
      : `<video src="${esc(it.src)}" poster="${esc(it.th)}" controls playsinline preload="metadata"></video>`;
    return `<div class="pd-gal" data-gal='${esc(JSON.stringify(items))}' data-title="${esc(p.title)}"><div class="pd-main">${main(items[0])}</div>
      ${items.length > 1 ? `<div class="pd-thumbs">${items.map((it, i) => `<button class="${i ? '' : 'on'}" data-gi="${i}" aria-label="${it.t === 'vid' ? 'Play video' : 'Photo'} ${i + 1}"><img src="${esc(it.th)}" alt="" loading="lazy">${it.t === 'vid' ? '<span class="play"><i class="fa-solid fa-play"></i></span>' : ''}</button>`).join('')}</div>` : ''}</div>`;
  };
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-gi]'); if (!b) return;
    const gal = b.closest('.pd-gal'), items = JSON.parse(gal.dataset.gal), it = items[+b.dataset.gi];
    gal.querySelector('.pd-main').innerHTML = it.t === 'img'
      ? `<img src="${esc(it.src)}" alt="${esc(gal.dataset.title)}">`
      : `<video src="${esc(it.src)}" poster="${esc(it.th)}" controls playsinline autoplay></video>`;
    gal.querySelectorAll('[data-gi]').forEach(x => x.classList.toggle('on', x === b));
  });

  const details = p => `<div class="pd">
      <div>${gallery(p)}</div>
      <div class="pd-info">
        <h2>${esc(p.title)}</h2>
        <p class="veh"><i class="fa-solid fa-car-side"></i> ${esc(p.make)} ${esc(p.model)} <span class="yr">${esc(p.years)}</span></p>
        <strong class="price${p.hasPrice ? '' : ' ask'}">${esc(p.price)}</strong>
        <dl class="facts">
          <dt>Type</dt><dd>${esc(p.categoryLabel)}</dd>
          <dt>Side</dt><dd>${esc(p.side)}</dd>
          <dt>Condition</dt><dd>${esc(p.grade)}</dd>
          ${p.defects ? `<dt>Known defects</dt><dd>${esc(p.defects)}</dd>` : ''}
          ${p.partNumber ? `<dt>Part number</dt><dd>${esc(p.partNumber)}</dd>` : ''}
          <dt>Stock</dt><dd>${esc(p.stock)}${p.checked ? ' · checked ' + esc(p.checked) : ''}</dd>
        </dl>
        ${p.description ? `<p>${esc(p.description)}</p>` : ''}
        <p class="note"><i class="fa-solid fa-circle-info"></i> Light shapes change between model years. Send us your vehicle year and we'll confirm it fits before you pay.</p>
        <div class="pd-cta">
          <button class="btn btn-primary" data-act="ask" data-id="${p.id}"><i class="fa-solid fa-cart-shopping"></i> Ask / Order</button>
          ${M.whatsapp ? `<a class="btn btn-wa" target="_blank" rel="noopener" href="${esc(M.whatsapp)}?text=${encodeURIComponent('Hi, I am interested in: ' + p.title + ' (' + p.years + ') ' + p.url)}"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>` : ''}
        </div>
        <a href="${esc(p.url)}">Open full page <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
      </div></div>`;

  let state = {};
  const vehicleText = () => [state.make, state.model, state.year].filter(Boolean).join(' ');

  const enquiryForm = p => `<form class="form" data-enquiry novalidate>
      <h2>${p ? 'Ask about / order this part' : 'Talk to a person'}</h2>
      <p class="sub">${p ? esc(p.title) + ' · ' + esc(p.years) : "Tell us what you need and we'll come back to you."} We confirm fitment, price and delivery with you before any payment.</p>
      <input type="hidden" name="product_id" value="${p ? p.id : ''}">
      <div class="row2">
        <div class="field"><label for="eq-name">Your name</label><input id="eq-name" name="name" required maxlength="120" autocomplete="name"></div>
        <div class="field"><label for="eq-phone">Cell / WhatsApp number</label><input id="eq-phone" name="phone" required inputmode="tel" maxlength="20" autocomplete="tel" placeholder="e.g. 082 123 4567"></div>
      </div>
      <div class="row2">
        <div class="field"><label for="eq-veh">Your vehicle and year</label><input id="eq-veh" name="vehicle" maxlength="160" value="${esc(vehicleText() || (p ? p.make + ' ' + p.model : ''))}" placeholder="e.g. Toyota Hilux 2018"></div>
        <div class="field"><label for="eq-area">Town / suburb (for delivery)</label><input id="eq-area" name="area" maxlength="120" autocomplete="address-level2"></div>
      </div>
      <div class="field"><label for="eq-msg">Message (optional)</label><textarea id="eq-msg" name="message" maxlength="1000" placeholder="Left or right side? Anything else we should know?"></textarea></div>
      <label class="check"><input type="checkbox" name="consent" value="1" required> <span>I agree that Motor Parts Market may contact me about this enquiry. See our <a href="${esc(M.base)}/privacy.php" target="_blank">privacy policy</a>.</span></label>
      <p class="err" role="alert" hidden></p>
      <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-paper-plane"></i> Send my enquiry</button>
    </form>`;

  document.addEventListener('submit', e => {
    const f = e.target.closest('[data-enquiry]'); if (!f) return;
    e.preventDefault();
    const err = $('.err', f), btn = $('button[type=submit]', f);
    const fail = m => { err.textContent = m; err.hidden = false; };
    const d = Object.fromEntries(new FormData(f));
    if (!d.name?.trim() || !d.phone?.trim()) return fail('Please fill in your name and number.');
    if (!d.consent) return fail('Please tick the box so we may contact you.');
    btn.disabled = true; err.hidden = true;
    post('ajax/enquiry.php', d).then(j => {
      if (!j.ok) { btn.disabled = false; return fail(j.error || 'Something went wrong.'); }
      f.outerHTML = `<div class="form"><h2>Thank you, ${esc(d.name.trim().split(' ')[0])}!</h2><p class="ok-box"><i class="fa-solid fa-circle-check"></i> We have your enquiry (ref ${esc(j.ref)}). We'll contact you on ${esc(d.phone)} to confirm the part, price and delivery.</p>
        ${j.whatsapp ? `<a class="btn btn-wa btn-block" target="_blank" rel="noopener" href="${esc(j.whatsapp)}"><i class="fa-brands fa-whatsapp"></i> Continue on WhatsApp now</a>` : ''}
        <button class="btn btn-ghost btn-block" data-sheet-close>Close</button></div>`;
      if (window.fbq) fbq('track', 'Lead');
      if (window.gtag) gtag('event', 'generate_lead');
    }).catch(x => { btn.disabled = false; fail(x.message); });
  });

  // Product page: inline enquiry form placeholder
  document.querySelectorAll('[data-enquiry-slot]').forEach(el => { el.innerHTML = enquiryForm(M.products[el.dataset.enquirySlot] || null); });

  document.addEventListener('click', e => {
    const b = e.target.closest('[data-act]');
    if (b) {
      const id = b.dataset.id || b.closest('[data-id]')?.dataset.id, p = M.products[id];
      if (!p) return;
      e.preventDefault();
      if (!sheet) { location.href = p.url; return; }
      openSheet(b.dataset.act === 'ask' ? enquiryForm(p) : details(p));
      return;
    }
    if (e.target.closest('[data-enquire]')) { e.preventDefault(); closeSide(); openSheet(enquiryForm(null)); }
  });

  // ── Chat (home page only) ──────────────────────────────
  const form = $('#composer'); if (!form) return;
  const input = $('#say'), msgs = $('#msgs'), thread = $('#thread'), quick = $('#quick'), shell = $('#shell');
  const botIcon = '<span class="avatar"><svg viewBox="0 0 32 32"><path d="M5 16c0-5 4-9 9-9h3v18h-3c-5 0-9-4-9-9z" fill="currentColor"/><path d="M21 9l6-2M21 16h7M21 23l6 2" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg></span>';
  let busy = false;

  const closeSide = () => shell?.classList.remove('side-open');
  document.addEventListener('click', e => {
    if (e.target.closest('[data-side-open]')) shell.classList.add('side-open');
    else if (e.target.closest('[data-side-close]')) closeSide();
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSide(); });

  const card = p => `<article class="pcard glass" data-id="${p.id}">
      <button class="pcard-img" data-act="view" aria-label="View photos of ${esc(p.title)}">
        <img src="${esc(p.thumb)}" alt="${esc(p.title + ' ' + p.years)}" width="560" height="420" loading="lazy" decoding="async">
        <span class="badge">${esc(p.categoryLabel)}</span>
        ${p.videos.length ? '<span class="badge badge-r"><i class="fa-solid fa-play"></i> Video</span>' : ''}
        ${p.reserved ? '<span class="badge badge-warn">Reserved</span>' : ''}
      </button>
      <div class="pcard-body">
        <h3><a href="${esc(p.url)}" data-act="view">${esc(p.title)}</a></h3>
        <p class="veh"><i class="fa-solid fa-car-side"></i> ${esc(p.make)} ${esc(p.model)} <span class="yr">${esc(p.years)}</span></p>
        <p class="meta">${esc(p.side)} · ${esc(p.grade)}</p>
        <div class="pcard-foot"><strong class="price${p.hasPrice ? '' : ' ask'}">${esc(p.price)}</strong><button class="btn btn-primary btn-sm" data-act="ask">Ask / Order</button></div>
      </div></article>`;

  const scrollTo = el => requestAnimationFrame(() => thread.scrollTo({ top: el.offsetTop - 12, behavior: 'smooth' }));
  const addUser = t => { msgs.insertAdjacentHTML('beforeend', `<div class="msg msg-user"><div class="bubble">${esc(t)}</div></div>`); return msgs.lastElementChild; };
  const addBot = (html, extra = '') => { msgs.insertAdjacentHTML('beforeend', `<div class="msg msg-bot">${botIcon}<div class="body"><div class="bubble">${html}</div>${extra}</div></div>`); return msgs.lastElementChild; };
  const setQuick = list => { quick.innerHTML = (list || []).map(q => `<button type="button" class="chip" data-say="${esc(q)}">${esc(q)}</button>`).join(''); };

  const started = () => { $('#hero').hidden = true; $('#stock').hidden = true; };

  function say(text) {
    text = (text || '').trim();
    if (!text || busy) return;
    closeSide();
    if (/^what year/i.test(text)) { started(); const b = addBot('Type the year of your vehicle, for example <b>2018</b>.'); scrollTo(b); input.focus(); return; }
    if (/^request this part/i.test(text)) { openSheet(enquiryForm(null)); return; }
    started();
    const u = addUser(text); scrollTo(u);
    input.value = ''; grow();
    busy = true; form.querySelector('.send').disabled = true;
    const wait = addBot('<span class="typing" aria-label="Typing"><i></i><i></i><i></i></span>');
    post('ajax/chat.php', { message: text, state: JSON.stringify(state) }).then(j => {
      wait.remove();
      if (!j.ok) { addBot(esc(j.error || 'Something went wrong. Please try again.')); return; }
      state = j.state || {};
      (j.products || []).forEach(p => { M.products[p.id] = p; });
      let extra = '';
      if (j.products?.length) extra += `<div class="grid">${j.products.map(card).join('')}</div>`;
      if (j.action) extra += `<div class="chips"><button class="btn btn-primary btn-sm" data-enquire><i class="fa-solid fa-headset"></i> ${j.action === 'request' ? 'Request this part' : 'Leave my details'}</button>${M.whatsapp ? `<a class="btn btn-wa btn-sm" target="_blank" rel="noopener" href="${esc(M.whatsapp)}"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>` : ''}</div>`;
      addBot(esc(j.reply), extra);
      setQuick(j.quick);
      document.querySelectorAll('.veh-btn').forEach(b => b.classList.toggle('on', !!state.model && b.dataset.say === state.make + ' ' + state.model));
      scrollTo(u);
    }).catch(x => { wait.remove(); addBot(esc(x.message)); })
      .finally(() => { busy = false; form.querySelector('.send').disabled = false; });
  }

  const grow = () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; };
  input.addEventListener('input', grow);
  input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); say(input.value); } });
  form.addEventListener('submit', e => { e.preventDefault(); say(input.value); });
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-say]');
    if (b) { say(b.dataset.say); return; }
    if (e.target.closest('[data-new-chat]')) {
      state = {}; msgs.innerHTML = ''; setQuick([]); $('#hero').hidden = false; $('#stock').hidden = false;
      document.querySelectorAll('.veh-btn.on').forEach(b => b.classList.remove('on'));
      closeSide(); thread.scrollTo({ top: 0 }); input.focus();
    }
  });
})();
