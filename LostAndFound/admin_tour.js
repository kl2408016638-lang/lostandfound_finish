function startAdminTour() {
    const tour = new Shepherd.Tour({
    useModalOverlay: false,
    defaultStepOptions: {
        classes: 'shepherd-theme-custom',
        scrollTo: { behavior: 'smooth', block: 'center' },
        cancelIcon: { enabled: true },
        when: {
            show() {
                document.querySelectorAll('.shepherd-highlight').forEach(el => el.classList.remove('shepherd-highlight'));
                const el = document.querySelector(this.options.attachTo?.element);
                if (el) el.classList.add('shepherd-highlight');
            },
            hide() {
                document.querySelectorAll('.shepherd-highlight').forEach(el => el.classList.remove('shepherd-highlight'));
            }
        }
    }
});

    const page = window.location.pathname.split('/').pop();

    const commonSteps = [
        {
            id: 'welcome',
            title: '👋 Welcome, Admin!',
            text: 'This is the Admin Panel for Surau Ismail Kharofa Lost & Found System. Let us show you around!',
            attachTo: { element: '.admin-logo', on: 'right' },
            buttons: [
                { text: 'Skip Tour', action: () => tour.cancel(), classes: 'shepherd-btn-skip' },
                { text: 'Start Tour →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'sidebar',
            title: '📋 Admin Navigation',
            text: 'Use this sidebar to navigate all admin pages — Profile, Lists, and Dashboard tools.',
            attachTo: { element: '.admin-nav-menu', on: 'right' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'notification',
            title: '🔔 Admin Notifications',
            text: 'This bell icon shows new activity — such as new items reported by users. Badge appears when there are unread notifications.',
            attachTo: { element: '.admin-notification-btn', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'admin-profile-header',
            title: '👤 Admin Account',
            text: 'Click here to view your admin profile or log out of the system.',
            attachTo: { element: '.admin-user-profile', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const profileSteps = [
        {
            id: 'profile-pic',
            title: '📷 Admin Profile Picture',
            text: 'Upload or change your admin profile picture here. Supported formats: JPG, PNG, GIF. Max size: 2MB.',
            attachTo: { element: '.profile-pic-wrapper', on: 'right' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'profile-info',
            title: 'ℹ️ Current Information',
            text: 'This shows your current admin name, email, password, and admin ID.',
            attachTo: { element: '.profile-card', on: 'right' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'profile-form',
            title: '✏️ Edit Admin Profile',
            text: 'Update your name, email address, and password here. Leave password blank to keep the current one.',
            attachTo: { element: '.form-card', on: 'left' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const listFoundSteps = [
        {
            id: 'found-stats',
            title: '📦 Active Found Items',
            text: 'This badge shows the total number of active found items — Pending and Approved only. Matched and Claimed items are in Archive.',
            attachTo: { element: '.stats-badge', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'found-search',
            title: '🔍 Search Found Items',
            text: 'Search by item type, description, or location to quickly find a specific item.',
            attachTo: { element: '.search-form', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'found-table',
            title: '📋 Found Items Table',
            text: 'All active found items are listed here. You can see the type, date, location, picture, description, and who reported it.',
            attachTo: { element: 'table', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'found-status-change',
            title: '🔄 Change Item Status',
            text: 'Use this dropdown to update an item\'s status. Flow: Pending → Approved → Matched → Claimed. Items marked Matched or Claimed will move to Archive automatically.',
            attachTo: { element: 'select[name="status"]', on: 'left' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const listLostSteps = [
        {
            id: 'lost-stats',
            title: '⚠️ Active Lost Items',
            text: 'This badge shows the total number of active lost item reports. Only Pending items appear here — Matched and Claimed go to Archive.',
            attachTo: { element: '.stats-badge', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'lost-search',
            title: '🔍 Search Lost Items',
            text: 'Search for a specific lost item report by name, description, or location.',
            attachTo: { element: '.search-form', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'lost-table',
            title: '📋 Lost Items Table',
            text: 'All active lost item reports are shown here — item type, date lost, location, picture, description, and the user who reported it.',
            attachTo: { element: 'table', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'lost-status-change',
            title: '🔄 Change Lost Item Status',
            text: 'Lost items skip "Approved" status. Flow: Pending → Matched → Claimed. Once Matched or Claimed, they are archived automatically.',
            attachTo: { element: 'select[name="status"]', on: 'left' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const statisticsSteps = [
        {
            id: 'stats-period',
            title: '📅 Period Filter',
            text: 'Filter statistics by time period — Today, Last 7 Days, Last 30 Days, This Year, or All Time. Click Refresh to reload data.',
            attachTo: { element: '.period-selector', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'stats-quickstats',
            title: '📊 Quick Statistics',
            text: 'These 4 cards show Total Cases, Today\'s Cases, Claimed Rate (percentage of items returned to owners), and Pending Cases requiring attention.',
            attachTo: { element: '.quick-stats', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'stats-charts',
            title: '📈 Charts',
            text: 'The left chart shows status distribution (Pending, Approved, Matched, Claimed). The right chart shows case trends over the last 7 days.',
            attachTo: { element: '.charts-section', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'stats-top-items',
            title: '🏆 Top 10 Common Items',
            text: 'This table ranks the most frequently reported item types. Useful for identifying which items are most commonly lost or found in the surau.',
            attachTo: { element: '.table-container', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'stats-export',
            title: '📤 Export & Print',
            text: 'Print the dashboard directly or export statistics data as a CSV file for record keeping.',
            attachTo: { element: '.export-section', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const trailSteps = [
        {
            id: 'trail-stats',
            title: '📊 Activity Overview',
            text: 'These cards show Total Activities, Today\'s Activities, User-related Actions, and Item-related Actions logged in the system.',
            attachTo: { element: '.stats-container', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'trail-filters',
            title: '🔍 Filter Activity Logs',
            text: 'Filter logs by keyword search, action type (approve, delete, edit, etc.), and date range. Apply filters to narrow down what you want to see.',
            attachTo: { element: '.filters-container', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'trail-table',
            title: '📋 Activity Log Table',
            text: 'Every admin action is recorded here — timestamp, which admin did it, what action was taken, which target was affected, a description, and the IP address.',
            attachTo: { element: 'table', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'trail-export',
            title: '📤 Export Logs',
            text: 'Export the activity log to a CSV file for audit purposes or record keeping.',
            attachTo: { element: '.export-options', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const usersSteps = [
        {
            id: 'users-stats',
            title: '👥 Account Overview',
            text: 'These cards show the total number of accounts, how many are Admin accounts, and how many are regular User accounts.',
            attachTo: { element: '.stats-container', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'users-filter',
            title: '🔍 Search & Filter Users',
            text: 'Search users by name, email, or phone number. Filter by role — All, Admin only, or User only.',
            attachTo: { element: '.filters-container', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'users-table',
            title: '📋 User Accounts Table',
            text: 'All registered accounts are listed here with their ID, name, email, phone, and role.',
            attachTo: { element: 'table', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'users-actions',
            title: '⚙️ Edit & Delete',
            text: 'Use Edit to update a user\'s name, email, or phone number. Use Delete to remove an account. Note: You cannot delete your own account or the last remaining admin.',
            attachTo: { element: '.actions-cell', on: 'left' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const archiveSteps = [
        {
            id: 'archive-stats',
            title: '📦 Archive Statistics',
            text: 'These cards show how many Lost Items, Found Items, and total items have been matched or claimed and archived.',
            attachTo: { element: '.stats-cards', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'archive-filter',
            title: '🔍 Filter Archive',
            text: 'Filter archived items by type (Lost or Found), keyword search, or date range. Useful for finding specific resolved cases.',
            attachTo: { element: '.filter-section', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'archive-table',
            title: '📋 Archived Items Table',
            text: 'All matched and claimed items are stored here. Each row shows the item type, description, date, location, picture, who reported it, and final status.',
            attachTo: { element: '.items-table', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'archive-print',
            title: '🖨️ Print Archive',
            text: 'Print the archive report directly for physical record keeping.',
            attachTo: { element: '.print-btn', on: 'left' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const lastStep = [
        {
            id: 'done',
            title: "🎉 That's all!",
            text: 'You now know how to manage the Lost & Found System. Click the <strong>?</strong> button in the header anytime to restart this tour.',
            buttons: [
                { text: 'Finish', action: () => tour.complete(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    let pageSteps = [];
    if (page === 'admin_profile.php') pageSteps = profileSteps;
    else if (page === 'list_found.php') pageSteps = listFoundSteps;
    else if (page === 'list_lost.php') pageSteps = listLostSteps;
    else if (page === 'admin_statistics.php') pageSteps = statisticsSteps;
    else if (page === 'admin_trail.php') pageSteps = trailSteps;
    else if (page === 'admin_users.php') pageSteps = usersSteps;
    else if (page === 'archive_items.php') pageSteps = archiveSteps;

    [...commonSteps, ...pageSteps, ...lastStep].forEach(step => tour.addStep(step));

    tour.start();
    localStorage.setItem('adminTourSeen_' + page, 'true');
}

function initAdminTour() {
    if (typeof Shepherd === 'undefined') {
        setTimeout(initAdminTour, 100);
        return;
    }
    const page = window.location.pathname.split('/').pop();
    if (!localStorage.getItem('adminTourSeen_' + page)) {
        setTimeout(() => startAdminTour(), 800);
    }
}

document.addEventListener('DOMContentLoaded', initAdminTour);