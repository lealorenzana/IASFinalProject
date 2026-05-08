<?php
require 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Last login from audit log
$stmt2 = $pdo->prepare("SELECT timestamp FROM audit_logs WHERE user_id = ? AND action = 'login' AND status = 'success' ORDER BY timestamp DESC LIMIT 2");
$stmt2->execute([$user_id]);
$logins = $stmt2->fetchAll();
$last_login = isset($logins[1]) ? $logins[1]['timestamp'] : ($logins[0]['timestamp'] ?? 'First login');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureVault — Dashboard</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0a0e1a;
            --surface:   #111827;
            --surface2:  #1a2235;
            --surface3:  #0f1929;
            --border:    rgba(99,179,237,0.1);
            --border-hi: rgba(99,179,237,0.25);
            --accent:    #3b82f6;
            --accent2:   #6366f1;
            --glow:      rgba(59,130,246,0.2);
            --text:      #f1f5f9;
            --muted:     #94a3b8;
            --success:   #10b981;
            --warning:   #f59e0b;
            --danger:    #ef4444;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(59,130,246,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59,130,246,0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 240px;
            height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 28px 0;
            z-index: 10;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 24px 28px;
            border-bottom: 1px solid var(--border);
        }

        .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 16px var(--glow);
            flex-shrink: 0;
        }

        .brand-icon svg { width: 18px; height: 18px; fill: white; }

        .brand-name {
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-name span { color: var(--accent); }

        .nav {
            flex: 1;
            padding: 20px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #334155;
            padding: 0 12px;
            margin: 12px 0 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }

        .nav-item svg { width: 16px; height: 16px; flex-shrink: 0; }

        .nav-item:hover { background: var(--surface2); color: var(--text); }

        .nav-item.active {
            background: rgba(59,130,246,0.12);
            color: var(--accent);
            border: 1px solid rgba(59,130,246,0.2);
        }

        .sidebar-footer {
            padding: 20px 12px 0;
            border-top: 1px solid var(--border);
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--surface2);
            border: 1px solid var(--border);
        }

        .avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }

        .user-name {
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-role {
            font-size: 0.7rem;
            color: var(--muted);
            text-transform: capitalize;
        }

        /* Main content */
        .main {
            margin-left: 240px;
            padding: 40px;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 36px;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .page-title span { color: var(--muted); font-weight: 400; }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--muted);
            font-size: 0.8rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.2s, color 0.2s, background 0.2s;
        }

        .logout-btn svg { width: 14px; height: 14px; }

        .logout-btn:hover {
            border-color: rgba(239,68,68,0.4);
            color: #fca5a5;
            background: rgba(239,68,68,0.06);
        }

        /* Stats row */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 42px; height: 42px;
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon svg { width: 18px; height: 18px; }

        .stat-icon.blue  { background: rgba(59,130,246,0.12); color: var(--accent); }
        .stat-icon.green { background: rgba(16,185,129,0.12); color: var(--success); }
        .stat-icon.purple{ background: rgba(99,102,241,0.12); color: #818cf8; }

        .stat-label { font-size: 0.75rem; color: var(--muted); margin-bottom: 3px; }
        .stat-value { font-size: 1rem; font-weight: 700; }

        /* Cards grid */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 26px;
            transition: border-color 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            opacity: 0;
            transition: opacity 0.2s;
        }

        .card:hover {
            border-color: var(--border-hi);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .card:hover::before { opacity: 1; }

        .card-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }

        .card-icon svg { width: 20px; height: 20px; }
        .card-icon.blue   { background: rgba(59,130,246,0.12); color: var(--accent); }
        .card-icon.purple { background: rgba(99,102,241,0.12); color: #818cf8; }
        .card-icon.green  { background: rgba(16,185,129,0.12); color: var(--success); }

        .card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .card p {
            color: var(--muted);
            font-size: 0.85rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 16px;
            background: rgba(59,130,246,0.1);
            border: 1px solid rgba(59,130,246,0.2);
            border-radius: 9px;
            color: var(--accent);
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, border-color 0.2s;
        }

        .card-btn svg { width: 13px; height: 13px; }

        .card-btn:hover {
            background: rgba(59,130,246,0.18);
            border-color: rgba(59,130,246,0.35);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .badge.admin { background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.25); }
        .badge.user  { background: rgba(59,130,246,0.12); color: var(--accent); border: 1px solid rgba(59,130,246,0.2); }

        /* Responsive Design */
        /* Tablet: 560px - 899px */
        @media (max-width: 899px) and (min-width: 560px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 32px; }
            
            .stats { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 14px;
            }
            
            .cards { 
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
                gap: 16px;
            }
            
            .topbar { margin-bottom: 28px; }
            .page-title { font-size: 1.35rem; }
        }

        /* Mobile: < 560px */
        @media (max-width: 560px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 20px 16px; }
            
            /* Stack stat cards vertically */
            .stats { 
                grid-template-columns: 1fr; 
                gap: 12px;
                margin-bottom: 24px;
            }
            
            .stat-card { 
                padding: 16px 18px; 
            }
            
            .stat-icon { 
                width: 38px; 
                height: 38px; 
            }
            
            .stat-icon svg { width: 16px; height: 16px; }
            .stat-label { font-size: 0.7rem; }
            .stat-value { font-size: 0.95rem; }
            
            /* Stack feature cards */
            .cards { 
                grid-template-columns: 1fr; 
                gap: 14px;
            }
            
            .card { 
                padding: 22px; 
                border-radius: 16px;
            }
            
            .card-icon { 
                width: 42px; 
                height: 42px; 
                margin-bottom: 14px;
            }
            
            .card-icon svg { width: 18px; height: 18px; }
            .card h3 { font-size: 0.95rem; }
            .card p { font-size: 0.8rem; margin-bottom: 16px; }
            
            /* Ensure minimum touch target sizes (44x44px) */
            .card-btn, .logout-btn { 
                min-height: 44px;
                padding: 12px 16px;
                font-size: 0.85rem;
            }
            
            /* Topbar adjustments */
            .topbar { 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 16px;
                margin-bottom: 24px;
            }
            
            .page-title { font-size: 1.25rem; }
            
            .logout-btn { 
                align-self: stretch;
                justify-content: center;
            }
            
            /* Adjust font sizes for mobile readability */
            .badge { font-size: 0.65rem; padding: 2px 7px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            </div>
            <div class="brand-name">Secure<span>Vault</span></div>
        </div>

        <nav class="nav">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="profile.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                My Profile
            </a>
            <?php if ($role == 'admin'): ?>
            <div class="nav-label">Admin</div>
            <a href="admin.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Audit Logs
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1))); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($role); ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <div class="topbar">
            <div class="page-title">Dashboard <span>/ Overview</span></div>
            <a href="logout.php" class="logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </a>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div>
                    <div class="stat-label">Signed in as</div>
                    <div class="stat-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div>
                    <div class="stat-label">Session status</div>
                    <div class="stat-value" style="color: var(--success);">Active &amp; Secure</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="stat-label">Last login</div>
                    <div class="stat-value" style="font-size:0.8rem;"><?php echo htmlspecialchars($last_login); ?></div>
                </div>
            </div>
        </div>

        <!-- Cards -->
        <div class="cards">
            <div class="card">
                <div class="badge <?php echo $role == 'admin' ? 'admin' : 'user'; ?>">
                    <svg width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                    <?php echo ucfirst($role); ?>
                </div>
                <div class="card-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <h3>My Profile</h3>
                <p>View and update your personal information, contact details, and account settings.</p>
                <a href="profile.php" class="card-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                    Manage Profile
                </a>
            </div>

            <?php if ($role == 'admin'): ?>
            <div class="card">
                <div class="badge admin">
                    <svg width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
                    Admin Only
                </div>
                <div class="card-icon purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Audit Logs</h3>
                <p>Monitor all system activity, user actions, login attempts, and security events.</p>
                <a href="admin.php" class="card-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                    View Logs
                </a>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-icon green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h3>Security Status</h3>
                <p>Your account is protected with bcrypt hashing, AES-256 encryption, and session management.</p>
                <span class="card-btn" style="cursor:default; opacity:0.7;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    All systems secure
                </span>
            </div>
        </div>
    </main>
</body>
</html>
