    // public/js/sales.js
    // Minimal fixes: loader reference counting, ensure popover z-index, keep "per-period" chart labels,
    // make "Total Sales" show all-time when month filter active (year still updates).
    // Ready to copy/paste.

    if (window.__salesInit) {
      console.warn('sales.js: already initialized — continuing to (re)attach listeners safely');
    } else {
      window.__salesInit = true;
    }

    (function () {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
      } else {
        init();
      }

      function init() {
        const cfg = window.SALES_CONFIG || {};
        const ROUTE_REALTIME = cfg.realtime || '';
        const ROUTE_CUSTOMER = cfg.customerDetails || '';
    

        if (!ROUTE_REALTIME) console.warn('sales.js: realtime route missing (window.SALES_CONFIG.realtime).');
        if (!ROUTE_CUSTOMER) console.warn('sales.js: customer details route missing (window.SALES_CONFIG.customerDetails).');
        if (typeof Chart === 'undefined') console.warn('sales.js: Chart.js not detected.');

        // safe read of dateTo
        let currentDate = cfg.dateTo || (document.getElementById('dateTo') && document.getElementById('dateTo').value) || '';
        let latestMonthlyBreakdown =
    cfg.salesByMonth || [];
        // ---------- Utilities ----------
        function formatPesoShort(v) {
          const val = Number(v) || 0;
          if (val >= 1e9) return '₱' + (val / 1e9).toFixed(2) + 'B';
          if (val >= 1e6) return '₱' + (val / 1e6).toFixed(2) + 'M';
          if (val >= 1e3) return '₱' + (val / 1e3).toFixed(2) + 'K';
          return '₱' + val.toFixed(2);
        }
        function formatPeso(v) {
    return '₱' + Number(v || 0).toLocaleString(
        undefined,
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );
}
        function escapeHtml(s) {
          if (!s && s !== 0) return '';
          return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
        }
        function qs(id){ return document.getElementById(id); }
  function _bindExportButton(btn, popupEl) {
    if (!btn) return;

    // Remove previous handlers idempotently
    try { if (btn.__salesExportHandlerBound) btn.removeEventListener('click', btn.__salesExportHandler, true); } catch(_) {}
    try { if (btn.__salesPointerGuard) btn.removeEventListener('pointerdown', btn.__salesPointerGuard, true); } catch(_) {}

    // helper that builds the detail object (same shape as buildExportDetail)
    function _buildDetail(popup) {
      const popupEl = popup || (btn && btn.closest ? btn.closest('.customer-popover') : null) || document.querySelector('.customer-popover');
      if (!popupEl) return { customer: '', popup: null, rows: null, tableHtml: null };
      const customer = popupEl.dataset && popupEl.dataset.customer ? popupEl.dataset.customer : '';
      let rows = null;
      try { if (popupEl.dataset && popupEl.dataset.exportRows) rows = JSON.parse(popupEl.dataset.exportRows); } catch (e) { rows = null; }
      const tableHtml = popupEl.dataset && popupEl.dataset.exportTableHtml ? popupEl.dataset.exportTableHtml : null;
      return { customer, popup: popupEl, rows, tableHtml };
    }

    // central dispatcher helper: create event that bubbles + composed so listeners in many places catch it
    function _dispatchExport(detail) {
      try {
        const ev = new CustomEvent('sales.export.clicked', { detail, bubbles: true, composed: true });
        // dispatch on document + window for maximum compatibility
        try { document.dispatchEvent(ev); } catch (e) { console.warn('dispatch to document failed', e); }
        try { window.dispatchEvent(new CustomEvent('sales.export.clicked', { detail, bubbles: true, composed: true })); } catch (e) { /* noop */ }
      } catch (e) {
        console.warn('export dispatch error', e);
      }
    }

    // main click handler (capture phase)
    btn.__salesExportHandler = function(ev) {
      try {
        // defensive: attempt to find the most relevant popup element
        const detail = _buildDetail(popupEl || (btn.closest && btn.closest('.customer-popover')));
        // small guard against duplicate rapid dispatches
        if (!btn.__salesExportDispatched) {
          btn.__salesExportDispatched = true;
          setTimeout(()=>{ btn.__salesExportDispatched = false; }, 50);
          // DEBUG: remove this console.log after validating
          // console.debug('sales: export click -> dispatching', detail);
          _dispatchExport(detail);
        }
      } catch (e) { console.warn('export handler error', e); }
    };

    // attach click (capture=true ensures handler runs early in capture phase)
    try { btn.addEventListener('click', btn.__salesExportHandler, true); } catch(e) { console.warn('addEventListener click failed', e); }



    // mark as bound so future idempotent removals work
    btn.__salesExportHandlerBound = true;
  }


      function fetchWithTimeout(url, options = {}, timeoutMs = 15000) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);

  return fetch(url, {
    ...options,
    signal: controller.signal
  }).finally(() => {
    clearTimeout(timeout);
  });
}
     // ---------- Simple Loader (Production Safe) ----------
function showLoader() {
  const l = qs('loadingOverlay');
  if (!l) return;
  l.classList.add('active');
  l.style.pointerEvents = 'auto';
  l.setAttribute('aria-hidden', 'false');
}

function hideLoader() {
  const l = qs('loadingOverlay');
  if (!l) return;
  l.classList.remove('active');
  l.style.pointerEvents = 'none';
  l.setAttribute('aria-hidden', 'true');
}

        // ---------- DOM refs ----------
        const miniCanvas = qs('yearlySalesMiniChart');
        const clusterCanvas = qs('monthlyClusterChart');
        const topCustomersBody = qs('topCustomersBody');
        const filterYear = qs('filterYear');
        const filterMonth = qs('filterMonth');
        const applyBtn = qs('applyFilters');
        const resetBtn = qs('resetFilters');
        const latestRefresh = qs('latestRefresh');

        const salesPerMonthValueEl = qs('salesPerMonthValue');
        if (miniCanvas) { miniCanvas.style.pointerEvents = 'auto'; miniCanvas.style.cursor = 'default'; }
        if (clusterCanvas) { clusterCanvas.style.pointerEvents = 'auto'; clusterCanvas.style.cursor = 'pointer'; }
        if (salesPerMonthValueEl) {
          const card = salesPerMonthValueEl.closest && salesPerMonthValueEl.closest('.card') ? salesPerMonthValueEl.closest('.card') : null;
          if (card) card.style.cursor = 'pointer';
          salesPerMonthValueEl.style.cursor = 'pointer';
          const labelEl = qs('salesMonthLabel');
          if (labelEl) labelEl.style.cursor = 'pointer';
        }

        // ---------- Charts ----------
        let miniChart = null, clusterChart = null, clusterBaseGradient = null;

        function createMiniChart(salesByYear) {
          if (!miniCanvas) return;
          if (typeof Chart === 'undefined') return;
          try {
            const ctx = miniCanvas.getContext('2d');
            const arr = Array.isArray(salesByYear) ? salesByYear : (salesByYear?.data || []);
            const labels = (arr || []).map(x => String(x.TrnYear ?? ''));
            const values = (arr || []).map(x => Number(x.YearlySales || 0));

            let finalLabels = labels.slice();
            let finalValues = values.slice();
            if (finalValues.length === 1) {
              const y = Number(arr[0].TrnYear || 0);
              if (y) {
                finalLabels = [String(y - 1), String(y), String(y + 1)];
                finalValues = [0, values[0], 0];
              } else {
                finalLabels = ['','', labels[0] || ''];
                finalValues = [0, 0, values[0]];
              }
            }

            if (miniChart) {
              miniChart.data.labels = finalLabels;
              miniChart.data.datasets[0].data = finalValues;
              miniChart.update('none');
              return;
            }

            miniChart = new Chart(ctx, {
              type: 'line',
              data: {
                labels: finalLabels,
                datasets: [{
                  data: finalValues,
                  borderColor: '#0d6efd',
                  backgroundColor: 'rgba(13,110,253,0.08)',
                  tension: 0.35,
                  pointRadius: 3,
                  fill: true
                }]
              },
              options: {
                plugins:{ legend:{ display:false } },
                maintainAspectRatio:false,
                animation:{ duration:200 },
                scales: { x: { display: false }, y: { display: false } },
                elements: { point: { hoverRadius: 4 } }
              }
            });
          } catch (e) {
            console.warn('createMiniChart error', e);
          }
        }

        function createClusterChart(salesByMonth) {
          if (!clusterCanvas) return;
          if (typeof Chart === 'undefined') return;
          try {
            const ctx = clusterCanvas.getContext('2d');
            const arr = Array.isArray(salesByMonth) ? salesByMonth : (salesByMonth?.data || []);
            if (!arr || !arr.length) {
              if (clusterChart) {
                clusterChart.data.labels = [];
                clusterChart.data.datasets[0].data = [];
                clusterChart.update('none');
              }
              return;
            }

            let normalizedArr = (arr || []).slice();
            if (normalizedArr.length === 1) {
              const single = normalizedArr[0];
              const m = Number(single.TrnMonth) || 0;
              const left = { TrnMonth: m - 1, MonthlySales: 0 };
              const right = { TrnMonth: m + 1, MonthlySales: 0 };
              const padded = [];
              if (left.TrnMonth >= 1) padded.push(left);
              padded.push(single);
              if (right.TrnMonth <= 12) padded.push(right);
              normalizedArr = padded;
            }

            const labels = normalizedArr.map(x => String(x.TrnMonth ?? ''));
            const values = normalizedArr.map(x => Number(x.MonthlySales || 0));

            clusterBaseGradient = ctx.createLinearGradient(0,0,0,150);
            clusterBaseGradient.addColorStop(0,'rgba(13,110,253,0.92)');
            clusterBaseGradient.addColorStop(1,'rgba(13,110,253,0.22)');

            if (clusterChart) {
              clusterChart.data.labels = labels;
              clusterChart.data.datasets[0].data = values;
              clusterChart.data.datasets[0].backgroundColor = labels.map(()=>clusterBaseGradient);
              clusterChart.update('none');
              return;
            }

            clusterChart = new Chart(ctx, {
              type: 'bar',
              data: {
                labels,
                datasets: [{
                  data: values,
                  backgroundColor: labels.map(()=>clusterBaseGradient),
                  borderRadius: 8,
                  borderSkipped: false,
                  barThickness: 'flex',
                  maxBarThickness: 38
                }]
              },
              options: {
                maintainAspectRatio:false,
                plugins:{
                  legend:{ display:false },
                  tooltip:{ callbacks:{ label: ctxItem => '₱' + Number(ctxItem.parsed.y).toLocaleString(undefined,{minimumFractionDigits:2}) } }
                },
                scales:{
                  x:{ grid:{ display:false }, ticks:{ font:{ size:10 } } },
                  y:{ beginAtZero:true, ticks:{ callback:v => '₱' + Number(v).toLocaleString(), font:{size:9}}, grid:{color:'rgba(0,0,0,0.05)'} }
                },
                onClick: (e, elements) => {
                  try {
                    if (!elements.length) {
                      if (filterMonth) filterMonth.value = '';
                      const total = values.reduce((a,b)=>a+b,0);
                    
                      clusterChart.data.datasets[0].backgroundColor = labels.map(()=>clusterBaseGradient);
                      clusterChart.update('none');
              
                      return;
                    }
                    const idx = elements[0].index;
                    const clickedMonthLabel = labels[idx];
                    if (filterMonth) filterMonth.value = String(clickedMonthLabel);
                    clusterChart.data.datasets[0].backgroundColor = labels.map((m,i)=> i===idx ? 'rgba(0,123,255,1)' : 'rgba(13,110,253,0.28)');
                    clusterChart.update('none');
                    if (qs('salesMonthLabel')) qs('salesMonthLabel').textContent = `Month: ${clickedMonthLabel}`;
                    
          
                  } catch (err) {
                    console.warn('cluster onClick error', err);
                  }
                }
              }
            });
          } catch (e) {
            console.warn('createClusterChart error', e);
          }
        }

        // ---------- cache for customer details ----------
        const customerCache = new Map();

        // ---------- Render Top Customers ----------
        function renderTopCustomers(list) {
          if (!topCustomersBody) return;
          topCustomersBody.innerHTML = '';
          (list || []).forEach((c,i) => {
            const tr = document.createElement('tr');
            tr.className = 'top-customer-row';
            const name = c['Customer Name'] ?? c.CustomerName ?? '';
            tr.dataset.customer = name;
            tr.innerHTML = `<td>${i+1}</td><td class="text-start">${escapeHtml(name)}</td><td class="text-end">₱${Number(c.TotalAmount||0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>`;
            topCustomersBody.appendChild(tr);
          });
        }

        // ---------- POPOVER (robust handler) ----------
        const POP = (function () {

          let popover = null;
          let popBackdrop = null;

function setPopoverState(mode, meta = {}) {
  if (!popover) return;

  popover.dataset.mode = mode;

  // Clear navigation metadata only
  delete popover.dataset.agentName;
  delete popover.dataset.productPage;
  delete popover.dataset.item;

  Object.keys(meta).forEach(k => {
    popover.dataset[k] = meta[k];
  });
}

function setExportToken(token) {
  if (!popover) return;
  popover.dataset.exportToken = token || '';
}

function getPopoverState() {
  if (!popover) return {};
  return {
    mode: popover.dataset.mode || null,
    ...popover.dataset
  };
}  

          const BACKDROP_CLASS = 'popover-backdrop';
          const POPOVER_CLASS = 'customer-popover';
          const BODY_POP_CLASS = 'popover-open-sales';
          const BACKDROP_Z = 3000;
          const POPOVER_Z = 3010;

    
          let popPreviouslyFocused = null;
        
  // ---------- Export detail builder (shared) ----------
  function buildExportDetail(popupEl) {
    const popup = popupEl || document.querySelector('.customer-popover');
    if (!popup) return { customer: '', popup: null, rows: null, tableHtml: null };

    // customer token (single name or 'ALL' for listing)
    const customer = popup.dataset && popup.dataset.exportToken
  ? popup.dataset.exportToken
  : '';

  let rows = window.__salesExportBuffer || null;

    // optional table HTML snapshot (if the code saved it)
    const tableHtml = popup.dataset && popup.dataset.exportTableHtml ? popup.dataset.exportTableHtml : null;

    return { customer, popup, rows, tableHtml };
  }

    

  function rebindPopoverHandlers(pop) {
    if (!pop) return;

    // --- close button handler (store ref so removeEventListener works) ---
    const closeBtn = pop.querySelector('.cp-close');
    if (closeBtn) {
      if (closeBtn.__salesCloseHandler) {
        try { closeBtn.removeEventListener('click', closeBtn.__salesCloseHandler, true); } catch(_) {}
      }
      closeBtn.__salesCloseHandler = function (ev) { window.__SALES_POP.hideCustomerPopover(); };
      closeBtn.addEventListener('click', closeBtn.__salesCloseHandler, true);
    }
  const expBtn = pop.querySelector('.cp-export');
  if (expBtn) _bindExportButton(expBtn, pop);


    
    // --- row click handlers: use delegation instead of individual handlers ---
    if (!pop.__rowDelegationBound) {
      pop.addEventListener('click', function(e) {
        const row = e.target.closest('.top-customer-row');
        if (row && row.dataset.customer) {
          e.stopPropagation();
          openCustomerModal(row.dataset.customer);
        }
      }, true);
      pop.__rowDelegationBound = true;
    }

    // ensure cp-footer visibility
    const footer = pop.querySelector('.cp-footer');
    if (footer) footer.style.display = footer.innerHTML.trim() ? 'flex' : 'none';
  const exportBtn = pop.querySelector('.cp-export');
  if (exportBtn) {
    const cust = pop.dataset.customer || '';
    exportBtn.disabled = (cust === 'PRODUCTS');
    exportBtn.title = exportBtn.disabled
      ? 'Export available in customer view only'
      : 'Export CSV';
  }
    // normalize DOM (backdrop/z-index) to ensure consistent state
  
  }


    // cleanup helper for popovers that are being removed from the DOM
    function _cleanupRemovedPopover(p) {
      try {
        // remove ESC handler bound to window (if any)
        if (p && p._escHandler) {
          try { window.removeEventListener('keydown', p._escHandler, true); } catch(_) {}
          try { delete p._escHandler; } catch(_) {}
        }

        // remove stored close/export/row handlers on elements inside this popover
        try {
          const cb = p.querySelector && p.querySelector('.cp-close');
          if (cb && cb.__salesCloseHandler) {
            try { cb.removeEventListener('click', cb.__salesCloseHandler, true); } catch(_) {}
            try { delete cb.__salesCloseHandler; } catch(_) {}
          }
          const eb = p.querySelector && p.querySelector('.cp-export');
          if (eb && eb.__salesExportHandler) {
            try { eb.removeEventListener('click', eb.__salesExportHandler, true); } catch(_) {}
            try { delete eb.__salesExportHandler; } catch(_) {}
          }
            
          // remove row handlers
          const rows = (p.querySelectorAll && p.querySelectorAll('.top-customer-row')) || [];
          rows.forEach(r => {
            if (r.__salesRowHandler) {
              try { r.removeEventListener('click', r.__salesRowHandler, true); } catch(_) {}
              try { delete r.__salesRowHandler; } catch(_) {}
            }
          });
        } catch(_) {}
      } catch(_) {}
    }

          function createPopBackdrop() {
            const existing = document.querySelector('.' + BACKDROP_CLASS);
            if (existing) { popBackdrop = existing; return; }
            popBackdrop = document.createElement('div');
            popBackdrop.className = BACKDROP_CLASS;
            popBackdrop.addEventListener('click', () => { window.__SALES_POP.hideCustomerPopover(); }, { passive: true });

            document.body.appendChild(popBackdrop);
          }

function createCustomerPopover() {

  // Always remove old one first
  const old = document.querySelector('.customer-popover');
  if (old) old.remove();

  const oldBackdrop = document.querySelector('.popover-backdrop');
  if (oldBackdrop) oldBackdrop.remove();

    createPopBackdrop();
    popover = document.createElement('div');
    popover.className = POPOVER_CLASS;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-modal', 'true');
    popover.setAttribute('aria-hidden', 'true');
    popover.style.zIndex = String(POPOVER_Z);
    popover.innerHTML = `
      <div class="cp-header">
        <div style="display:flex;align-items:center;gap:12px;">
          <div class="cp-title">Customer Details</div>
          <div class="cp-period" style="font-weight:600;opacity:.95">Period: All</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
          <button class="btn btn-sm btn-light cp-export" title="Export CSV">📤 Export</button>
          <button class="cp-close" aria-label="Close">&times;</button>
        </div>
      </div>
      <div class="cp-body" tabindex="0">
        <div class="cp-loader" style="padding:18px;text-align:center;">
          <div class="spinner-border text-primary" role="status" style="width:1.4rem;height:1.4rem"></div>
        </div>
      </div>
      <div class="cp-footer" style="display:none"></div>
    `;

    document.body.appendChild(popover);

    // Use stored handler refs (so later removeEventListener works)
    const closeBtn = popover.querySelector('.cp-close');
    if (closeBtn) {
      closeBtn.__salesCloseHandler = function () { window.__SALES_POP.hideCustomerPopover(); };
      closeBtn.addEventListener('click', closeBtn.__salesCloseHandler, true);
    }
  const expBtn = popover.querySelector('.cp-export');
  if (expBtn) _bindExportButton(expBtn, popover);



  if (!popover._escHandler) {
    popover._escHandler = (e) => {
      if (e.key === 'Escape' && popover && popover.classList.contains('show')) {
        window.__SALES_POP.hideCustomerPopover();
      }
    };
    window.addEventListener('keydown', popover._escHandler, true);
  }

  rebindPopoverHandlers(popover);

  }
          function showCustomerPopover() {
          
      if (!popover) createCustomerPopover();
      createPopBackdrop();


      popPreviouslyFocused = document.activeElement;
      document.body.classList.add(BODY_POP_CLASS);


      try { popBackdrop.classList.add('show'); } catch(e){}



      popover.setAttribute('aria-hidden','false');
      requestAnimationFrame(() => popover.classList.add('show'));

    setTimeout(() => { try { popover.querySelector('.cp-close')?.focus({preventScroll:true}); } catch(e){} }, 60);
    }
function hideCustomerPopover() {
  
  window.__salesExportBuffer = null;
window.__salesExportTableHtml = null;

  try {
    // Remove body state
    document.body.classList.remove('popover-open-sales');

    // Hide popover safely
    if (popover) {
  try {
    if (popover._escHandler) {
      window.removeEventListener('keydown', popover._escHandler, true);
    }
  } catch (_) {}

  try { popover.remove(); } catch (_) {}
  popover = null;
}

    try {

} catch (_) {}

    // 🔥 HARD REMOVE ALL BACKDROPS (fixes click blocking)
    document.querySelectorAll('.popover-backdrop').forEach(b => {
      try {
        b.classList.remove('show');
        b.style.pointerEvents = 'none';
        b.remove();
      } catch (_) {}
    });

    popBackdrop = null;

    // Restore scroll
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';

    // Restore focus
    if (popPreviouslyFocused && popPreviouslyFocused.focus) {
      try { popPreviouslyFocused.focus({ preventScroll: true }); } catch (_) {}
    }

  } catch (e) {
    console.warn('hideCustomerPopover error', e);
  }
}
      
    
  // ---------- Export: consumer that generates CSV / Excel fallback ----------
  (function installExportConsumer() {
    if (window.__salesExportConsumerInstalled) return;
    window.__salesExportConsumerInstalled = true;

    // Small helper: CSV escape
    function csvEscape(v) {
      if (v === null || v === undefined) return '';
      const s = String(v);
      if (s.indexOf('"') >= 0 || s.indexOf(',') >= 0 || s.indexOf('\n') >= 0 || s.indexOf('\r') >= 0) {
        return '"' + s.replace(/"/g, '""') + '"';
      }
      return s;
    }

function buildCsvFromRows(rows) {

  if (!Array.isArray(rows) || !rows.length) {
    return null;
  }

  const keys = Object.keys(rows[0]);

  const header =
      keys.map(k => csvEscape(k)).join(',');

  const lines = rows.map(row => {

      return keys.map(key => {

          let value = row[key];

          if (
              key.includes('Total')
              && !isNaN(value)
          ) {
              value = Number(value)
                  .toLocaleString(
                      undefined,
                      {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2
                      }
                  );
          }

          return csvEscape(value);

      }).join(',');

  });

  return header + '\n' + lines.join('\n');
}

    // Download helper
    function downloadBlob(content, mime, filename) {
      try {
        const blob = new Blob([content], { type: mime });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(() => {
          try { a.remove(); } catch(_) {}
          URL.revokeObjectURL(url);
        }, 250);
      } catch (e) {
        console.error('sales export download failed', e);
      }
    }

    // Main handler
    function onExportClicked(ev) {
      try {
        const detail = ev && ev.detail ? ev.detail : null;
        if (!detail) return;

        // guard duplicates
        if (onExportClicked.__locked) {
          console.debug('sales.export: duplicate ignored');
          return;
        }
        onExportClicked.__locked = true;
        setTimeout(() => { onExportClicked.__locked = false; }, 200);

        const popup = detail.popup || document.querySelector('.customer-popover');
   const rawToken =
  (popup && popup.dataset && popup.dataset.exportToken) ||
  'export';

const customerToken = String(rawToken)
  .replace(/\s+/g, '_')
  .replace(/[^\w\-]/g, '');
        // Attempt to pick a period label if visible
        let period = '';
        try {
          const periodEl = (popup && popup.querySelector && popup.querySelector('.cp-period')) || document.querySelector('.cp-period');
          if (periodEl && periodEl.textContent) period = periodEl.textContent.replace(/[:·]/g,'').trim().replace(/\s+/g,'_');
        } catch(_) {}
        const ts = new Date().toISOString().slice(0,19).replace(/[:T]/g,'-');

        // 1) structured rows -> CSV
        if (Array.isArray(window.__salesExportBuffer) && window.__salesExportBuffer.length) {
          const csv = buildCsvFromRows(window.__salesExportBuffer);
          const filename = `sales_${customerToken}${period ? '_' + period : ''}_${ts}.csv`;
          console.info('sales.export: creating CSV', filename);
          downloadBlob(csv, 'text/csv;charset=utf-8;', filename);
          return;
        }

        // 2) fallback: if tableHtml present -> package as Excel-compatible HTML file
        if (window.__salesExportTableHtml) {
          // Wrap table into a minimal HTML that Excel will accept
          const html = `<!doctype html><html><head><meta charset="utf-8"></head><body>${window.__salesExportTableHtml}</body></html>`;
          const filename = `sales_${customerToken}${period ? '_' + period : ''}_${ts}.xls`;
          console.info('sales.export: creating Excel-compatible file from table HTML', filename);
          downloadBlob(html, 'application/vnd.ms-excel;charset=utf-8;', filename);
          return;
        }

        // 3) nothing to export client-side -> attempt to request server endpoint (if route known)
        // If you have a server endpoint that returns CSV for a customer, try to call it.
        // For convenience, we attempt to find a `data-export-url` attribute on popup
        let exportUrl = popup && popup.dataset && popup.dataset.exportUrl ? popup.dataset.exportUrl : null;
        if (!exportUrl && window.SALES_CONFIG && window.SALES_CONFIG.exportEndpoint) exportUrl = window.SALES_CONFIG.exportEndpoint;
        if (exportUrl) {
          // Build params from popup dataset/customer and filters
          const qp = new URLSearchParams();
          if (detail.customer) qp.append('customer', detail.customer);
          if (filterYear && filterYear.value) qp.append('year', filterYear.value);
          if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);
          const url = exportUrl + (exportUrl.indexOf('?') === -1 ? '?' : '&') + qp.toString();
          console.info('sales.export: fetching server CSV', url);
          fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' } })
            .then(r => {
              if (!r.ok) throw new Error('Network ' + r.status);
              return r.blob();
            })
            .then(blob => {
              const filename = `sales_${customerToken}${period ? '_' + period : ''}_${ts}.csv`;
              const urlObj = URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = urlObj;
              a.download = filename;
              document.body.appendChild(a);
              a.click();
              setTimeout(() => { try{ a.remove(); }catch(_){} URL.revokeObjectURL(urlObj); }, 250);
            })
            .catch(err => { console.error('sales.export: server export failed', err); });
          return;
        }

        // Nothing could be exported
        console.warn('sales.export: no rows/tableHtml/exportUrl found to create export.');
      } catch (e) {
        console.error('sales.export handler error', e);
      }
    }

    // Attach once (idempotent)
    document.addEventListener('sales.export.clicked', onExportClicked, false);
    // Also attach to window for good measure
    try { window.addEventListener('sales.export.clicked', onExportClicked, false); } catch(_) {}

    // small debug helper: expose it for devtools
    window.__salesExport_onExportClicked = onExportClicked;
  })();

  function boot() {
  
        }
          if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
          else boot();

          window.__salesShowCustomerPopover = showCustomerPopover;
          window.__salesHideCustomerPopover = hideCustomerPopover;

   const api = {
  createCustomerPopover,
  createPopBackdrop,
  showCustomerPopover,
  hideCustomerPopover,
  setPopoverState,        // ✅ ADD THIS
  getPopoverState,   
  setExportToken,      // ✅ ADD THIS (optional but correct)
  get popover() { return document.querySelector('.customer-popover'); }
};

  // ✅ expose globally
  window.__SALES_POP = api;

  return api;


        })();



        // ---------- Popover content/rendering ----------
        function renderDetailsInPopover(details, customerName) {
          if (!window.__SALES_POP.popover) window.__SALES_POP.createCustomerPopover();
        const pop = window.__SALES_POP.popover;
  if (!pop) return;
  // ensure dataset.customer reflects who we're showing (used by export)
 window.__SALES_POP.setExportToken(customerName);
  // clear any previous listing export rows (we are showing single-customer details)
 

          const body = pop.querySelector('.cp-body');
          const periodEl = pop.querySelector('.cp-period');
          const y = (filterYear && filterYear.value) || 'All Years';
          const m = (filterMonth && filterMonth.value) || 'All Months';
          if (periodEl) periodEl.textContent = `Period: ${y} · ${m}`;

          if (!Array.isArray(details) || !details.length) {
            body.innerHTML = `<div class="text-center text-muted py-3">No records found for this customer.</div>`;
            const footer = pop.querySelector('.cp-footer'); if (footer) footer.style.display = 'none';
            return;
          }

          let totalQty = 0, totalAmount = 0;
          const skuSet = new Set();
          details.forEach(r => {
            totalQty += Number(r.Qty || 0);
            totalAmount += Number(r.Amount || 0);
            if (r.ItemCode) skuSet.add(String(r.ItemCode));
          });

          const existingSummary = pop.querySelector('.cp-summary');
          const summaryHTML = `
            <div class="cp-summary" role="region" aria-label="Summary" style="padding:12px 22px;display:flex;gap:12px;align-items:center;">
              <div class="pill"><span class="label">Distinct SKUs</span>&nbsp;<span class="value">${skuSet.size.toLocaleString()}</span></div>
              <div class="pill"><span class="label">Total Qty</span>&nbsp;<span class="value">${totalQty.toLocaleString()}</span></div>
              <div class="pill"><span class="label">Total</span>&nbsp;<span class="value">${formatPesoShort(totalAmount)}</span></div>
            </div>
          `;
          if (existingSummary) existingSummary.outerHTML = summaryHTML;
          else {
            const headerEl = pop.querySelector('.cp-header');
            if (headerEl) headerEl.insertAdjacentHTML('afterend', summaryHTML);
            else pop.insertAdjacentHTML('afterbegin', summaryHTML);
          }

          const table = document.createElement('table');
          table.className = 'table table-sm mb-0';
          const thead = document.createElement('thead'); thead.className = 'table-light text-center';
          const trHead = document.createElement('tr');
        ['Invoice','Item Code','Item Description','Class','Qty','Amount (₱)'].forEach(h => {
            const th = document.createElement('th'); th.textContent = h; trHead.appendChild(th);
          });
          thead.appendChild(trHead);
          table.appendChild(thead);

          const tbody = document.createElement('tbody');
          details.forEach(d => {
            const tr = document.createElement('tr');
          tr.innerHTML =
    `<td>${escapeHtml(d.Invoice||'')}</td>
    <td>${escapeHtml(d.ItemCode||'')}</td>
    <td class="text-start">${escapeHtml(d.ItemDescription||'')}</td>
    <td>${escapeHtml(d.Class||'')}</td>
    <td class="text-end">${Number(d.Qty||0).toLocaleString()}</td>
    <td class="text-end">₱${Number(d.Amount||0).toLocaleString(undefined,{minimumFractionDigits:2})}</td>`;

            tbody.appendChild(tr);
          });
          table.appendChild(tbody);
          body.innerHTML = '';
          body.appendChild(table);
                  // ---- make current customer details exportable (CSV + Excel fallback) ----
          try {
            // structured rows: map each detail row to a compact shape
        const exportRows = details.map(d => ({
    Invoice: d.Invoice ?? '',
    ItemCode: d.ItemCode ?? '',
    ItemDescription: d.ItemDescription ?? '',
    Class: d.Class ?? '',
    Qty: Number(d.Qty || 0),
    Amount: Number(d.Amount || 0),
    Category: d.Category ?? '',
    Brand: d.Brand ?? '',
    SubCategory: d.SubCategory ?? ''
  }));
            window.__salesExportBuffer = exportRows;
  window.__salesExportTableHtml = table.outerHTML;
          } catch (e) {
    
          }

          const footer = pop.querySelector('.cp-footer');
          if (footer) {
            footer.innerHTML = `<div><strong>Total</strong></div><div class="total-amount">${formatPesoShort(totalAmount)}</div>`;
            footer.style.display = 'flex';
          }
          try {  } catch (_) {}
        }

      async function openCustomerModal(customerName) {
    if (!customerName) return;
    try {
  if (!window.__SALES_POP.popover) window.__SALES_POP.createCustomerPopover();
  const pop = window.__SALES_POP.popover;
  if (!pop) return;
 
 
  const exportBtn = pop.querySelector('.cp-export');
  if (exportBtn) {
    exportBtn.disabled = false;
    exportBtn.title = 'Export CSV';
    exportBtn.style.pointerEvents = 'auto';
  }


  // set dataset.customer for this open (so Export has the customer immediately)
 window.__SALES_POP.setExportToken(customerName);
  // clear any previous listing export rows (we are showing single-customer details)


// Ensure a back button exists in header while viewing details
try {

  let backBtn = pop.querySelector('.cp-back');

  if (!backBtn) {
    backBtn = document.createElement('button');
    backBtn.type = 'button';
    backBtn.className = 'cp-back btn btn-sm btn-light';
    backBtn.title = 'Back';
    backBtn.style.marginRight = '12px';
    backBtn.textContent = '← Back';

    const headerLeft = pop.querySelector('.cp-header > div:first-child');
    if (headerLeft && headerLeft.parentNode) {
      headerLeft.parentNode.insertBefore(backBtn, headerLeft);
    } else {
      const headerEl = pop.querySelector('.cp-header');
      if (headerEl) headerEl.insertBefore(backBtn, headerEl.firstChild);
    }
  }

  if (backBtn.__salesBackHandler) {
    backBtn.removeEventListener('click', backBtn.__salesBackHandler, true);
  }

  backBtn.__salesBackHandler = function () {

    const mode = pop.dataset.mode;

switch (mode) {

  case 'agent':
    openAgentCustomers(pop.dataset.agentName);
    break;

  case 'agents':
    openAgentDrilldown();
    break;

  // 🔥 THIS IS THE IMPORTANT FIX
  case 'division':
    openDivisionDrilldown();
    break;

  case 'divisions':
    // Level 1 — close popover
    window.__SALES_POP.hideCustomerPopover();
    break;

  case 'customer':
    openAllCustomersDrilldown();
    break;

  case 'products':
    openProductDrilldown(parseInt(pop.dataset.productPage || '1', 10));
    break;

  default:
    openAllCustomersDrilldown();
}
  };

  backBtn.addEventListener('click', backBtn.__salesBackHandler, true);

} catch (e) {
  console.warn('Back button injection error', e);
}

      window.__SALES_POP.showCustomerPopover();

      const cacheKey = `${customerName}||${(filterYear&&filterYear.value)||''}||${(filterMonth&&filterMonth.value)||''}`;
      if (customerCache.has(cacheKey)) {
        renderDetailsInPopover(customerCache.get(cacheKey), customerName);
        return;
      }

      const body = pop.querySelector('.cp-body');
      if (body) body.innerHTML = `<div class="cp-loader" style="padding:18px;text-align:center;"><div class="spinner-border text-primary" role="status" style="width:1.4rem;height:1.4rem"></div></div>`;

      showLoader();
      const qp = new URLSearchParams({ customer: customerName });
      if (filterYear && filterYear.value) qp.append('year', filterYear.value);
      if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);

      if (!ROUTE_CUSTOMER) throw new Error('Customer details route not configured');
      let resp;
      try {
        resp = await fetchWithTimeout(`${ROUTE_CUSTOMER}?${qp.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }, 15000);
    
      } catch (err) {

    // 🔥 Ignore aborted requests (timeout / rapid click)
    if (err && err.name === 'AbortError') {
      return;
    }

    console.error('openCustomerModal fetch error', err);

    const pop = window.__SALES_POP.popover || document.querySelector('.customer-popover');
    if (pop) {
      const body = pop.querySelector('.cp-body');
      if (body) {
        body.innerHTML = `<div class="text-danger p-3">Unable to load details.</div>`;
      }
    }
  }


   if (!resp || !resp.ok) {
  const pop = window.__SALES_POP.popover || document.querySelector('.customer-popover');
  if (pop) {
    const body = pop.querySelector('.cp-body');
    if (body) {
      body.innerHTML = `<div class="text-danger p-3">Unable to load details.</div>`;
    }
  }
  return;
}
      const payload = await resp.json();
      const details = payload.details || [];
      try { customerCache.set(cacheKey, details); } catch(e){}
      renderDetailsInPopover(details, customerName);
    } catch (err) {
      console.error('openCustomerPopover error', err);
      const pop = window.__SALES_POP.popover || document.querySelector('.customer-popover');
      if (pop) {
        const body = pop.querySelector('.cp-body');
        if (body) body.innerHTML = `<div class="text-danger p-3">Error loading details.</div>`;
      }
    } finally {
      hideLoader();
    }
      }

  // =====================================================
  // CUSTOMER DRILLDOWN — ALL CUSTOMERS (REAL SQL)
  // =====================================================
 async function openAllCustomersDrilldown() {

  // Always create a fresh popover
  window.__SALES_POP.createCustomerPopover();
  const pop = window.__SALES_POP.popover;
  if (!pop) return;

  try {
    showLoader();

    // Remove back button if exists
    const oldBack = pop.querySelector('.cp-back');
    if (oldBack) oldBack.remove();

    window.__salesExportBuffer = null;
    window.__salesExportTableHtml = null;

    const exportBtn = pop.querySelector('.cp-export');
    if (exportBtn) {
      exportBtn.disabled = false;
      exportBtn.title = 'Export CSV';
    }

    if (!ROUTE_REALTIME) {
      console.warn('Customer drilldown: ROUTE_REALTIME missing');
      return;
    }

    const qp = new URLSearchParams({ getAllCustomers: '1' });
    if (filterYear && filterYear.value) qp.append('year', filterYear.value);
    if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);

    const url = `${ROUTE_REALTIME}?${qp.toString()}`;

    const resp = await fetchWithTimeout(
      url,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
      15000
    );

    if (!resp || !resp.ok) {
      throw new Error('Network error');
    }

    const payload = await resp.json();
    let list = payload.allCustomers ?? payload.topCustomers ?? [];
    if (!Array.isArray(list)) list = [];

window.__SALES_POP.setPopoverState('customer');

const y = filterYear?.value || 'All Years';
const m = filterMonth?.value || 'All Months';

pop.querySelector('.cp-title').textContent = 'Customers';

const periodEl = pop.querySelector('.cp-period');

if (periodEl) {
    periodEl.textContent = `Period: ${y} · ${m}`;
}

    const body = pop.querySelector('.cp-body');
    body.innerHTML = '';

    if (!list.length) {
      body.innerHTML = `<div class="text-center text-muted py-3">No customers found.</div>`;
      return;
    }

    const table = document.createElement('table');
    table.className = 'table table-sm mb-0';
    table.innerHTML = `
      <thead class="table-light text-center">
        <tr>
          <th>#</th>
          <th class="text-start">Customer</th>
          <th class="text-end">Total (₱)</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;

    const tb = table.querySelector('tbody');

    list.forEach((r, i) => {
      const name = r['Customer Name'] ?? r.CustomerName ?? '';
      const total = Number(r.TotalAmount ?? 0);

      const tr = document.createElement('tr');
      tr.className = 'top-customer-row';
      tr.dataset.customer = name;
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td class="text-start">${escapeHtml(name)}</td>
        <td class="text-end">₱${total.toLocaleString(undefined,{minimumFractionDigits:2})}</td>
      `;
      tb.appendChild(tr);
    });

 body.appendChild(table);

window.__SALES_POP.setExportToken('ALL');

const exportRows = list.map((r, i) => ({
  '#': i + 1,
  Customer: r['Customer Name'] ?? r.CustomerName ?? '',
  'Total (PHP)': Number(r.TotalAmount ?? 0)
}));

const grandTotal = list.reduce(
  (sum, r) => sum + Number(r.TotalAmount ?? 0),
  0
);

/* Footer Display */
const footer = pop.querySelector('.cp-footer');

if (footer) {
  footer.innerHTML = `
    <div><strong>Grand Total</strong></div>
    <div class="total-amount">
      ₱${grandTotal.toLocaleString(undefined,{
        minimumFractionDigits:2,
        maximumFractionDigits:2
      })}
    </div>
  `;
  footer.style.display = 'flex';
}

exportRows.push({
  '#': '',
  Customer: 'GRAND TOTAL',
  'Total (PHP)': grandTotal
});

window.__salesExportBuffer = exportRows;

    window.__salesExportTableHtml = table.outerHTML;

    window.__SALES_POP.showCustomerPopover();

  } catch (err) {

    if (err && err.name === 'AbortError') return;

    console.error('Customer drilldown error', err);

  } finally {

    hideLoader();

  }
}

  async function openProductDrilldown(page = 1) {
  if (!window.__SALES_POP.popover) window.__SALES_POP.createCustomerPopover();
    const pop = window.__SALES_POP.popover;

    try {
  const oldBack = pop.querySelector('.cp-back');
  if (oldBack) oldBack.remove();
} catch (_) {}

    window.__salesExportBuffer = null;
    window.__salesExportTableHtml = null;

  const body = pop.querySelector('.cp-body');
  if (!body) return;

  // 🔴 CLEAN OLD PRODUCT ROW HANDLERS (PLACE IT HERE)
  const oldProductRows = body.querySelectorAll('.product-row');
  oldProductRows.forEach(r => {
    if (r.__salesProductHandler) {
      r.removeEventListener('click', r.__salesProductHandler);
      delete r.__salesProductHandler;
    }
  });







    try {
      showLoader();

      const qp = new URLSearchParams({
    getProducts: '1',
    page: page
  });
      if (filterYear && filterYear.value) qp.append('year', filterYear.value);
      if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);

    const resp = await fetchWithTimeout(
    `${ROUTE_REALTIME}?${qp.toString()}`,
    { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
    15000
  );


    if (!resp || !resp.ok) {
    throw new Error('Network error');
  }

      const payload = await resp.json();
      const products = payload.products || [];
      const pagination = payload.pagination || {
    current_page: 1,
    last_page: 1,
    total: products.length,
    per_page: products.length
  };

  if (!window.__SALES_POP.popover) window.__SALES_POP.createCustomerPopover();


      pop.querySelector('.cp-title').textContent = 'Top Products';
      const periodEl = pop.querySelector('.cp-period');

if (periodEl) {
    periodEl.textContent =
        `Period: ${
            filterYear?.value || 'All Years'
        } · ${
            filterMonth?.value || 'All Months'
        }`;
}
      window.__SALES_POP.setPopoverState('products', {
  productPage: pagination.current_page
});
      // clear any previous customer export context

const exportBtn = pop.querySelector('.cp-export');

if (exportBtn) {
  exportBtn.disabled = false;
  exportBtn.title = 'Export CSV';
  exportBtn.style.pointerEvents = 'auto';
}

      body.innerHTML = `
  <!-- Product Controls -->
  <div class="d-flex justify-content-end align-items-center mb-3">
    <div id="productPaginationInfo" class="small text-muted"></div>
  </div>

    <!-- TABLE CONTAINER -->
    <div id="productTableWrap"></div>

    <!-- PAGINATION BUTTONS -->
    <div class="d-flex justify-content-between align-items-center mt-3">
      <button class="btn btn-sm btn-outline-primary" id="productPrevBtn">◀ Previous</button>
      <button class="btn btn-sm btn-outline-primary" id="productNextBtn">Next ▶</button>
    </div>
  `;

if (!products.length) {
  body.innerHTML = `<div class="text-center text-muted py-3">No product data found.</div>`;
  return;
}
      const tableWrap = body.querySelector('#productTableWrap');
      tableWrap.innerHTML = '';
      const table = document.createElement('table');
      table.className = 'table table-sm mb-0';
      table.innerHTML = `
        <thead class="table-light text-center">
          <tr>
            <th>#</th>
            <th class="text-start">Item Description</th>
            <th class="text-end">Total Sales (₱)</th>
          </tr>
        </thead>
        <tbody></tbody>
      `;

      const tb = table.querySelector('tbody');

      products.forEach((p, i) => {
        const tr = document.createElement('tr');
        tr.className = 'product-row';
        tr.dataset.item = p.ItemDescription;
  if (tr.__salesProductHandler) {
    tr.removeEventListener('click', tr.__salesProductHandler);
  }

  tr.__salesProductHandler = () => {
    openProductInvoiceDetails(p.ItemDescription);
  };

  tr.addEventListener('click', tr.__salesProductHandler);

        tr.innerHTML = `
          <td>${((pagination.current_page - 1) * pagination.per_page) + i + 1}</td>
          <td class="text-start">${escapeHtml(p.ItemDescription)}</td>
          <td class="text-end">₱${Number(p.TotalAmount).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
        `;
        tb.appendChild(tr);
      });

    tableWrap.appendChild(table);


const exportRows = products.map((p, i) => ({
  '#': ((pagination.current_page - 1) * pagination.per_page) + i + 1,
  'Item Description': p.ItemDescription ?? '',
  'Total Sales (PHP)': Number(p.TotalAmount ?? 0)
}));

// ✅ FIXED: Use server-calculated grand total instead of page-only sum
const grandTotal = payload.grandTotal || products.reduce(
  (sum, p) => sum + Number(p.TotalAmount ?? 0),
  0
);

exportRows.push({
  '#': '',
  'Item Description': 'GRAND TOTAL',
  'Total Sales (PHP)': grandTotal
});

window.__salesExportBuffer = exportRows;
window.__salesExportTableHtml = table.outerHTML;
window.__SALES_POP.setExportToken('PRODUCTS');

const footer = pop.querySelector('.cp-footer');

if (footer) {
  footer.innerHTML = `
    <div><strong>Grand Total</strong></div>
    <div class="total-amount">
      ₱${grandTotal.toLocaleString(undefined,{
        minimumFractionDigits:2,
        maximumFractionDigits:2
      })}
    </div>
  `;
  footer.style.display = 'flex';
}
  /* ===========================
    PRODUCT PAGINATION CONTROLS
    =========================== */

  const pageInfo = body.querySelector('#productPaginationInfo');
  if (pageInfo) {
    pageInfo.textContent =
      `Page ${pagination.current_page} of ${pagination.last_page} — ${pagination.total.toLocaleString()} items`;
  }

  const prevBtn = body.querySelector('#productPrevBtn');
  const nextBtn = body.querySelector('#productNextBtn');

  if (prevBtn && nextBtn) {
    prevBtn.disabled = pagination.current_page <= 1;
    nextBtn.disabled = pagination.current_page >= pagination.last_page;



// Replace handler safely without cloning DOM
prevBtn.onclick = null;
nextBtn.onclick = null;

prevBtn.onclick = () => {
  if (pagination.current_page > 1) {
    openProductDrilldown(pagination.current_page - 1);
  }
};

nextBtn.onclick = () => {
  if (pagination.current_page < pagination.last_page) {
    openProductDrilldown(pagination.current_page + 1);
  }
};

  }

  window.__SALES_POP.showCustomerPopover();

  } catch (err) {

    if (err && err.name === 'AbortError') {
      return;
    }

    console.error('Product drilldown error', err);
  } finally {
      hideLoader();
    }
  }

  async function openProductInvoiceDetails(item) {
  if (!window.__SALES_POP.popover) window.__SALES_POP.createCustomerPopover();
  const pop = window.__SALES_POP.popover || document.querySelector('.customer-popover');
  if (!pop) return;

    const body = pop.querySelector('.cp-body');
    window.__salesExportBuffer = null;
  window.__salesExportTableHtml = null;
 
    try {

      showLoader();

      const qp = new URLSearchParams({ item });
      if (filterYear && filterYear.value) qp.append('year', filterYear.value);
      if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);
      
      if (!window.SALES_CONFIG.productDetails) {
    console.warn('Product details route not configured.');
    hideLoader();
    return;
  }

      const url = `${window.SALES_CONFIG.productDetails}?${qp.toString()}`;

      const resp = await fetchWithTimeout(
    url,
    { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
    15000
  );

      if (!resp || !resp.ok) {
    throw new Error('Network error');
  }

      const payload = await resp.json();
      const details = payload.details || [];

  if (!window.__SALES_POP.popover) window.__SALES_POP.createCustomerPopover();
  

      pop.querySelector('.cp-title').textContent =
    `Product: ${item}`;
    
      

    window.__SALES_POP.setPopoverState('product-details', {
  item,
  productPage: pop.dataset.productPage // preserve pagination
});
      // ---------- Inject Back Button (Product Flow) ----------
  try {
    let backBtn = pop.querySelector('.cp-back');

    if (!backBtn) {
      backBtn = document.createElement('button');
      backBtn.type = 'button';
      backBtn.className = 'cp-back btn btn-sm btn-light';
      backBtn.title = 'Back';
      backBtn.style.marginRight = '12px';
      backBtn.textContent = '← Back';

      const headerLeft = pop.querySelector('.cp-header > div:first-child');
      if (headerLeft && headerLeft.parentNode) {
        headerLeft.parentNode.insertBefore(backBtn, headerLeft);
      }
    }

    if (backBtn.__salesBackHandler) {
      backBtn.removeEventListener('click', backBtn.__salesBackHandler, true);
    }

const page = parseInt(pop.dataset.productPage || '1', 10);

backBtn.__salesBackHandler = function () {
  openProductDrilldown(page);
};

    backBtn.addEventListener('click', backBtn.__salesBackHandler, true);

  } catch (e) {
    console.warn('Back button injection failed', e);
  }

      renderDetailsInPopover(details, '');

      const exportBtn = pop.querySelector('.cp-export');
  if (exportBtn) {
    exportBtn.disabled = true;
    exportBtn.title = 'Export available in customer view only';
  }

    } catch (err) {
  if (err && err.name === 'AbortError') return;
  console.error(err);
} finally {
      hideLoader();
    }
  }

  async function openAgentDrilldown() {

  window.__SALES_POP.createCustomerPopover();
  const pop = window.__SALES_POP.popover;
  if (!pop) return;

  try {
    showLoader();

    const qp = new URLSearchParams({ getAgents: '1' });
    if (filterYear && filterYear.value) qp.append('year', filterYear.value);
    if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);

    const resp = await fetchWithTimeout(
      `${ROUTE_REALTIME}?${qp.toString()}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
      15000
    );

    if (!resp || !resp.ok) throw new Error('Network error');

    const payload = await resp.json();
    const agents = payload.agents || [];

    pop.querySelector('.cp-title').textContent = 'Sales Agents';
    const periodEl = pop.querySelector('.cp-period');

if (periodEl) {
    periodEl.textContent =
        `Period: ${
            filterYear?.value || 'All Years'
        } · ${
            filterMonth?.value || 'All Months'
        }`;
}
    window.__SALES_POP.setPopoverState('agents');
    window.__SALES_POP.setExportToken('AGENTS');

    const body = pop.querySelector('.cp-body');
    body.innerHTML = '';



    if (!agents.length) {
      body.innerHTML = `<div class="text-center text-muted py-3">No agents found.</div>`;
      return;
    }

    const table = document.createElement('table');
    table.className = 'table table-sm mb-0';
    table.innerHTML = `
      <thead class="table-light text-center">
        <tr>
          <th>#</th>
          <th class="text-start">Agent</th>
          <th class="text-end">Total (₱)</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;

    const tb = table.querySelector('tbody');

   agents.forEach((a, i) => {
  const name = a['Agent Name'] ?? a.AgentName ?? 'Unknown';
      const total = Number(a.TotalAmount || 0);

      const tr = document.createElement('tr');
      tr.className = 'agent-row';
      tr.dataset.agent = name;
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td class="text-start">${escapeHtml(name)}</td>
        <td class="text-end">₱${total.toLocaleString(undefined,{minimumFractionDigits:2})}</td>
      `;
      tb.appendChild(tr);
    });

body.appendChild(table);

/* EXPORT DATA */
const exportRows = agents.map((a, i) => ({
    '#': i + 1,
    Agent: a['Agent Name'] ?? a.AgentName ?? '',
    'Total (PHP)': Number(a.TotalAmount ?? 0)
}));

const grandTotal = agents.reduce(
    (sum, a) => sum + Number(a.TotalAmount ?? 0),
    0
);

exportRows.push({
    '#': '',
    Agent: 'GRAND TOTAL',
    'Total (PHP)': grandTotal
});

window.__salesExportBuffer = exportRows;
window.__salesExportTableHtml = table.outerHTML;

/* FOOTER */
const footer = pop.querySelector('.cp-footer');

if (footer) {
    footer.innerHTML = `
        <div><strong>Grand Total</strong></div>
        <div class="total-amount">
            ₱${grandTotal.toLocaleString(undefined,{
                minimumFractionDigits:2,
                maximumFractionDigits:2
            })}
        </div>
    `;
    footer.style.display = 'flex';
}

body.querySelectorAll('.agent-row').forEach(row => {
  row.addEventListener('click', () => {
    openAgentCustomers(row.dataset.agent);
  });
});

window.__SALES_POP.showCustomerPopover();

  } catch (err) {
    if (err && err.name === 'AbortError') return;
    console.error('Agent drilldown error', err);
  } finally {
    hideLoader();
  }
}

// =====================================================
// DIVISION LEVEL 1
// =====================================================
async function openDivisionDrilldown() {

  window.__SALES_POP.createCustomerPopover();
  const pop = window.__SALES_POP.popover;
  if (!pop) return;

   const oldBack = pop.querySelector('.cp-back');
  if (oldBack) {
    try {
      if (oldBack.__salesBackHandler) {
        oldBack.removeEventListener('click', oldBack.__salesBackHandler, true);
      }
      oldBack.remove();
    } catch (_) {}
  }

  try {
    showLoader();

    const qp = new URLSearchParams({ getDivisions: '1' });

    if (filterYear && filterYear.value) qp.append('year', filterYear.value);
    if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);

    const resp = await fetchWithTimeout(
      `${ROUTE_REALTIME}?${qp.toString()}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
      15000
    );

    if (!resp || !resp.ok) throw new Error('Network error');

    const payload = await resp.json();
    const divisions = payload.divisions || [];

    pop.querySelector('.cp-title').textContent = 'Divisions';
    const periodEl = pop.querySelector('.cp-period');

if (periodEl) {
    periodEl.textContent =
        `Period: ${
            filterYear?.value || 'All Years'
        } · ${
            filterMonth?.value || 'All Months'
        }`;
}
    window.__SALES_POP.setPopoverState('divisions');

    const body = pop.querySelector('.cp-body');
    body.innerHTML = '';

    const table = document.createElement('table');
    table.className = 'table table-sm mb-0';
    table.innerHTML = `
      <thead class="table-light text-center">
        <tr>
          <th>#</th>
          <th class="text-start">Division</th>
          <th class="text-end">Total (₱)</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;

    const tb = table.querySelector('tbody');

    divisions.forEach((d, i) => {
      const tr = document.createElement('tr');
      tr.className = 'division-row';
     const divName = d.Division ?? 'Unclassified';
tr.dataset.division = divName;
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td class="text-start">${escapeHtml(divName)}</td>
        <td class="text-end">₱${Number(d.TotalAmount).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
      `;
      tb.appendChild(tr);
    });

    body.appendChild(table);
    const exportRows = divisions.map((d, i) => ({
    '#': i + 1,
    Division: d.Division ?? 'Unclassified',
    'Total (PHP)': Number(d.TotalAmount ?? 0)
}));

const grandTotal = divisions.reduce(
    (sum, d) => sum + Number(d.TotalAmount ?? 0),
    0
);

exportRows.push({
    '#': '',
    Division: 'GRAND TOTAL',
    'Total (PHP)': grandTotal
});

window.__salesExportBuffer = exportRows;
window.__salesExportTableHtml = table.outerHTML;

window.__SALES_POP.setExportToken('DIVISIONS');

const footer = pop.querySelector('.cp-footer');

if (footer) {
    footer.innerHTML = `
        <div><strong>Grand Total</strong></div>
        <div class="total-amount">
            ₱${grandTotal.toLocaleString(undefined,{
                minimumFractionDigits:2,
                maximumFractionDigits:2
            })}
        </div>
    `;
    footer.style.display = 'flex';
}

    body.querySelectorAll('.division-row').forEach(row => {
      row.addEventListener('click', () => {
        openDivisionAgents(row.dataset.division);
      });
    });

    window.__SALES_POP.showCustomerPopover();

 } catch (err) {
  if (err && err.name === 'AbortError') return;
  console.error('Division drilldown error', err);
} finally {
    hideLoader();
  }
}
// =====================================================
// DIVISION LEVEL 2 → AGENTS
// =====================================================
async function openDivisionAgents(divisionName) {

  window.__SALES_POP.createCustomerPopover();
  const pop = window.__SALES_POP.popover;
  if (!pop) return;

  try {
    showLoader();

    const qp = new URLSearchParams({ division: divisionName });

    if (filterYear && filterYear.value) qp.append('year', filterYear.value);
    if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);

    const resp = await fetchWithTimeout(
      `${ROUTE_REALTIME}?${qp.toString()}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
      15000
    );

    if (!resp || !resp.ok) throw new Error('Network error');

    const payload = await resp.json();
    const agents = payload.divisionAgents || [];

    pop.querySelector('.cp-title').textContent = `Division: ${divisionName}`;
    const periodEl = pop.querySelector('.cp-period');

if (periodEl) {
    periodEl.textContent =
        `Period: ${
            filterYear?.value || 'All Years'
        } · ${
            filterMonth?.value || 'All Months'
        }`;
}
    window.__SALES_POP.setPopoverState('division', { divisionName });

    // ---------- Inject Back Button (Division → Agents) ----------
let backBtn = pop.querySelector('.cp-back');

if (!backBtn) {
  backBtn = document.createElement('button');
  backBtn.type = 'button';
  backBtn.className = 'cp-back btn btn-sm btn-light';
  backBtn.textContent = '← Back';
  backBtn.style.marginRight = '12px';

  const headerLeft = pop.querySelector('.cp-header > div:first-child');
  if (headerLeft && headerLeft.parentNode) {
    headerLeft.parentNode.insertBefore(backBtn, headerLeft);
  }
}

// Remove old handler safely
if (backBtn.__salesBackHandler) {
  backBtn.removeEventListener('click', backBtn.__salesBackHandler, true);
}

// 🔥 THIS is the important part
backBtn.__salesBackHandler = function () {
  openDivisionDrilldown();
};

backBtn.addEventListener('click', backBtn.__salesBackHandler, true);

    const body = pop.querySelector('.cp-body');
    body.innerHTML = '';

    const table = document.createElement('table');
    table.className = 'table table-sm mb-0';
    table.innerHTML = `
      <thead class="table-light text-center">
        <tr>
          <th>#</th>
          <th class="text-start">Agent</th>
          <th class="text-end">Total (₱)</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;

    const tb = table.querySelector('tbody');

    agents.forEach((a, i) => {
      const name = a['Agent Name'] ?? '';
      const tr = document.createElement('tr');
      tr.className = 'agent-row';
      tr.dataset.agent = name;
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td class="text-start">${escapeHtml(name)}</td>
        <td class="text-end">₱${Number(a.TotalAmount).toLocaleString(undefined,{minimumFractionDigits:2})}</td>
      `;
      tb.appendChild(tr);
    });

    body.appendChild(table);

    const exportRows = agents.map((a, i) => ({
    '#': i + 1,
    Agent: a['Agent Name'] ?? '',
    'Total (PHP)': Number(a.TotalAmount ?? 0)
}));

const grandTotal = agents.reduce(
    (sum, a) => sum + Number(a.TotalAmount ?? 0),
    0
);

exportRows.push({
    '#': '',
    Agent: 'GRAND TOTAL',
    'Total (PHP)': grandTotal
});

window.__salesExportBuffer = exportRows;
window.__salesExportTableHtml = table.outerHTML;

window.__SALES_POP.setExportToken(
    `DIVISION_${divisionName}`
);

const footer = pop.querySelector('.cp-footer');

if (footer) {
    footer.innerHTML = `
        <div><strong>Grand Total</strong></div>
        <div class="total-amount">
            ₱${grandTotal.toLocaleString(undefined,{
                minimumFractionDigits:2,
                maximumFractionDigits:2
            })}
        </div>
    `;
    footer.style.display = 'flex';
}

    body.querySelectorAll('.agent-row').forEach(row => {
      row.addEventListener('click', () => {
        openAgentCustomers(row.dataset.agent);
      });
    });

    window.__SALES_POP.showCustomerPopover();

 } catch (err) {
  if (err && err.name === 'AbortError') return;
  console.error('Division drilldown error', err);
} finally {
    hideLoader();
  }
}
// =====================================================
// AGENT → CUSTOMER LEVEL
// =====================================================
async function openAgentCustomers(agentName) {

  window.__SALES_POP.createCustomerPopover();
  const pop = window.__SALES_POP.popover;
  if (!pop) return;

  try {
    showLoader();

    const qp = new URLSearchParams({ agent: agentName });
    if (filterYear && filterYear.value) qp.append('year', filterYear.value);
    if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);

    const resp = await fetchWithTimeout(
      `${ROUTE_REALTIME}?${qp.toString()}`,
      { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
      15000
    );

    if (!resp || !resp.ok) throw new Error('Network error');

    const payload = await resp.json();
    const customers = payload.agentCustomers || [];

    pop.querySelector('.cp-title').textContent = `Agent: ${agentName}`;
    const periodEl = pop.querySelector('.cp-period');

if (periodEl) {
    periodEl.textContent =
        `Period: ${
            filterYear?.value || 'All Years'
        } · ${
            filterMonth?.value || 'All Months'
        }`;
}
    window.__SALES_POP.setPopoverState('agent', { agentName });
    // ---------- Inject Back Button (Agent Flow) ----------
let backBtn = pop.querySelector('.cp-back');

if (!backBtn) {
  backBtn = document.createElement('button');
  backBtn.type = 'button';
  backBtn.className = 'cp-back btn btn-sm btn-light';
  backBtn.textContent = '← Back';
  backBtn.style.marginRight = '12px';

  const headerLeft = pop.querySelector('.cp-header > div:first-child');
  if (headerLeft && headerLeft.parentNode) {
    headerLeft.parentNode.insertBefore(backBtn, headerLeft);
  }
}

// Remove old handler safely
if (backBtn.__salesBackHandler) {
  backBtn.removeEventListener('click', backBtn.__salesBackHandler, true);
}

// 🔥 FIXED: go back to AGENT LEVEL 1
backBtn.__salesBackHandler = function () {
  openAgentDrilldown();
};

backBtn.addEventListener('click', backBtn.__salesBackHandler, true);
    window.__SALES_POP.setExportToken(agentName);

    const body = pop.querySelector('.cp-body');
    body.innerHTML = '';


    if (!customers.length) {
      body.innerHTML = `<div class="text-center text-muted py-3">No customers found.</div>`;
      return;
    }

    const table = document.createElement('table');
    table.className = 'table table-sm mb-0';
    table.innerHTML = `
      <thead class="table-light text-center">
        <tr>
          <th>#</th>
          <th class="text-start">Customer</th>
          <th class="text-end">Total (₱)</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;

    const tb = table.querySelector('tbody');

    customers.forEach((c, i) => {
      const name = c.CustomerName || '';
      const total = Number(c.TotalAmount || 0);

      const tr = document.createElement('tr');
      tr.className = 'top-customer-row';
      tr.dataset.customer = name;
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td class="text-start">${escapeHtml(name)}</td>
        <td class="text-end">₱${total.toLocaleString(undefined,{minimumFractionDigits:2})}</td>
      `;
      tb.appendChild(tr);
    });

    body.appendChild(table);
    const exportRows = customers.map((c, i) => ({
    '#': i + 1,
    Customer: c.CustomerName ?? '',
    'Total (PHP)': Number(c.TotalAmount ?? 0)
}));

const grandTotal = customers.reduce(
    (sum, c) => sum + Number(c.TotalAmount ?? 0),
    0
);

exportRows.push({
    '#': '',
    Customer: 'GRAND TOTAL',
    'Total (PHP)': grandTotal
});

window.__salesExportBuffer = exportRows;
window.__salesExportTableHtml = table.outerHTML;

const footer = pop.querySelector('.cp-footer');

if (footer) {
    footer.innerHTML = `
        <div><strong>Grand Total</strong></div>
        <div class="total-amount">
            ₱${grandTotal.toLocaleString(undefined,{
                minimumFractionDigits:2,
                maximumFractionDigits:2
            })}
        </div>
    `;
    footer.style.display = 'flex';
}

    window.__SALES_POP.showCustomerPopover();

  } catch (err) {
    if (err && err.name === 'AbortError') return;
    console.error('Agent customer drilldown error', err);
  } finally {
    hideLoader();
  }
}
function showSalesBreakdownModal(months) {
  const titleEl =
    document.getElementById(
        'salesBreakdownTitle'
    );

const selectedYear =
    filterYear?.value || 'All Years';

const selectedMonth =
    filterMonth?.value || 'All Months';

if (titleEl) {
    titleEl.textContent =
        `Monthly Sales Breakdown • ${selectedYear} • ${selectedMonth}`;
}

    const body = document.getElementById('salesBreakdownBody');
    const totalEl = document.getElementById('salesBreakdownTotal');

    if (!body) return;

    body.innerHTML = '';

    let total = 0;

    const monthNames = {
        1:'January',
        2:'February',
        3:'March',
        4:'April',
        5:'May',
        6:'June',
        7:'July',
        8:'August',
        9:'September',
        10:'October',
        11:'November',
        12:'December'
    };

    for (let m = 1; m <= 12; m++) {

        const row =
            (months || []).find(
                x => Number(x.TrnMonth) === m
            );

        const sales = Number(
            row?.MonthlySales || 0
        );

        total += sales;

        body.insertAdjacentHTML(
            'beforeend',
            `
            <tr>
                <td>${monthNames[m]}</td>
                <td class="text-end">
                    ${formatPeso(sales)}
                </td>
            </tr>
            `
        );
    }

    totalEl.textContent = formatPeso(total);

    const modal =
        new bootstrap.Modal(
            document.getElementById(
                'salesBreakdownModal'
            )
        );

    modal.show();
}
        // ---------- Realtime fetch ----------
        async function fetchRealtime(opts = {}) {
          opts = opts || {};
          try {
            if (!opts.silent) showLoader();
            const qp = new URLSearchParams();
            if (filterYear && filterYear.value) qp.append('year', filterYear.value);
            if (filterMonth && filterMonth.value) qp.append('month', filterMonth.value);
            if (currentDate) qp.append('dateTo', currentDate);

            if (!ROUTE_REALTIME) {
              console.warn('fetchRealtime skipped: ROUTE_REALTIME not configured.');
              return;
            }

            const url = `${ROUTE_REALTIME}?${qp.toString()}`;
            let resp;
    try {
      resp = await fetchWithTimeout(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }, 15000);
    } catch (err) {
      console.error('fetchRealtime network/timeout', err);

      try {
          const el = qs('latestRefresh');
          if (el) {
              const prev = el.textContent;
              el.textContent = 'Refresh failed (network or timeout)';
              setTimeout(() => { if (el) el.textContent = prev; }, 3500);
          }
      } catch (_) {}

      if (!opts.silent) hideLoader();   // 🔥 FORCE CLEAR

      return;
  }

          if (!resp || !resp.ok) {
    throw new Error('Network error');
  }
            const payload = await resp.json();

      const cfgAllTime = cfg.allTimeTotalFormatted || cfg.totalSalesFormatted || null;

    // ---------- FINAL: Total Sales display rule ----------
    // Rule: If a YEAR is selected -> show year total.
    //       Otherwise (no YEAR selected) -> ALWAYS show ALL-TIME.
    // (This prevents month-only selections from changing the Total Sales card.)
    if (filterYear && filterYear.value) {
      // Year explicitly selected -> show year total (prefer server-formatted string)
      if (payload.totalSalesFormatted && qs('totalSalesValue')) {
        qs('totalSalesValue').textContent = payload.totalSalesFormatted;
      } else if (payload.salesByYear && Array.isArray(payload.salesByYear)) {
        const found = payload.salesByYear.find(y => String(y.TrnYear) === String(filterYear.value));
        const v = found ? Number(found.YearlySales || 0) : payload.salesByYear.reduce((a,b)=>a+Number(b.YearlySales||0),0);
        if (qs('totalSalesValue')) qs('totalSalesValue').textContent = formatPesoShort(v);
      }
    } else {
      // No year selected -> always show ALL-TIME (preferred stable behavior)
      if (cfgAllTime && qs('totalSalesValue')) {
        qs('totalSalesValue').textContent = cfgAllTime;
      } else {
        // Defensive fallback: compute from payload if all-time label not provided
        if (payload.salesByYear && Array.isArray(payload.salesByYear)) {
          const sum = payload.salesByYear.reduce((a,b)=>a+Number(b.YearlySales||0),0);
          if (qs('totalSalesValue')) qs('totalSalesValue').textContent = formatPesoShort(sum);
        } else if (payload.salesByMonth && Array.isArray(payload.salesByMonth)) {
          const sum = payload.salesByMonth.reduce((a,b)=>a+Number(b.MonthlySales||0),0);
          if (qs('totalSalesValue')) qs('totalSalesValue').textContent = formatPesoShort(sum);
        }
      }
    }



            if (payload.avgSalesPerCustomerFormatted && qs('avgSalesValue')) qs('avgSalesValue').textContent = payload.avgSalesPerCustomerFormatted;
            if (payload.salesPerMonthFormatted && qs('salesPerMonthValue')) qs('salesPerMonthValue').textContent = payload.salesPerMonthFormatted;
            if (qs('salesMonthLabel')) {
              if (filterMonth && filterMonth.value) qs('salesMonthLabel').textContent = `Month: ${filterMonth.value}`;
              else qs('salesMonthLabel').textContent = 'All Months';
            }
            if (qs('yearLabel')) {
  if (filterYear && filterYear.value) {
    qs('yearLabel').textContent = `Year: ${filterYear.value}`;
  } else {
    qs('yearLabel').textContent = 'All Years';
  }
}

            if (payload.salesByYear) createMiniChart(payload.salesByYear);
            if (payload.salesByMonth) {

    latestMonthlyBreakdown =
        payload.salesByMonth;

    createClusterChart(
        payload.salesByMonth
    );
}

            if (Array.isArray(payload.topCustomers)) renderTopCustomers(payload.topCustomers);

            if (latestRefresh) latestRefresh.textContent = 'Latest Refresh: ' + new Date().toLocaleString(undefined, { month:'long', day:'2-digit', year:'numeric' });

          } catch (err) {
            console.error('fetchRealtime error', err);
          } finally {
            if (!opts.silent) hideLoader();
          }
        }

        // ---------- Attach events ----------
    // ---------- Drilldown Cards (Customers / Products / Agents / Divisions) ----------
  (function bindDrilldownCards() {
    const cards = document.querySelectorAll('.drill-card');
    if (!cards.length) return;

    cards.forEach(card => {
      if (card.__bound) return;
      card.__bound = true;

      card.style.cursor = 'pointer';

      card.addEventListener('click', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();

        const type = this.dataset.type;
        if (!type) return;

        // ✅ Customers = REAL SQL drilldown
        if (type === 'customer') {
          openAllCustomersDrilldown();
          return;
        }
        
        if (type === 'product') {
    openProductDrilldown();
    return;
  }

  if (type === 'agent') {
    openAgentDrilldown();
    return;
}

if (type === 'division') {
    openDivisionDrilldown();
    return;
}
        // Other drilldowns not yet implemented
        alert('This drilldown is coming soon.');
      });
    });
  })();

  // ===============================
  // Sales Per Month Card Drilldown
  // ===============================
(function bindSalesPerMonthCard() {

  const metric = document.getElementById('salesPerMonthValue');
  if (!metric) return;

  const card = metric.closest('.card');
  if (!card || card.__bound) return;

  card.__bound = true;
  card.style.cursor = 'pointer';

  card.addEventListener('click', function (ev) {
    ev.preventDefault();
    ev.stopPropagation();
    openAllCustomersDrilldown();
  });

})();
(function bindTotalSalesCard() {

    const card =
        document.getElementById(
            'totalSalesCard'
        );

    if (!card) return;

    if (card.__bound) return;

    card.__bound = true;

    card.addEventListener(
        'click',
        function () {

            showSalesBreakdownModal(
                latestMonthlyBreakdown
            );

        }
    );

})();
        try {
       if (applyBtn && !applyBtn.__bound) {
  applyBtn.addEventListener('click', function (e) {
    e.preventDefault();

    // 🔥 CLEAR CUSTOMER CACHE HERE TOO
    try { customerCache.clear(); } catch (_) {}

    fetchRealtime();
  });
  applyBtn.__bound = true;
}
        if (resetBtn && !resetBtn.__bound) {
  resetBtn.addEventListener('click', function (e) {
    e.preventDefault();

    if (filterYear) filterYear.value = '';
    if (filterMonth) filterMonth.value = '';

    // 🔥 CLEAR CUSTOMER CACHE HERE
    try { customerCache.clear(); } catch (_) {}

    fetchRealtime();
  });
  resetBtn.__bound = true;
}

          if (topCustomersBody && !topCustomersBody.__bound) {
          topCustomersBody.addEventListener('click', function (ev) {
    const tr = ev.target.closest('.top-customer-row');
    if (!tr) return;

    // prevent the document-level outside-click from closing the popover while opening details
    try { ev.stopPropagation(); } catch (_) {}

    const customer = tr.dataset.customer;
    openCustomerModal(customer);
  });

            topCustomersBody.__bound = true;
          }

        } catch(e){ console.warn('event attach error', e); }




        // ---------- INITIALIZE with server data (cfg) ----------
        try { if (cfg.salesByYear) createMiniChart(cfg.salesByYear); } catch (e) { console.warn('init mini chart failed', e); }
        try { if (cfg.salesByMonth) createClusterChart(cfg.salesByMonth); } catch (e) { console.warn('init cluster chart failed', e); }

        if (cfg.allTimeTotalFormatted && qs('totalSalesValue')) {
          qs('totalSalesValue').textContent = cfg.allTimeTotalFormatted;
        } else if (cfg.totalSalesFormatted && qs('totalSalesValue')) {
          qs('totalSalesValue').textContent = cfg.totalSalesFormatted;
        }

        if (Array.isArray(cfg.topCustomers)) renderTopCustomers(cfg.topCustomers);


      if (ROUTE_REALTIME) fetchRealtime();
      } // ✅ closes function init()
  window.addEventListener('unload', () => {
    try {
      if (window.__sales_pop_observer) {
        window.__sales_pop_observer.disconnect();
      }
    } catch (_) {}
  });
  })();