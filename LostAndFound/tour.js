function startTour() {
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
            title: '👋 Welcome to Lost & Found!',
            text: 'This is the Lost & Found System for Surau Ismail Kharofa. Let us show you around!',
            attachTo: { element: '.logo', on: 'right' },
            buttons: [
                { text: 'Skip Tour', action: () => tour.cancel(), classes: 'shepherd-btn-skip' },
                { text: 'Start Tour →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'sidebar',
            title: '📋 Navigation Menu',
            text: 'Use this sidebar to navigate between pages — Dashboard, Profile, Found Items, and Report Item.',
            attachTo: { element: '.nav-menu', on: 'right' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'font-toggle',
            title: '🔤 Text Size',
            text: 'Adjust the text size here. Choose S, M, or XL — whichever is most comfortable for you.',
            attachTo: { element: '.font-size-toggle', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'notification',
            title: '🔔 Notifications',
            text: 'This bell icon shows new notifications — such as updates on your reported items.',
            attachTo: { element: '.notification-btn', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'user-profile-header',
            title: '👤 Your Account',
            text: 'Click here to view your profile or log out.',
            attachTo: { element: '.user-profile', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const dashboardSteps = [
        {
            id: 'dashboard-stats',
            title: '📈 Item Statistics',
            text: 'These cards show how many items you have reported by status — Pending, Approved, Matched, and Claimed.',
            attachTo: { element: '.stats-card', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'dashboard-sections',
            title: '📂 Item Sections',
            text: 'Items are grouped by status. You can expand or collapse each section. Pending items can still be edited.',
            attachTo: { element: '.status-card', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const profileSteps = [
        {
            id: 'profile-pic',
            title: '📷 Profile Picture',
            text: 'Upload or change your profile picture here. Supported: JPG, PNG, GIF. Max size: 2MB.',
            attachTo: { element: '.profile-pic-wrapper', on: 'right' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'profile-info',
            title: 'ℹ️ Your Information',
            text: 'This shows your current name, contact number, email, and role.',
            attachTo: { element: '.profile-card', on: 'right' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'profile-form',
            title: '✏️ Edit Profile',
            text: 'Update your name, contact number, and password here. Leave password blank to keep the current one.',
            attachTo: { element: '.form-card', on: 'left' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const listFoundSteps = [
        {
            id: 'list-search',
            title: '🔍 Search Items',
            text: 'Search for a specific item by name, description, or location.',
            attachTo: { element: '.search-form', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'list-table',
            title: '📋 Found Items Table',
            text: 'This table shows all active found items in the surau — type, date, location, picture, and status.',
            attachTo: { element: 'table', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    const formSteps = [
        {
            id: 'form-type',
            title: '📍 Report Type',
            text: 'Select whether you are reporting a Found Item (you found it) or a Lost Item (you lost it).',
            attachTo: { element: '.item-type-selector', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'form-item-type',
            title: '📦 Type of Item',
            text: 'Select the item category. If not listed, choose "Other" and describe it.',
            attachTo: { element: '#type_item', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'form-datetime',
            title: '📅 Date & Time',
            text: "Enter when the item was found or lost. Today's date and current time are filled automatically.",
            attachTo: { element: '.form-row', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'form-location',
            title: '📌 Location',
            text: 'Select where in the surau the item was found or lost.',
            attachTo: { element: '#location', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'form-picture',
            title: '📸 Item Picture',
            text: 'Upload a photo of the item — this helps identify it faster. This field is optional.',
            attachTo: { element: '#picture', on: 'bottom' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'form-description',
            title: '📝 Description',
            text: 'Describe the item in detail — colour, brand, size, or special features. The more detail, the better!',
            attachTo: { element: '#description', on: 'top' },
            buttons: [
                { text: 'Back', action: () => tour.back(), classes: 'shepherd-btn-back' },
                { text: 'Next →', action: () => tour.next(), classes: 'shepherd-btn-next' }
            ]
        },
        {
            id: 'form-submit',
            title: '✅ Submit Report',
            text: 'Click this button to submit your report. Admin will review it and update the status.',
            attachTo: { element: '#submitBtn', on: 'top' },
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
            text: 'You now know how to use this system. Click the <strong>?</strong> button anytime to restart the tour.',
            buttons: [
                { text: 'Finish', action: () => tour.complete(), classes: 'shepherd-btn-next' }
            ]
        }
    ];

    let pageSteps = [];
    if (page === 'user_dashboard.php') pageSteps = dashboardSteps;
    else if (page === 'user_profile.php') pageSteps = profileSteps;
    else if (page === 'list_found.php') pageSteps = listFoundSteps;
    else if (page === 'form_item.php') pageSteps = formSteps;

    [...commonSteps, ...pageSteps, ...lastStep].forEach(step => tour.addStep(step));

    tour.start();
    localStorage.setItem('tourSeen_' + page, 'true');
}

function initTour() {
    if (typeof Shepherd === 'undefined') {
        setTimeout(initTour, 100);
        return;
    }
    const page = window.location.pathname.split('/').pop();
    const adminPages = ['admin_profile.php','admin_statistics.php','admin_trail.php','admin_users.php','archive_items.php','list_found.php','list_lost.php'];
    if (adminPages.includes(page)) return; 
    if (!localStorage.getItem('tourSeen_' + page)) {
        setTimeout(() => startTour(), 800);
    }
}

document.addEventListener('DOMContentLoaded', initTour);