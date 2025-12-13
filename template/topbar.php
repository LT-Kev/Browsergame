<?php
// template/topbar.php
// Spielerdaten sind bereits in $playerData aus index.php geladen
?>
<div class="topbar">
    <div class="topbar-left">
        <button onclick="navigateBack()" class="back-button" id="back-button" style="display: none;" title="Zurück">
            <span class="iconify" data-icon="twemoji:left-arrow" data-width="24" data-height="24"></span>
        </button>

        <span class="iconify" data-icon="twemoji:video-game" data-width="24" data-height="24"></span>
        <?php echo SITE_NAME; ?>

        <div class="breadcrumb-container" id="breadcrumb-container" style="display: none;">
            <!-- Wird dynamisch per JS gefüllt -->
        </div>
    </div>

    <div class="topbar-right">
        <span id="player-name" title="Spielername">
            <span class="iconify" data-icon="twemoji:bust-in-silhouette" data-width="20" data-height="20"></span>
            <?php echo htmlspecialchars($playerData['username']); ?>
            <?php if($isAdmin): ?>
                <span class="admin-badge" style="background: <?php echo $adminLevelInfo['color']; ?>;">
                    <?php echo $adminLevelInfo['name']; ?>
                </span>
            <?php endif; ?>
        </span>

        <span id="player-gold" title="Gold: <?php echo $playerData['gold']; ?> / <?php echo $playerData['gold_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:coin" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['gold'], 0, ',', '.'); ?>
        </span>

        <span id="player-food" title="Nahrung: <?php echo $playerData['food']; ?> / <?php echo $playerData['food_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:poultry-leg" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['food'], 0, ',', '.'); ?>
        </span>

        <span id="player-wood" title="Holz: <?php echo $playerData['wood']; ?> / <?php echo $playerData['wood_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:wood" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['wood'], 0, ',', '.'); ?>
        </span>

        <span id="player-stone" title="Stein: <?php echo $playerData['stone']; ?> / <?php echo $playerData['stone_capacity']; ?>">
            <span class="iconify" data-icon="twemoji:rock" data-width="20" data-height="20"></span>
            <?php echo number_format($playerData['stone'], 0, ',', '.'); ?>
        </span>

        <span id="player-energy">
            <span class="iconify" data-icon="twemoji:high-voltage" data-width="20" data-height="20"></span>
            <?php echo $playerData['energy']; ?>/100
        </span>

        <a href="logout.php">
            <span class="iconify" data-icon="twemoji:door" data-width="20" data-height="20"></span>
            Logout
        </a>
    </div>
</div>

<style>
.back-button {
    background: rgba(233, 69, 96, 0.2);
    border: 2px solid rgba(233, 69, 96, 0.4);
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    margin-right: 15px;
    color: #fff;
}

.back-button:hover {
    background: rgba(233, 69, 96, 0.4);
    border-color: #e94560;
    transform: translateX(-3px);
}

.breadcrumb-container {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 15px;
    padding: 5px 15px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 8px;
}

.breadcrumb-arrow {
    opacity: 0.5;
}

.breadcrumb-link {
    color: #3498db;
    text-decoration: none;
    font-size: 0.9em;
    transition: color 0.3s;
    cursor: pointer;
}

.breadcrumb-link:hover {
    color: #5dade2;
    text-decoration: underline;
}

.breadcrumb-current {
    color: #e94560;
    font-size: 0.9em;
    font-weight: bold;
}

.admin-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: bold;
    margin-left: 8px;
    color: #fff;
}
</style>

<script>
// ------------------------------
// Helper: Root-Seiten erkennen
// ------------------------------
function isRootPage(page) {
    if (!page.includes('/')) return true;
    if (page === 'admin/dashboard') return true;
    return false;
}

// Navigation History Manager
const NavigationHistory = {
    stack: [],
    maxSize: 20,

    push(page) {
        this.stack = this.stack.filter(p => p !== page);
        this.stack.push(page);

        if (this.stack.length > this.maxSize) {
            this.stack.shift();
        }

        this.save();
        this.updateUI();
    },

    pop() {
        if (this.stack.length > 1) {
            this.stack.pop();
            this.save();
            return this.stack[this.stack.length - 1];
        }
        return 'overview';
    },

    getCurrent() {
        return this.stack[this.stack.length - 1] || 'overview';
    },

    getPrevious() {
        return this.stack[this.stack.length - 2] || 'overview';
    },

    clear() {
        this.stack = [];
        this.save();
        this.updateUI();
    },

    save() {
        try {
            sessionStorage.setItem('nav_history', JSON.stringify(this.stack));
        } catch(e) {
            console.warn('Could not save navigation history:', e);
        }
    },

    load() {
        try {
            const saved = sessionStorage.getItem('nav_history');
            if (saved) {
                this.stack = JSON.parse(saved);
            }
        } catch(e) {
            console.warn('Could not load navigation history:', e);
            this.stack = [];
        }
        this.updateUI();
    },

    updateUI() {
        this.updateBackButton();
        this.updateBreadcrumbs();
    },

    updateBackButton() {
        const backBtn = document.getElementById('back-button');
        if (!backBtn) return;

        const current = this.getCurrent();

        if (isRootPage(current)) {
            backBtn.style.display = 'none';
            return;
        }

        backBtn.style.display = 'inline-flex';
        backBtn.title = `Zurück zu: ${this.formatPageName(this.getPrevious())}`;
    },

    updateBreadcrumbs() {
        const container = document.getElementById('breadcrumb-container');
        if (!container) return;

        const currentPage = this.getCurrent();
        const isInAdmin = currentPage.startsWith('admin/');

        if (!isInAdmin || this.stack.length <= 1) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'inline-flex';

        const parts = currentPage.split('/');
        let html = '';
        let path = '';

        parts.forEach((part, index) => {
            path += (path ? '/' : '') + part;

            if (index > 0) {
                html += '<span class="iconify breadcrumb-arrow" data-icon="twemoji:right-arrow" data-width="16" data-height="16"></span>';
            }

            const label = this.formatPageName(part);

            let target = path;
            if (target === 'admin') {
                target = 'admin/dashboard';
            }

            if (index < parts.length - 1) {
                html += `<span class="breadcrumb-link" onclick="loadPage('${target}')">${label}</span>`;
            } else {
                html += `<span class="breadcrumb-current">${label}</span>`;
            }
        });

        container.innerHTML = html;
    },

    formatPageName(page) {
        const specialNames = {
            'admins': 'Admin',
            'admin_edit': 'Bearbeiten',
            'dashboard': 'Dashboard',
            'players': 'Spieler',
            'player_edit': 'Bearbeiten',
            'logs': 'Logs',
            'settings': 'Einstellungen',
            'race_manager': 'Rassen',
            'class_manager': 'Klassen',
            'overview': 'Übersicht'
        };

        const lastPart = page.split('/').pop();
        if (specialNames[lastPart]) return specialNames[lastPart];

        return lastPart.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }
};

// Navigation
function navigateBack() {
    const previousPage = NavigationHistory.pop();
    loadPageInternal(previousPage, true);
}

function loadPageInternal(page, skipHistoryPush = false) {
    let url = 'pages/' + page + '.php';

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $('#center-content').html(response);

            if (!skipHistoryPush) {
                NavigationHistory.push(page);
            } else {
                NavigationHistory.updateUI();
            }
        },
        error: function() {
            $('#center-content').html('<h2>Fehler</h2><p>Seite konnte nicht geladen werden.</p>');
        }
    });
}

window.loadPage = function(page, params = {}) {
    NavigationHistory.push(page);

    let url = 'pages/' + page + '.php';
    if (Object.keys(params).length > 0) {
        url += '?' + $.param(params);
    }

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $('#center-content').html(response);
        },
        error: function() {
            NavigationHistory.pop();
        }
    });
};

// Init
$(document).ready(function() {
    NavigationHistory.load();
    if (NavigationHistory.stack.length === 0) {
        NavigationHistory.push('overview');
    }
});

// Keyboard Shortcut
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'ArrowLeft') {
        e.preventDefault();
        if (!isRootPage(NavigationHistory.getCurrent())) {
            navigateBack();
        }
    }
});

// Debug
if (typeof DEV_MODE !== 'undefined' && DEV_MODE) {
    window.showHistory = function() {
        console.log('Navigation History:', NavigationHistory.stack);
    };
}
</script>
