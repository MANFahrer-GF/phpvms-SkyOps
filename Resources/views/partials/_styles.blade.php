{{-- SkyOps Shared Styles — mirrors Airline Pulse design system --}}
{{-- CSS Variables: ap-* (shared) · Class prefix: so-* (module-scoped) --}}
<style>
/* ═══ Design System Variables (identical to Airline Pulse) ═══ */
:root {
  --ap-cyan:    #0ea5e9;
  --ap-blue:    #3b82f6;
  --ap-violet:  #818cf8;
  --ap-green:   #22c55e;
  --ap-amber:   #f59e0b;
  --ap-red:     #ef4444;
  --ap-font-head: 'Outfit', sans-serif;
  --ap-font-mono: 'JetBrains Mono', monospace;
  --ap-font-body: 'Inter', sans-serif;
  /* Dark defaults */
  --ap-surface:    rgba(255,255,255,0.04);
  --ap-border:     rgba(255,255,255,0.08);
  --ap-border2:    rgba(255,255,255,0.18);
  --ap-card-bg:    rgba(255,255,255,0.03);
  --ap-text:       #e2e8f0;
  --ap-text-head:  #ffffff;
  --ap-muted:      #cbd5e1;
  --ap-select-bg:  rgba(255,255,255,0.07);
  --ap-tag-bg:     rgba(255,255,255,0.07);
  --ap-tag-color:  #e2e8f0;
  --ap-divider:    rgba(255,255,255,0.07);
}

/* ═══ Light mode (triggered by theme detection script) ═══ */
html.ap-light {
  --ap-surface:    rgba(255,255,255,0.9);
  --ap-border:     rgba(0,0,0,0.1);
  --ap-border2:    rgba(0,0,0,0.2);
  --ap-card-bg:    rgba(255,255,255,0.8);
  --ap-text:       #1e293b;
  --ap-text-head:  #0f172a;
  --ap-muted:      #64748b;
  --ap-select-bg:  rgba(0,0,0,0.05);
  --ap-tag-bg:     rgba(0,0,0,0.06);
  --ap-tag-color:  #334155;
  --ap-divider:    rgba(0,0,0,0.08);
}
html.ap-light .so-card{box-shadow:0 1px 8px rgba(0,0,0,.06)}
html.ap-light .so-nav{box-shadow:0 1px 4px rgba(0,0,0,.04)}

/* ═══ SkyOps Module Components ═══ */
.so-wrap{max-width:100%;margin:0 auto;padding:1rem;font-family:var(--ap-font-body);color:var(--ap-text);font-variant-numeric:lining-nums;overflow-x:hidden}

/* Navigation */
.so-nav{display:flex;gap:.25rem;background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:10px;padding:.25rem;margin-bottom:1.5rem;overflow-x:auto}
.so-nav a{padding:.5rem 1rem;border-radius:6px;color:var(--ap-muted);text-decoration:none;font-family:var(--ap-font-head);font-size:.85rem;font-weight:500;white-space:nowrap;transition:all .15s}
.so-nav a:hover{color:var(--ap-text);background:var(--ap-surface)}
.so-nav a.active{color:#fff;background:var(--ap-blue)}
.so-nav-guide{margin-left:auto!important;width:30px;height:30px;display:flex!important;align-items:center;justify-content:center;border-radius:50%!important;padding:0!important;font-weight:700;font-size:.78rem;opacity:.5;transition:opacity .15s,background .15s}
.so-nav-guide:hover,.so-nav-guide.active{opacity:1}

/* Cards */
.so-card{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;padding:1.25rem;margin-bottom:1rem;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);overflow:hidden}
.so-card-title{font-family:var(--ap-font-head);font-size:1rem;font-weight:700;margin-bottom:1rem;color:var(--ap-text-head)}
.so-card-subtitle{font-size:.78rem;color:var(--ap-muted);margin-bottom:.75rem}

/* Tables */
.so-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;max-width:100%}
.so-table{width:100%;border-collapse:collapse;font-size:.82rem}
.so-table th{background:var(--ap-surface);color:var(--ap-muted);font-family:var(--ap-font-head);font-weight:600;text-transform:uppercase;font-size:.72rem;letter-spacing:.5px;padding:.6rem .75rem;border-bottom:2px solid var(--ap-border);white-space:nowrap;text-align:left}
.so-table td{padding:.55rem .75rem;border-bottom:1px solid var(--ap-border);vertical-align:middle;color:var(--ap-text)}
.so-table tr:hover{background:rgba(59,130,246,.04)}
.so-table th a{color:var(--ap-muted);text-decoration:none}
.so-table th a:hover{color:var(--ap-text-head)}
.so-table th a.so-sort-active{color:var(--ap-blue)}

/* Badges (matches Airline Pulse ap-tag style) */
.so-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-family:var(--ap-font-mono);font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;border:1px solid transparent}
.so-badge-primary{background:rgba(59,130,246,.15);border-color:rgba(59,130,246,.3);color:#93c5fd}
.so-badge-secondary{background:rgba(125,133,144,.15);border-color:rgba(125,133,144,.3);color:var(--ap-muted)}
.so-badge-info{background:rgba(14,165,233,.15);border-color:rgba(14,165,233,.3);color:#67e8f9}
.so-badge-success{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.3);color:#86efac}
.so-badge-warning{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.3);color:#fde68a}
.so-badge-danger{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:#fca5a5}
.so-badge-violet{background:rgba(129,140,248,.15);border-color:rgba(129,140,248,.3);color:#c4b5fd}
.so-badge-vatsim{background:rgba(0,159,227,.15);border-color:rgba(0,159,227,.3);color:#7dd3fc}
.so-badge-ivao{background:rgba(30,80,162,.15);border-color:rgba(30,80,162,.3);color:#93c5fd}
.so-badge-poscon{background:rgba(255,165,0,.15);border-color:rgba(255,165,0,.3);color:#fed7aa}
html.ap-light .so-badge-primary{color:#1d4ed8;background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.2)}
html.ap-light .so-badge-secondary{color:#475569;background:rgba(100,116,139,.1);border-color:rgba(100,116,139,.2)}
html.ap-light .so-badge-info{color:#0369a1;background:rgba(14,165,233,.1);border-color:rgba(14,165,233,.2)}
html.ap-light .so-badge-success{color:#166534;background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.2)}
html.ap-light .so-badge-warning{color:#92400e;background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.2)}
html.ap-light .so-badge-danger{color:#991b1b;background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.2)}
html.ap-light .so-badge-violet{color:#5b21b6;background:rgba(129,140,248,.1);border-color:rgba(129,140,248,.2)}
html.ap-light .so-badge-vatsim{color:#0369a1;background:rgba(0,159,227,.1);border-color:rgba(0,159,227,.2)}
html.ap-light .so-badge-ivao{color:#1e3a8a;background:rgba(30,80,162,.1);border-color:rgba(30,80,162,.2)}
html.ap-light .so-badge-poscon{color:#92400e;background:rgba(255,165,0,.1);border-color:rgba(255,165,0,.2)}

/* Landing Rate */
.so-rate-crash{color:var(--ap-red)}
.so-rate-hard{color:var(--ap-amber)}
.so-rate-ok{color:var(--ap-green)}
.so-rate-smooth{color:var(--ap-cyan)}
.so-rate-butter{color:var(--ap-blue)}

/* Landing Rate alignment */
.so-pl-landing{display:inline-flex;align-items:center;gap:0;font-size:.75rem;font-family:var(--ap-font-mono);white-space:nowrap}
.so-pl-lr-icon{width:18px;text-align:center;flex-shrink:0}
.so-pl-lr-val{text-align:right;min-width:36px}
.so-pl-lr-unit{margin-left:3px;font-size:.65rem;color:var(--ap-muted)}

/* Phase Status Pill */
.so-phase{display:inline-flex;align-items:center;gap:.3rem;padding:.15rem .5rem;border-radius:12px;font-family:var(--ap-font-mono);font-size:.72rem;font-weight:500;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);color:#93c5fd}
.so-phase-live{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.3);color:#86efac;animation:so-pulse 2s infinite}
.so-phase-arrived{background:rgba(125,133,144,.15);border-color:rgba(125,133,144,.3);color:var(--ap-muted)}
.so-phase-accepted{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.3);color:#86efac}
.so-phase-rejected{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:#fca5a5}
html.ap-light .so-phase{color:#1d4ed8;background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.2)}
html.ap-light .so-phase-live{color:#166534;background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.2)}
html.ap-light .so-phase-arrived{color:#475569;background:rgba(100,116,139,.08);border-color:rgba(100,116,139,.15)}
html.ap-light .so-phase-accepted{color:#166534;background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.2)}
html.ap-light .so-phase-rejected{color:#991b1b;background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.2)}
@keyframes so-pulse{0%,100%{opacity:1}50%{opacity:.6}}

/* Forms */
.so-input,.so-select{background:#1e293b;border:1px solid var(--ap-border);border-radius:8px;color:var(--ap-text);font-family:var(--ap-font-head);padding:.4rem .75rem;font-size:.85rem;outline:none;transition:border-color .15s}
.so-input:focus,.so-select:focus{border-color:var(--ap-blue);box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.so-input::placeholder{color:var(--ap-muted)}
.so-select option{background:#1e293b;color:var(--ap-text)}
html.ap-light .so-input,html.ap-light .so-select{background:#ffffff}
html.ap-light .so-select option{background:#ffffff;color:#1e293b}

/* Buttons */
.so-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem 1rem;border-radius:6px;font-family:var(--ap-font-head);font-size:.82rem;font-weight:500;border:none;cursor:pointer;transition:all .15s;text-decoration:none}
.so-btn-primary{background:var(--ap-blue);color:#fff}
.so-btn-primary:hover{opacity:.85}
.so-btn-ghost{background:transparent;color:var(--ap-muted);border:1px solid var(--ap-border)}
.so-btn-ghost:hover{color:var(--ap-text);border-color:var(--ap-muted)}
.so-btn-sm{padding:.25rem .6rem;font-size:.75rem}

/* PIREP link button */
.so-pl-link{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;border:1px solid var(--ap-border);background:var(--ap-surface);color:var(--ap-text);font-size:.8rem;text-decoration:none;transition:all .15s;cursor:pointer}
.so-pl-link:hover{background:rgba(59,130,246,.12);border-color:var(--ap-blue);color:var(--ap-blue)}
html.ap-light .so-pl-link:hover{background:rgba(59,130,246,.08);color:#1d4ed8}

/* LIVE badge — pulsing red */
.so-live-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:8px;font-family:var(--ap-font-mono);font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.35);color:#fca5a5;animation:so-live-pulse 1.5s ease-in-out infinite}
.so-live-dot{width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;animation:so-live-dot-pulse 1.5s ease-in-out infinite}
@keyframes so-live-pulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(239,68,68,.3)}50%{opacity:.85;box-shadow:0 0 12px 4px rgba(239,68,68,.15)}}
@keyframes so-live-dot-pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.3);opacity:.7}}
html.ap-light .so-live-badge{color:#991b1b;background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.25)}

/* LIVE card border glow */
.so-card-live{border-color:rgba(239,68,68,.3)!important;box-shadow:0 0 0 1px rgba(239,68,68,.08),0 2px 16px rgba(239,68,68,.06)}
.so-card-live .so-card-title{border-bottom:2px solid rgba(239,68,68,.3)!important;padding-bottom:10px}
.so-quick-btn{padding:.25rem .6rem;border-radius:4px;font-family:var(--ap-font-head);font-size:.72rem;font-weight:500;border:1px solid var(--ap-border);background:transparent;color:var(--ap-muted);cursor:pointer;text-decoration:none}
.so-quick-btn:hover,.so-quick-btn.active{background:var(--ap-blue);color:#fff;border-color:var(--ap-blue)}

/* Filters */
.so-filter-row{display:flex;flex-wrap:wrap;gap:.5rem;align-items:flex-end}
.so-filter-group{display:flex;flex-direction:column;gap:.2rem}
.so-filter-label{font-family:var(--ap-font-head);font-size:.72rem;font-weight:600;color:var(--ap-muted);text-transform:uppercase;letter-spacing:.12em}

/* Pagination */
.so-pagination{display:flex;justify-content:center;gap:.25rem;margin-top:1rem}
.so-pagination a,.so-pagination span{padding:.35rem .75rem;border-radius:6px;font-size:.8rem;text-decoration:none;color:var(--ap-muted);border:1px solid var(--ap-border)}
.so-pagination a:hover{background:var(--ap-surface);color:var(--ap-text)}
.so-pagination .active{background:var(--ap-blue);color:#fff;border-color:var(--ap-blue)}
.so-pagination .disabled{opacity:.4;pointer-events:none}

/* Stat Cards */
.so-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem;margin-bottom:1rem}
.so-stat-box{background:var(--ap-surface);border:1px solid var(--ap-border);border-radius:14px;padding:1rem;text-align:center;position:relative;overflow:hidden;transition:transform .15s,border-color .2s}
.so-stat-box:hover{transform:translateY(-2px);border-color:var(--ap-border2)}
.so-stat-value{font-family:var(--ap-font-head);font-size:1.5rem;font-weight:800;color:var(--ap-text-head);font-variant-numeric:lining-nums tabular-nums}
.so-stat-label{font-size:.72rem;color:var(--ap-muted);text-transform:uppercase;margin-top:.25rem;font-weight:500;letter-spacing:.1em}

/* Period Tabs */
.so-tabs{display:flex;gap:.25rem;margin-bottom:1rem}
.so-tab{padding:.35rem .75rem;border-radius:6px;font-family:var(--ap-font-head);font-size:.8rem;font-weight:500;border:1px solid var(--ap-border);background:transparent;color:var(--ap-muted);cursor:pointer;text-decoration:none}
.so-tab:hover{color:var(--ap-text)}
.so-tab.active{background:var(--ap-blue);color:#fff;border-color:var(--ap-blue)}

/* Live Row + Empty + Count */
.so-row-live{background:rgba(239,68,68,.04)!important}
.so-row-live:hover{background:rgba(239,68,68,.08)!important}
.so-empty{text-align:center;padding:2rem;color:var(--ap-muted);font-size:.9rem}
.so-count{display:inline-flex;align-items:center;gap:.3rem;padding:.15rem .5rem;border-radius:10px;font-family:var(--ap-font-mono);font-size:.72rem;font-weight:600;background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);color:#93c5fd}
html.ap-light .so-count{color:#1d4ed8;background:rgba(59,130,246,.1);border-color:rgba(59,130,246,.2)}

/* Mono text helper */
.so-mono{font-family:var(--ap-font-mono);font-variant-numeric:lining-nums tabular-nums}

/* Responsive */
@media(max-width:768px){
    .so-nav{gap:.15rem;padding:.15rem}
    .so-nav a{padding:.35rem .6rem;font-size:.78rem}
    .so-filter-row{flex-direction:column}
    .so-filter-group{width:100%}
    .so-input,.so-select{width:100%}
    .so-stat-grid{grid-template-columns:repeat(2,1fr)}
}

/* Footer */
.so-footer{text-align:center;padding:1.5rem 0 .5rem;font-size:.7rem;color:var(--ap-text-muted);opacity:.45;transition:opacity .3s;letter-spacing:.02em}
.so-footer:hover{opacity:.85}
.so-footer a{color:var(--ap-text-muted);text-decoration:none;font-weight:600}
.so-footer a:hover{color:var(--ap-accent);text-decoration:underline}
.so-heart{color:#e25555;font-size:.75rem;display:inline-block;animation:so-pulse 1.8s ease-in-out infinite}
@keyframes so-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}
</style>
