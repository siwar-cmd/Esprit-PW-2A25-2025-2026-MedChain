<style>
    .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
    .dashboard-sidebar { background: linear-gradient(180deg, var(--navy) 0%, #0F172A 100%); position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column; overflow-y: auto; }
    .dashboard-logo { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
    .dashboard-logo a { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .dashboard-logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; }
    .dashboard-logo-icon i { font-size: 18px; color: white; }
    .dashboard-logo-text { font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 700; color: white; }
    .dashboard-logo-text span { color: var(--green); }
    .dashboard-nav { flex: 1; display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
    .dashboard-nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #94A3B8; text-decoration: none; border-radius: var(--radius-md); transition: all 0.3s; font-size: 14px; font-weight: 500; }
    .dashboard-nav-item i { font-size: 18px; width: 24px; }
    .dashboard-nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
    .dashboard-nav-item.active { background: rgba(29,158,117,0.2); color: var(--green); }
    .dashboard-nav-item.logout { color: #F87171; }
    .dashboard-nav-item.logout:hover { background: rgba(248,113,113,0.1); }
    .dashboard-nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #64748B; padding: 16px 16px 8px; font-weight: 600; }
    .dashboard-main { padding: 32px 40px; overflow-y: auto; background: linear-gradient(145deg, #f0faf6 0%, #e8f7f1 50%, #ddf3ea 100%); min-height: 100vh; }
    @media (max-width: 1024px) { .dashboard-container { grid-template-columns: 240px 1fr; } .dashboard-main { padding: 24px; } }
    @media (max-width: 768px) {
        .dashboard-container { grid-template-columns: 1fr; }
        .dashboard-sidebar { position: fixed; left: -280px; top: 0; bottom: 0; width: 260px; z-index: 1000; transition: left 0.3s; }
        .dashboard-sidebar.open { left: 0; }
        .dashboard-main { padding: 20px; }
    }
</style>
