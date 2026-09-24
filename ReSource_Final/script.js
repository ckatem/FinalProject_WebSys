const STORAGE = {


users: "resource_users_v2",

listings: "resource_listings_v2",

messages: "resource_messages_v2",

drafts: "resource_drafts_v2",

saved: "resource_saved_v2",

cart: "resource_cart_v2",

purchases: "resource_purchases_v2",

session: "resource_session_v2"


};

const FALLBACK_IMAGE =
"https://images.unsplash.com/photo-1503602642458-232111445657?w=900&auto=format&fit=crop&q=80";

const $ = (selector, parent = document) =>
parent.querySelector(selector);

const $$ = (selector, parent = document) =>
[...parent.querySelectorAll(selector)];

function readStorage(key, fallback) {


try {

    const raw = localStorage.getItem(key);

    return raw ? JSON.parse(raw) : fallback;

} catch {

    return fallback;

}


}

function writeStorage(key, value) {


localStorage.setItem(
    key,
    JSON.stringify(value)
);


}

function createId(prefix = "id") {


return `${prefix}_${Date.now()}_${Math.random()
    .toString(36)
    .slice(2, 8)}`;


}

function getUsers() {
return readStorage(STORAGE.users, []);
}

function normalizeUserRecord(user) {

    if (!user || typeof user !== "object") return null;

    const firstName = user.firstName ?? user.first_name ?? "";
    const middleName = user.middleName ?? user.middle_name ?? "";
    const lastName = user.lastName ?? user.last_name ?? "";
    const name = user.name ?? [firstName, middleName, lastName].filter(Boolean).join(" ");

    return {
        ...user,
        id: String(user.id ?? user.user_id ?? ""),
        firstName: firstName || "",
        middleName: middleName || "",
        lastName: lastName || "",
        name: name || [firstName, middleName, lastName].filter(Boolean).join(" ") || "TIPian",
        studentId: user.studentId ?? user.student_id ?? "",
        email: user.email ?? "",
        course: user.course ?? "",
        campus: user.campus ?? "",
        role: user.role ?? "student",
        profilePhoto: user.profilePhoto ?? user.profile_photo ?? "",
        privacy: user.privacy && typeof user.privacy === "object"
            ? user.privacy
            : {
                showProfilePhoto: user.privacy_photo == null ? true : Number(user.privacy_photo) === 0
            }
    };

}

function getListings() {
return readStorage(STORAGE.listings, []);
}

function normalizeMessage(message) {

    if (!message || typeof message !== "object") return null;

    const text =
        message.text ?? message.message ?? "";

    const senderId =
        message.senderId ?? message.sender_id;

    const receiverId =
        message.receiverId ?? message.receiver_id;

    const createdAt =
        message.createdAt ?? message.created_at ?? new Date().toISOString();

    return {
        ...message,
        id: message.id ?? message.message_id ?? createId("message"),
        senderId: senderId != null ? String(senderId) : null,
        receiverId: receiverId != null ? String(receiverId) : null,
        text: String(text ?? ""),
        createdAt
    };

}

function getMessages() {
    return (readStorage(STORAGE.messages, []) || [])
        .map(normalizeMessage)
        .filter(Boolean);
}

function getSaved() {
return readStorage(STORAGE.saved, []);
}

function getCart() {
return readStorage(STORAGE.cart, []);
}

function getPurchases() {
return readStorage(STORAGE.purchases, []);
}

function getCurrentUserId() {
return localStorage.getItem(STORAGE.session);
}

function getCurrentUser() {


const id = getCurrentUserId();

if (!id) return null;

return getUsers().find(
    user => String(user.id) === String(id)
) || null;


}

function saveUsers(users) {
writeStorage(STORAGE.users, users);
}

async function syncCurrentUserFromSession() {

    const userId = getCurrentUserId();

    if (!userId) return false;

    try {
        const response = await fetch("php/auth/session.php", { credentials: "same-origin" });

        if (!response.ok) return false;

        const result = await response.json();
        const serverUser = result?.user;

        if (!result?.logged_in || !serverUser) return false;

        const normalizedUser = normalizeUserRecord(serverUser);

        if (!normalizedUser) return false;

        const users = getUsers();
        const existingIndex = users.findIndex(account => String(account.id) === String(normalizedUser.id));
        const nextUsers = [...users];

        if (existingIndex >= 0) {
            nextUsers[existingIndex] = { ...nextUsers[existingIndex], ...normalizedUser };
        } else {
            nextUsers.push(normalizedUser);
        }

        saveUsers(nextUsers);

        return true;
    } catch {
        return false;
    }

}

function saveListings(listings) {
writeStorage(STORAGE.listings, listings);
}

function saveMessages(messages) {
    const normalized = (Array.isArray(messages) ? messages : [])
        .map(normalizeMessage)
        .filter(Boolean);

    writeStorage(STORAGE.messages, normalized);
}

function saveSaved(saved) {
writeStorage(STORAGE.saved, saved);
}

function saveCart(cart) {
writeStorage(STORAGE.cart, cart);
}

function savePurchases(purchases) {
writeStorage(STORAGE.purchases, purchases);
}

function updateCurrentUser(updates) {

const userId = getCurrentUserId();

if (!userId) return null;

const users = getUsers();
const matches = users.filter(user => user.id === userId);

if (!matches.length) return null;

const updatedUsers = users.map(user =>
    String(user.id) === String(userId)
        ? { ...user, ...updates }
        : user
);

saveUsers(updatedUsers);

return updatedUsers.find(user => user.id === userId) || null;

}

function getUserCart(userId = getCurrentUserId()) {

if (!userId) return [];

return getCart().filter(item => item.userId === userId);

}

function normalizeSavedEntries(entries, fallbackUserId = getCurrentUserId()) {

return (Array.isArray(entries) ? entries : [])
    .map(entry => {
        if (typeof entry === "string") {
            return {
                id: `saved_${entry}`,
                userId: fallbackUserId,
                listingId: entry,
                savedAt: null
            };
        }

        return entry;
    })
    .filter(entry => entry && entry.listingId);

}

function getUserSaved(userId = getCurrentUserId()) {

if (!userId) return [];

return normalizeSavedEntries(getSaved(), userId)
    .filter(entry => entry.userId === userId);

}

function escapeHTML(value = "") {


return String(value).replace(
    /[&<>"']/g,
    char => ({

        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"

    })[char]
);


}

function peso(value) {


return `₱${Number(value || 0)
    .toLocaleString("en-PH")}`;


}

function formatDate(iso) {


try {

    return new Date(iso).toLocaleDateString(
        "en-PH",
        {
            month: "short",
            day: "numeric",
            year: "numeric"
        }
    );

} catch {

    return "—";

}


}

function formatTime(iso) {


try {

    return new Date(iso).toLocaleTimeString(
        [],
        {
            hour: "2-digit",
            minute: "2-digit"
        }
    );

} catch {

    return "";

}


}

function toast(message, type = "success") {


const container = $("#toast-container");

if (!container) return;

const box = document.createElement("div");

box.className = `toast ${type}`;

box.textContent = message;

container.appendChild(box);

setTimeout(
    () => box.remove(),
    2800
);


}

function requireLogin() {


if (!getCurrentUser()) {

    toast(
        "Please log in first.",
        "error"
    );

    goToPage("login");

    switchAuthTab("login");

    return false;

}

return true;


}

/* ==========================================================
NAVIGATION
========================================================== */

function closeNavigationMenu() {

document.body.classList.remove("menu-open");

const button = $("#menu-toggle-btn");

if (button) {
    button.setAttribute("aria-expanded", "false");
    button.setAttribute("aria-label", "Open navigation menu");
}

}

function toggleNavigationMenu() {

const willOpen =
    !document.body.classList.contains("menu-open");

document.body.classList.toggle("menu-open", willOpen);

const button = $("#menu-toggle-btn");

if (button) {
    button.setAttribute("aria-expanded", String(willOpen));
    button.setAttribute(
        "aria-label",
        willOpen ? "Close navigation menu" : "Open navigation menu"
    );
}

}

function goToPage(pageKey) {


const target =
    $(`#page-${pageKey}`);

if (!target) return;

const validPages = new Set([
    "dashboard",
    "browse",
    "saved",
    "listing",
    "create",
    "profile",
    "messaging",
    "login"
]);

if (validPages.has(pageKey)) {
    const url = new URL(window.location.href);
    url.searchParams.set("page", pageKey);
    history.replaceState(null, "", url);
}

closeNavigationMenu();


$$(".page").forEach(page => {

    page.classList.remove("active");

});


$$(".nav-item").forEach(item => {

    item.classList.remove("active");

});


$$(".header-nav-link").forEach(item => {

    item.classList.remove("active");

});


target.classList.add("active");


const nav =
    $(`.nav-item[data-page="${pageKey}"]`);

if (nav) {
    nav.classList.add("active");
}


const headerNav =
    $(`.header-nav-link[data-header-page="${pageKey}"]`);

if (headerNav) {
    headerNav.classList.add("active");
}


if (pageKey === "dashboard")
    renderDashboard();

if (pageKey === "browse")
    renderBrowse();

if (pageKey === "saved")
    renderSavedItems();

if (pageKey === "listing")
    renderListingDetail();

if (pageKey === "create")
    prepareCreatePage();

if (pageKey === "profile")
    renderProfile();

if (pageKey === "messaging")
    renderMessaging();


window.scrollTo({
    top: 0,
    behavior: "smooth"
});


}

/* ==========================================================
AUTHENTICATION
========================================================== */

function switchAuthTab(tab) {


const loginTab =
    $("#tab-login");

const signupTab =
    $("#tab-signup");

const loginForm =
    $("#login-form");

const signupForm =
    $("#signup-form");


if (tab === "signup") {

    signupTab.classList.add("active");

    loginTab.classList.remove("active");

    signupForm.classList.remove("hidden");

    loginForm.classList.add("hidden");

} else {

    loginTab.classList.add("active");

    signupTab.classList.remove("active");

    loginForm.classList.remove("hidden");

    signupForm.classList.add("hidden");

}


}

function validateTipEmail(email) {


return email
    .toLowerCase()
    .endsWith("@tip.edu.ph");


}

function validateStudentId(studentId) {


return /^[A-Za-z0-9-]{5,20}$/.test(
    studentId.trim()
);


}

function getUserDisplayName(user) {


if (!user) return "TIPian";

if (user.name) return user.name;

return [
    user.firstName,
    user.middleName,
    user.lastName
]
    .filter(Boolean)
    .join(" ");


}

function getUserFirstName(user) {


if (!user) return "TIPian";

return (
    user.firstName ||
    user.name?.split(" ")[0] ||
    "TIPian"
);


}

function getNotificationSettings(user) {

return {
    messages: true,
    listingActivity: true,
    ...(user?.notifications || {})
};

}

function getPrivacySettings(user) {

return {
    showProfilePhoto: true,
    showCourse: true,
    ...(user?.privacy || {})
};

}

function getRatingSummary(user, role) {

const rating = user?.ratings?.[role] || {};
const score = Number(rating.score);

return {
    score: Number.isFinite(score) && score > 0
        ? score.toFixed(1)
        : "5.0",
    count: Math.max(0, Number(rating.count) || 0)
};

}

function formatRating(user, role, compact = false) {

const rating = getRatingSummary(user, role);
const label = role === "seller" ? "Seller" : "Buyer";

return compact
    ? `★ ${rating.score} ${label}`
    : `★ ${rating.score} ${label} rating`;

}

function avatarMarkup(user, respectPrivacy = false) {

const canShowPhoto =
    user?.profilePhoto &&
    (!respectPrivacy || getPrivacySettings(user).showProfilePhoto);

if (!canShowPhoto) return "👤";

return `<img src="${escapeHTML(user.profilePhoto)}" alt="${escapeHTML(getUserFirstName(user))}'s profile photo">`;

}

function openImageViewer(src, caption = "Image preview") {

const lightbox = $("#image-lightbox");
const image = $("#image-lightbox-image");
const text = $("#image-lightbox-caption");

if (!lightbox || !image || !src) return;

image.src = src;
image.alt = caption;
text.textContent = caption;
lightbox.classList.remove("hidden");
document.body.classList.add("image-viewer-open");

}

function closeImageViewer() {

const lightbox = $("#image-lightbox");

if (!lightbox) return;

lightbox.classList.add("hidden");
document.body.classList.remove("image-viewer-open");

}

/* ==========================================================
TOP HEADER
========================================================== */

function renderTopHeader() {


const area =
    $("#top-header-actions");

if (!area) return;


const user =
    getCurrentUser();


if (user) {

    area.innerHTML = `

        <button
            class="header-login-btn header-control-btn"
            id="top-logout-btn">

            Log Out

        </button>

        <button
            class="header-profile-btn header-control-btn"
            id="top-profile-btn">

            <span class="profile-button-avatar" aria-hidden="true">${avatarMarkup(user)}</span>
            <span>${escapeHTML(
                getUserFirstName(user)
            )}</span>

        </button>

    `;


    $("#top-profile-btn")
        .addEventListener(
            "click",
            () => goToPage("profile")
        );


    $("#top-logout-btn")
        .addEventListener(
            "click",
            logout
        );

} else {

    area.innerHTML = `

        <button
            class="header-login-btn header-control-btn"
            id="top-login-btn">

            Log In

        </button>

        <button
            class="header-signup-btn header-control-btn"
            id="top-signup-btn">

            Sign Up

        </button>

        <button
            class="header-profile-btn header-control-btn"
            id="top-profile-btn">

            <span class="profile-button-avatar" aria-hidden="true">👤</span>
            <span>Profile</span>

        </button>

    `;


    $("#top-profile-btn")
        .addEventListener(
            "click",
            () => {
                if (requireLogin()) {
                    goToPage("profile");
                }
            }
        );


    $("#top-login-btn")
        .addEventListener(
            "click",
            () => {

                goToPage("login");

                switchAuthTab("login");

            }
        );


    $("#top-signup-btn")
        .addEventListener(
            "click",
            () => {

                goToPage("login");

                switchAuthTab("signup");

            }
        );

}


}

function logout() {

    localStorage.removeItem(
        STORAGE.session
    );

    selectedConversationUserId = null;

    toast(
        "You have been logged out."
    );

    renderAll();

    goToPage("login");

    switchAuthTab("login");

}
/* ==========================================================
DASHBOARD
========================================================== */

function renderDashboard() {


renderTopHeader();


const user =
    getCurrentUser();


$("#welcome-title").textContent =
    user
        ? `Welcome back, ${getUserFirstName(user)}!`
        : "Welcome to ReSource!";


$("#welcome-sub").textContent =
    user

        ? "Find what you need, list what you have, and connect with fellow TIPians."

        : "Find useful campus resources, discover listings, and connect with fellow TIPians. Sign up to start buying and selling.";


const listings =
    [...getListings()]
        .filter(item => item.status !== "sold")
        .sort(
            (a, b) =>
                new Date(b.createdAt) -
                new Date(a.createdAt)
        )
        .slice(0, 4);


renderItemGrid(
    $("#recently-listed-grid"),
    listings,
    false
);


}

/* ==========================================================
ITEM GRID
========================================================== */

function renderItemGrid(
container,
items,
highlight = false
) {


if (!container) return;


container.innerHTML = "";


if (!items.length) {

    container.innerHTML = `

        <div
            class="empty-state"
            style="grid-column:1 / -1;">

            <div class="empty-icon">
                +
            </div>

            <h3>
                No listings yet
            </h3>

            <p>
                ReSource is currently waiting for its first listing.
                Be the first TIPian to post something.
            </p>

            <button
                class="btn btn-yellow"
                data-goto="create">

                Create Listing

            </button>

        </div>

    `;


    const button =
        $('button[data-goto="create"]', container);


    if (button) {

        button.addEventListener(
            "click",
            () => {

                if (!requireLogin()) return;

                goToPage("create");

            }
        );

    }

    return;

}


items.forEach(item => {

    const card =
        document.createElement("div");


    card.className =
        "item-card";


    card.innerHTML = `

        <div class="item-thumb">

            <img
                src="${escapeHTML(
                    item.image ||
                    FALLBACK_IMAGE
                )}"
                alt="${escapeHTML(item.title)}"
                loading="lazy">

        </div>


        <div class="item-title">
            ${escapeHTML(item.title)}
        </div>


        <div class="item-price">
            ${peso(item.price)}
        </div>


        <div class="item-user">
            👤 ${escapeHTML(
                item.sellerName ||
                "TIPian"
            )}
        </div>


        <div class="item-meta-row">

            <span class="item-category">
                ${escapeHTML(
                    item.category ||
                    "Other"
                )}
            </span>

            <span class="item-category">
                ${escapeHTML(
                    item.condition ||
                    ""
                )}
            </span>

        </div>

    `;


    const previewImage = $("img", card);

    previewImage.addEventListener(
        "click",
        event => {
            event.stopPropagation();
            openImageViewer(
                previewImage.src,
                item.title || "Listing image"
            );
        }
    );

    card.addEventListener(
        "click",
        () => {

            localStorage.setItem(
                "resource_selected_listing",
                item.id
            );

            goToPage("listing");

        }
    );


    container.appendChild(card);

});


}

/* ==========================================================
BROWSE
========================================================== */

function getFilteredListings() {


let listings =
    [...getListings()];


const query =
    ($("#browse-search-input")?.value || "")
        .trim()
        .toLowerCase();


const campus =
    $("#filter-campus")?.value || "";


const department =
    $("#filter-department")?.value || "";


const courseCode =
    ($("#filter-coursecode")?.value || "")
        .trim()
        .toLowerCase();


const category =
    $("#filter-category")?.value || "";


const condition =
    $("#filter-condition")?.value || "";


const min =
    $("#filter-min")?.value
        ? Number($("#filter-min").value)
        : 0;


const max =
    $("#filter-max")?.value
        ? Number($("#filter-max").value)
        : Infinity;


const sort =
    $("#filter-sort")?.value ||
    "newest";


listings =
    listings.filter(item => {

        const searchable = [

            item.title,
            item.description,
            item.category,
            item.courseCode,
            item.department,
            item.sellerName

        ]
            .join(" ")
            .toLowerCase();


        const matchesQuery =
            !query ||
            searchable.includes(query);


        return item.status !== "sold"

            && matchesQuery

            && (!campus ||
                item.campus === campus)

            && (!department ||
                item.department
                    ?.toLowerCase() ===
                department.toLowerCase())

            && (!courseCode ||
                item.courseCode
                    ?.toLowerCase()
                    .includes(courseCode))

            && (!category ||
                item.category === category)

            && (!condition ||
                item.condition === condition)

            && Number(item.price) >= min

            && Number(item.price) <= max;

    });


if (sort === "price-low") {

    listings.sort(
        (a, b) =>
            Number(a.price) -
            Number(b.price)
    );

} else if (sort === "price-high") {

    listings.sort(
        (a, b) =>
            Number(b.price) -
            Number(a.price)
    );

} else if (sort === "title") {

    listings.sort(
        (a, b) =>
            a.title.localeCompare(b.title)
    );

} else {

    listings.sort(
        (a, b) =>
            new Date(b.createdAt) -
            new Date(a.createdAt)
    );

}


return listings;


}

function renderBrowse() {


const listings =
    getFilteredListings();


renderItemGrid(
    $("#browse-grid"),
    listings,
    true
);


$("#results-count-num")
    .textContent = listings.length;


}

function resetFilters() {


[
    "filter-campus",
    "filter-department",
    "filter-category",
    "filter-condition",
    "filter-sort"
].forEach(id => {

    const el = $(`#${id}`);

    if (el) {

        el.value =
            id === "filter-sort"
                ? "newest"
                : "";

    }

});


[
    "filter-coursecode",
    "filter-min",
    "filter-max",
    "browse-search-input"
].forEach(id => {

    const el = $(`#${id}`);

    if (el) el.value = "";

});


renderBrowse();


}

/* ==========================================================
CART
========================================================== */

function updateCartBadge() {

const badge = $("#nav-cart-count");

if (!badge) return;

const quantity = getUserCart().reduce(
    (total, item) => total + Math.max(0, Number(item.quantity) || 0),
    0
);

badge.textContent = quantity;
badge.classList.toggle("hidden", quantity === 0);

}

function getCartRowsForUser(user) {

const listings = new Map(
    getListings().map(listing => [listing.id, listing])
);

return getUserCart(user.id)
    .map(entry => ({
        entry,
        listing: listings.get(entry.listingId)
    }))
    .filter(({ entry, listing }) =>
        listing &&
        listing.status !== "sold" &&
        listing.sellerId !== user.id &&
        Number(entry.quantity) > 0
    );

}

function addListingToCart(listingId) {

if (!requireLogin()) return;

const user = getCurrentUser();
const listing = getListings().find(item => item.id === listingId);

if (!listing || listing.status === "sold") {
    toast("This listing is no longer available.", "error");
    renderListingDetail();
    return;
}

if (listing.sellerId === user.id) {
    toast("You cannot add your own listing to the cart.", "error");
    return;
}

const cart = getCart();
const index = cart.findIndex(item =>
    item.userId === user.id && item.listingId === listingId
);

if (index >= 0) {
    cart[index].quantity = Math.min(99, Number(cart[index].quantity || 0) + 1);
} else {
    cart.push({
        id: createId("cart"),
        userId: user.id,
        listingId,
        quantity: 1,
        addedAt: new Date().toISOString()
    });
}

saveCart(cart);
updateCartBadge();
renderListingDetail();
toast("Item added to your cart.");

}

function changeCartQuantity(listingId, amount) {

const userId = getCurrentUserId();

if (!userId) return;

let cart = getCart();
const index = cart.findIndex(item =>
    item.userId === userId && item.listingId === listingId
);

if (index < 0) return;

const quantity = Math.min(99, Number(cart[index].quantity || 0) + amount);

if (quantity <= 0) {
    cart = cart.filter((item, itemIndex) => itemIndex !== index);
} else {
    cart[index].quantity = quantity;
}

saveCart(cart);
updateCartBadge();
renderSavedItems();
renderListingDetail();

}

function removeCartItem(listingId) {

const userId = getCurrentUserId();

saveCart(
    getCart().filter(item =>
        !(item.userId === userId && item.listingId === listingId)
    )
);

updateSavedBadge();
renderCart();
renderListingDetail();

}

function renderCart() {

const user = getCurrentUser();
const warning = $("#cart-login-warning");
const content = $("#cart-content");

if (!warning || !content) return;

if (!user) {
    warning.classList.remove("hidden");
    content.classList.add("hidden");
    updateCartBadge();
    return;
}

warning.classList.add("hidden");
content.classList.remove("hidden");

const rows = getCartRowsForUser(user);
const validIds = new Set(rows.map(({ entry }) => entry.id));
const cart = getCart();
const cleanedCart = cart.filter(item =>
    item.userId !== user.id || validIds.has(item.id)
);

if (cleanedCart.length !== cart.length) saveCart(cleanedCart);

const itemsArea = $("#cart-items");
const summary = $(".cart-summary", content);
const quantity = rows.reduce(
    (total, { entry }) => total + Number(entry.quantity || 0),
    0
);
const total = rows.reduce(
    (sum, { entry, listing }) => sum + Number(listing.price || 0) * Number(entry.quantity || 0),
    0
);

$("#cart-summary-quantity").textContent = quantity;
$("#cart-summary-total").textContent = peso(total);
summary.classList.toggle("hidden", rows.length === 0);

if (!rows.length) {
    itemsArea.innerHTML = `
        <div class="empty-state cart-empty-state">
            <div class="empty-icon">🛒</div>
            <h3>Your cart is empty</h3>
            <p>Add listings here, adjust their quantities, and check out when you are ready.</p>
            <button class="btn btn-yellow" id="cart-browse-btn">Browse Listings</button>
        </div>
    `;

    $("#cart-browse-btn").addEventListener("click", () => goToPage("browse"));
    updateCartBadge();
    return;
}

itemsArea.innerHTML = rows.map(({ entry, listing }) => `
    <article class="cart-row" data-listing-id="${escapeHTML(listing.id)}">
        <button class="cart-image-button" type="button" data-cart-preview="${escapeHTML(listing.image || FALLBACK_IMAGE)}" data-cart-caption="${escapeHTML(listing.title)}">
            <img src="${escapeHTML(listing.image || FALLBACK_IMAGE)}" alt="${escapeHTML(listing.title)}">
        </button>
        <div class="cart-item-info">
            <button class="cart-item-title" type="button" data-cart-open="${escapeHTML(listing.id)}">${escapeHTML(listing.title)}</button>
            <p>${escapeHTML(listing.category || "Other")} · ${escapeHTML(listing.condition || "")}</p>
            <span>Sold by ${escapeHTML(listing.sellerName || "TIPian")}</span>
        </div>
        <div class="cart-quantity-control" aria-label="Quantity for ${escapeHTML(listing.title)}">
            <button type="button" data-cart-action="decrease" data-listing-id="${escapeHTML(listing.id)}" aria-label="Decrease quantity">−</button>
            <strong>${Number(entry.quantity)}</strong>
            <button type="button" data-cart-action="increase" data-listing-id="${escapeHTML(listing.id)}" aria-label="Increase quantity">+</button>
        </div>
        <div class="cart-row-total">
            <strong>${peso(Number(listing.price || 0) * Number(entry.quantity || 0))}</strong>
            <button type="button" data-cart-action="remove" data-listing-id="${escapeHTML(listing.id)}">Remove</button>
        </div>
    </article>
`).join("");

$$('[data-cart-preview]', itemsArea).forEach(button => {
    button.addEventListener("click", () =>
        openImageViewer(button.dataset.cartPreview, button.dataset.cartCaption)
    );
});

$$('[data-cart-open]', itemsArea).forEach(button => {
    button.addEventListener("click", () => {
        localStorage.setItem("resource_selected_listing", button.dataset.cartOpen);
        goToPage("listing");
    });
});

$$('[data-cart-action]', itemsArea).forEach(button => {
    button.addEventListener("click", () => {
        const { cartAction, listingId } = button.dataset;

        if (cartAction === "increase") changeCartQuantity(listingId, 1);
        if (cartAction === "decrease") changeCartQuantity(listingId, -1);
        if (cartAction === "remove") removeCartItem(listingId);
    });
});

updateCartBadge();

}

function checkoutCart() {

const user = getCurrentUser();

if (!user) {
    requireLogin();
    return;
}

const rows = getCartRowsForUser(user);

if (!rows.length) {
    toast("Your cart is empty.", "error");
    return;
}

if (!confirm("Complete this demo purchase? Your purchases and sold items will be saved in this browser.")) return;

const listings = getListings();
const purchases = getPurchases();
const purchasedAt = new Date().toISOString();

rows.forEach(({ entry, listing }) => {
    const index = listings.findIndex(item => item.id === listing.id);

    if (index < 0 || listings[index].status === "sold") return;

    listings[index] = {
        ...listings[index],
        status: "sold",
        buyerId: user.id,
        soldAt: purchasedAt,
        soldQuantity: Number(entry.quantity || 1)
    };

    purchases.push({
        id: createId("purchase"),
        buyerId: user.id,
        sellerId: listing.sellerId,
        listingId: listing.id,
        title: listing.title,
        image: listing.image || FALLBACK_IMAGE,
        price: Number(listing.price || 0),
        quantity: Number(entry.quantity || 1),
        purchasedAt
    });
});

saveListings(listings);
savePurchases(purchases);
saveCart(getCart().filter(item => item.userId !== user.id));
updateCartBadge();
renderAll();
goToPage("profile");
showProfileTab("history");
toast("Purchase completed. Your history and sold items are updated.");

}

/* ==========================================================
SAVED ITEMS
========================================================== */

function updateSavedBadge() {

const badge = $("#nav-saved-count");

if (!badge) return;

const count = getUserSaved().length;

badge.textContent = count;
badge.classList.toggle("hidden", count === 0);

}

function toggleSavedItem(listingId) {

if (!requireLogin()) return;

const user = getCurrentUser();
const listing = getListings().find(item => item.id === listingId);

if (!listing || listing.status === "sold") {
    toast("This listing is no longer available.", "error");
    renderListingDetail();
    return;
}

const saved = normalizeSavedEntries(getSaved(), user.id);
const index = saved.findIndex(entry =>
    entry.userId === user.id && entry.listingId === listingId
);

if (index >= 0) {
    saved.splice(index, 1);
    toast("Removed from Saved Items.");
} else {
    saved.push({
        id: createId("saved"),
        userId: user.id,
        listingId,
        savedAt: new Date().toISOString()
    });
    toast("Item saved.");
}

saveSaved(saved);
updateSavedBadge();
renderSavedItems();
renderListingDetail();

}

function removeSavedItem(listingId) {

const userId = getCurrentUserId();

saveSaved(
    normalizeSavedEntries(getSaved(), userId)
        .filter(entry =>
            !(entry.userId === userId && entry.listingId === listingId)
        )
);

updateSavedBadge();
renderSavedItems();
renderListingDetail();
toast("Removed from Saved Items.");

}

function renderSavedItems() {

const user = getCurrentUser();
const warning = $("#saved-login-warning");
const content = $("#saved-content");
const grid = $("#saved-items-grid");

if (!warning || !content || !grid) return;

if (!user) {
    warning.classList.remove("hidden");
    content.classList.add("hidden");
    updateSavedBadge();
    return;
}

warning.classList.add("hidden");
content.classList.remove("hidden");

const listings = new Map(getListings().map(listing => [listing.id, listing]));
const rows = getUserSaved(user.id)
    .map(entry => ({ entry, listing: listings.get(entry.listingId) }))
    .filter(({ listing }) => listing && listing.status !== "sold");

if (!rows.length) {
    grid.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1;">
            <div class="empty-icon">🔖</div>
            <h3>No saved items yet</h3>
            <p>Tap Save Item on a listing to keep it here.</p>
            <button class="btn btn-yellow" id="saved-browse-btn">Browse Listings</button>
        </div>
    `;

    $("#saved-browse-btn").addEventListener("click", () => goToPage("browse"));
    updateSavedBadge();
    return;
}

grid.innerHTML = rows.map(({ listing }) => `
    <article class="saved-item-card">
        <button
            class="saved-item-image"
            type="button"
            data-saved-preview="${escapeHTML(listing.image || FALLBACK_IMAGE)}"
            data-saved-caption="${escapeHTML(listing.title)}">
            <img src="${escapeHTML(listing.image || FALLBACK_IMAGE)}" alt="${escapeHTML(listing.title)}">
        </button>
        <div class="saved-item-content">
            <button class="saved-item-title" type="button" data-saved-open="${escapeHTML(listing.id)}">${escapeHTML(listing.title)}</button>
            <p>${peso(listing.price)} · ${escapeHTML(listing.condition || "")}</p>
            <button class="saved-remove-btn" type="button" data-saved-remove="${escapeHTML(listing.id)}">Remove</button>
        </div>
    </article>
`).join("");

$$('[data-saved-preview]', grid).forEach(button => {
    button.addEventListener("click", () =>
        openImageViewer(button.dataset.savedPreview, button.dataset.savedCaption)
    );
});

$$('[data-saved-open]', grid).forEach(button => {
    button.addEventListener("click", () => {
        localStorage.setItem("resource_selected_listing", button.dataset.savedOpen);
        goToPage("listing");
    });
});

$$('[data-saved-remove]', grid).forEach(button => {
    button.addEventListener("click", () => removeSavedItem(button.dataset.savedRemove));
});

updateSavedBadge();

}

function markListingAsSold(listingId) {

const seller = getCurrentUser();
const listings = getListings();
const index = listings.findIndex(item => item.id === listingId);
const listing = listings[index];

if (!seller || !listing || listing.sellerId !== seller.id || listing.status === "sold") return;

const buyerEmail = prompt(
    "Enter the buyer's TIP email address to record this sale in their Purchase History:"
);

if (buyerEmail === null) return;

const buyer = getUsers().find(user =>
    user.email?.toLowerCase() === buyerEmail.trim().toLowerCase()
);

if (!buyer || buyer.id === seller.id) {
    toast("Enter the email of another registered TIP buyer.", "error");
    return;
}

if (!confirm(`Mark \"${listing.title}\" as sold to ${getUserDisplayName(buyer)}?`)) return;

const purchasedAt = new Date().toISOString();

listings[index] = {
    ...listing,
    status: "sold",
    buyerId: buyer.id,
    soldAt: purchasedAt,
    soldQuantity: 1
};

const purchases = getPurchases();
purchases.push({
    id: createId("purchase"),
    buyerId: buyer.id,
    sellerId: seller.id,
    listingId: listing.id,
    title: listing.title,
    image: listing.image || FALLBACK_IMAGE,
    price: Number(listing.price || 0),
    quantity: 1,
    purchasedAt
});

saveListings(listings);
savePurchases(purchases);
renderAll();
goToPage("profile");
showProfileTab("sold");
toast("Listing marked as sold and added to the buyer's Purchase History.");

}

/* ==========================================================
LISTING DETAIL
========================================================== */

function renderListingDetail() {


const listingId =
    localStorage.getItem(
        "resource_selected_listing"
    );


const listing =
    getListings().find(
        item => item.id === listingId
    );


const empty =
    $("#listing-detail-empty");


const content =
    $("#listing-detail-content");


if (!listing) {

    empty.classList.remove("hidden");

    content.classList.add("hidden");

    return;

}


empty.classList.add("hidden");

content.classList.remove("hidden");


const currentUser =
    getCurrentUser();


const isOwner =
    currentUser &&
    currentUser.id === listing.sellerId;


const isSold = listing.status === "sold";


const savedItem = currentUser
    ? getUserSaved(currentUser.id).find(item => item.listingId === listing.id)
    : null;


const seller = getUsers().find(user => user.id === listing.sellerId);


$("#detail-image").src =
    listing.image ||
    FALLBACK_IMAGE;


$("#detail-image").alt =
    listing.title;


$("#detail-image").onclick =
    () => openImageViewer(
        $("#detail-image").src,
        listing.title || "Listing image"
    );


$("#detail-tags").innerHTML = [

    listing.courseCode
        ? `<span class="tag">${escapeHTML(
            listing.courseCode
        )}</span>`
        : "",

    listing.department
        ? `<span class="tag">${escapeHTML(
            listing.department
        )}</span>`
        : "",

    `<span class="tag">${escapeHTML(
        listing.category ||
        "Other"
    )}</span>`,

    `<span class="tag">${escapeHTML(
        listing.condition ||
        ""
    )}</span>`

].join("");


$("#detail-title").textContent =
    listing.title;


$("#detail-price").textContent =
    peso(listing.price);


$("#detail-description").textContent =
    listing.description ||
    "No description provided.";


$("#detail-campus").textContent =
    listing.campus ||
    "—";


$("#detail-course").textContent =
    listing.courseCode ||
    "—";


$("#detail-date").textContent =
    formatDate(
        listing.createdAt
    );


$("#detail-seller-name").textContent =
    listing.sellerName ||
    "TIPian";


$("#detail-seller-avatar").innerHTML =
    avatarMarkup(seller, true);


$("#detail-seller-rating").textContent =
    formatRating(seller, "seller");


$("#detail-seller-active").textContent =
    isSold

        ? "This item has been sold"

        : listing.sellerId ===
        getCurrentUserId()

        ? "This is your listing"

        : "TIPian seller";


$("#save-item-btn").textContent =
    isSold
        ? "Sold"
        : isOwner
            ? "Your Listing"
            : savedItem
                ? "Saved ✓"
                : "Save Item";


$("#save-item-btn").disabled =
    !!isOwner ||
    isSold;


$("#mark-sold-btn")
    .classList.toggle(
        "hidden",
        !isOwner || isSold
    );


$("#mark-sold-btn").disabled =
    !isOwner ||
    isSold;


$("#delete-listing-btn")
    .classList.toggle(
        "hidden",
        !isOwner
    );


$("#message-seller-btn").disabled =
    !!isOwner ||
    isSold;


const images =
    Array.isArray(listing.images) &&
    listing.images.length

        ? listing.images

        : [
            listing.image ||
            FALLBACK_IMAGE
        ];


$("#listing-thumbs").innerHTML =
    images.map(
        (src, index) => `

            <div class="thumb">

                <img
                    src="${escapeHTML(src)}"
                    alt="Thumbnail ${index + 1}"
                    data-image="${escapeHTML(src)}">

            </div>

        `
    ).join("");


$$("#listing-thumbs .thumb img")
    .forEach(img => {

        img.addEventListener(
            "click",
            () => {

                $("#detail-image").src =
                    img.dataset.image;

            }
        );

    });


}

/* ==========================================================
CREATE LISTING
========================================================== */

let selectedListingImages = [];
let selectedListingFiles = [];

function prepareCreatePage() {


const user =
    getCurrentUser();


const warning =
    $("#create-login-warning");


const form =
    $("#create-listing-form");


if (!user) {

    warning.classList.remove("hidden");

    form.classList.add("hidden");

} else {

    warning.classList.add("hidden");

    form.classList.remove("hidden");


    if (!$("#create-campus").value) {

        $("#create-campus").value =
            user.campus ||
            "Manila";

    }

}


}

function resetCreateForm() {


$("#create-listing-form")?.reset();

selectedListingImages = [];
selectedListingFiles = [];


$$(".photo-slot")
    .forEach(slot => {

        slot.innerHTML = `

            <input
                type="file"
                accept="image/*"
                class="photo-input"
                hidden>

            <span class="plus">
                +
            </span>

        `;

    });


bindPhotoInputs();


}

function readImageAsDataURL(file) {


return new Promise(
    (resolve, reject) => {

        const reader =
            new FileReader();


        reader.onload =
            () => resolve(
                reader.result
            );


        reader.onerror =
            reject;


        reader.readAsDataURL(file);

    }
);


}

async function handlePhotoChange(input) {


const file =
    input.files?.[0];


if (!file) return;


if (
    file.size >
    1.5 * 1024 * 1024
) {

    toast(
        "Please choose images smaller than 1.5 MB each.",
        "error"
    );

    input.value = "";

    return;

}


try {

    const dataUrl =
        await readImageAsDataURL(file);


    const slot =
        input.closest(".photo-slot");


    slot.innerHTML = `

        <img
            src="${dataUrl}"
            alt="Selected image">

        <input
            type="file"
            accept="image/*"
            class="photo-input"
            hidden>

    `;


    const newInput =
        $(".photo-input", slot);


    newInput.addEventListener(
        "change",
        () =>
            handlePhotoChange(newInput)
    );


    $("img", slot).addEventListener(
        "click",
        event => {
            event.preventDefault();
            event.stopPropagation();
            openImageViewer(
                dataUrl,
                "Listing photo preview"
            );
        }
    );


    selectedListingImages
        .push(dataUrl);

    selectedListingFiles
        .push(file);


} catch {

    toast(
        "Could not read that image.",
        "error"
    );

}


}

function bindPhotoInputs() {


$$(".photo-input")
    .forEach(input => {

        input.addEventListener(
            "change",
            () =>
                handlePhotoChange(input)
        );

    });


}

/* ==========================================================
PROFILE
========================================================== */

function renderProfile() {


const user =
    getCurrentUser();


const warning =
    $("#profile-login-warning");


const content =
    $("#profile-content");


const logoutBtn =
    $("#logout-btn");


if (!user) {

    warning.classList.remove("hidden");

    content.classList.add("hidden");

    logoutBtn.classList.add("hidden");

    return;

}


warning.classList.add("hidden");

content.classList.remove("hidden");

logoutBtn.classList.remove("hidden");


$("#profile-avatar").innerHTML =
    avatarMarkup(user);


$("#profile-name").textContent =
    getUserDisplayName(user);


$("#profile-role").textContent =
    "TIP Student";


$("#profile-course").textContent =
    `${user.course || "Student"} • ReSource Member`;


$("#profile-campus").textContent =
    `📍 ${user.campus}`;


$("#profile-student-id").textContent =
    `ID: ${user.studentId}`;


$("#profile-member-since").textContent =
    `🗓 Member since ${formatDate(
        user.createdAt
    )}`;


$("#profile-seller-rating").textContent =
    formatRating(user, "seller", true);


$("#profile-buyer-rating").textContent =
    formatRating(user, "buyer", true);


const notifications = getNotificationSettings(user);
const privacy = getPrivacySettings(user);

$("#profile-course-input").value = user.course || "";
$("#notify-messages").checked = notifications.messages;
$("#notify-listings").checked = notifications.listingActivity;
$("#privacy-photo").checked = privacy.showProfilePhoto;
$("#privacy-course").checked = privacy.showCourse;


const myListings =
    getListings()

        .filter(
            listing =>
                listing.sellerId === user.id &&
                listing.status !== "sold"
        )

        .sort(
            (a, b) =>
                new Date(b.createdAt) -
                new Date(a.createdAt)
        );


renderProfileListings(
    myListings
);


renderProfileSoldItems(
    getListings()
        .filter(listing =>
            listing.sellerId === user.id &&
            listing.status === "sold"
        )
        .sort((a, b) =>
            new Date(b.soldAt || b.createdAt) -
            new Date(a.soldAt || a.createdAt)
        )
);


renderPurchaseHistory(user);


}

function renderProfileListings(listings) {


const grid =
    $("#profile-listings-grid");


grid.innerHTML = "";


if (!listings.length) {

    grid.innerHTML = `

        <div
            class="empty-state"
            style="grid-column:1 / -1;">

            <div class="empty-icon">
                +
            </div>

            <h3>
                No listings yet
            </h3>

            <p>
                You have not posted anything yet.
            </p>

            <button
                class="btn btn-yellow"
                id="profile-create-btn">

                Create your first listing

            </button>

        </div>

    `;


    $("#profile-create-btn")
        .addEventListener(
            "click",
            () =>
                goToPage("create")
        );


    return;

}


listings.forEach(listing => {

    const card =
        document.createElement("div");


    card.className =
        "listing-card";


    card.innerHTML = `

        <img
            src="${escapeHTML(
                listing.image ||
                FALLBACK_IMAGE
            )}"
            alt="${escapeHTML(
                listing.title
            )}">


        <div class="lc-title">
            ${escapeHTML(
                listing.title
            )}
        </div>


        <div class="lc-bottom">

            <span>
                ${peso(listing.price)}
            </span>

            <span class="status active">
                Active
            </span>

        </div>

    `;


    const profileImage = $("img", card);

    profileImage.addEventListener(
        "click",
        event => {
            event.stopPropagation();
            openImageViewer(profileImage.src, listing.title || "Listing image");
        }
    );


    card.addEventListener(
        "click",
        () => {

            localStorage.setItem(
                "resource_selected_listing",
                listing.id
            );

            goToPage("listing");

        }
    );


    grid.appendChild(card);

});


}

function renderProfileSoldItems(listings) {

const grid = $("#profile-sold-grid");

if (!grid) return;

if (!listings.length) {
    grid.innerHTML = `
        <div class="empty-state" style="grid-column:1 / -1;">
            <div class="empty-icon">✓</div>
            <h3>No sold items yet</h3>
            <p>Listings marked as sold through checkout will appear here.</p>
        </div>
    `;
    return;
}

grid.innerHTML = "";

listings.forEach(listing => {
    const card = document.createElement("div");
    const soldDate = formatDate(listing.soldAt || listing.createdAt);

    card.className = "listing-card";
    card.innerHTML = `
        <img src="${escapeHTML(listing.image || FALLBACK_IMAGE)}" alt="${escapeHTML(listing.title)}">
        <div class="lc-title">${escapeHTML(listing.title)}</div>
        <div class="lc-bottom">
            <span>${peso(listing.price)}</span>
            <span class="status sold">Sold ${escapeHTML(soldDate)}</span>
        </div>
    `;

    const image = $("img", card);
    image.addEventListener("click", event => {
        event.stopPropagation();
        openImageViewer(image.src, listing.title || "Sold listing image");
    });

    card.addEventListener("click", () => {
        localStorage.setItem("resource_selected_listing", listing.id);
        goToPage("listing");
    });

    grid.appendChild(card);
});

}

function renderPurchaseHistory(user) {

const area = $("#purchase-history-list");

if (!area) return;

const purchases = getPurchases()
    .filter(purchase => purchase.buyerId === user.id)
    .sort((a, b) => new Date(b.purchasedAt) - new Date(a.purchasedAt));

if (!purchases.length) {
    area.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">◫</div>
            <h3>No purchase history yet</h3>
            <p>Completed cart purchases will be recorded here.</p>
        </div>
    `;
    return;
}

area.innerHTML = purchases.map(purchase => `
    <article class="purchase-history-row">
        <button
            class="purchase-image-button"
            type="button"
            data-purchase-preview="${escapeHTML(purchase.image || FALLBACK_IMAGE)}"
            data-purchase-caption="${escapeHTML(purchase.title)}">
            <img src="${escapeHTML(purchase.image || FALLBACK_IMAGE)}" alt="${escapeHTML(purchase.title)}">
        </button>
        <div class="purchase-history-info">
            <button type="button" class="purchase-title" data-purchase-open="${escapeHTML(purchase.listingId)}">${escapeHTML(purchase.title)}</button>
            <p>${Number(purchase.quantity || 1)} item${Number(purchase.quantity || 1) === 1 ? "" : "s"} · ${formatDate(purchase.purchasedAt)}</p>
        </div>
        <div class="purchase-history-total">
            <strong>${peso(Number(purchase.price || 0) * Number(purchase.quantity || 1))}</strong>
            <span>Completed</span>
        </div>
    </article>
`).join("");

$$('[data-purchase-preview]', area).forEach(button => {
    button.addEventListener("click", () =>
        openImageViewer(button.dataset.purchasePreview, button.dataset.purchaseCaption)
    );
});

$$('[data-purchase-open]', area).forEach(button => {
    button.addEventListener("click", () => {
        localStorage.setItem("resource_selected_listing", button.dataset.purchaseOpen);
        goToPage("listing");
    });
});

}

function showProfileTab(tabName) {

const tab = $(`.ptab[data-tab="${tabName}"]`);
const panel = $(`#panel-${tabName}`);

if (!tab || !panel) return;

$$(".ptab").forEach(item => item.classList.remove("active"));
$$(".profile-panel").forEach(item => item.classList.add("hidden"));

tab.classList.add("active");
panel.classList.remove("hidden");

}

/* ==========================================================
MESSAGING
========================================================== */

let selectedConversationUserId =
null;

let conversationProfilesPromise = null;

async function refreshConversationProfiles() {

    if (conversationProfilesPromise) return conversationProfilesPromise;

    conversationProfilesPromise = (async () => {
        try {
            const response = await fetch("php/messages/conversations.php", {
                credentials: "same-origin"
            });
            const result = await response.json();
            const serverUsers = [
                ...(result?.users || []),
                ...(result?.directory || [])
            ];

            if (!response.ok || !result?.success || !serverUsers.length) return;

            const cachedUsers = getUsers();
            const mergedUsers = [...cachedUsers];

            serverUsers.forEach(serverUser => {
                const normalizedUser = normalizeUserRecord(serverUser);
                const index = mergedUsers.findIndex(user =>
                    String(user.id) === String(normalizedUser.id)
                );

                if (index >= 0) {
                    mergedUsers[index] = {
                        ...mergedUsers[index],
                        ...normalizedUser
                    };
                } else {
                    mergedUsers.push(normalizedUser);
                }
            });

            saveUsers(mergedUsers);
        } catch {
            // Local demo conversations remain available when the API is offline.
        } finally {
            conversationProfilesPromise = null;
        }
    })();

    return conversationProfilesPromise;
}

function getConversationKey(
userA,
userB
) {


return [
    userA,
    userB
]
    .sort()
    .join("__");


}

function getConversationParticipants() {


const current =
    getCurrentUser();


if (!current) return [];


const users =
    getUsers().filter(
        user =>
            String(user.id) !== String(current.id)
    );


const selectedPartner =
    selectedConversationUserId
        ? users.find(
            user => String(user.id) === String(selectedConversationUserId)
        )
        : null;


const messages =
    getMessages();


const userIdsWithMessages =
    new Set();


messages.forEach(message => {

    if (
        String(message.senderId) ===
        String(current.id)
    ) {

        userIdsWithMessages.add(
            message.receiverId
        );

    }


    if (
        String(message.receiverId) ===
        String(current.id)
    ) {

        userIdsWithMessages.add(
            message.senderId
        );

    }

});


const conversationUsers =
    users.filter(
        user =>
            userIdsWithMessages.has(String(user.id)) ||
            String(user.id) === String(selectedConversationUserId)
    );


if (selectedPartner && !conversationUsers.some(user => user.id === selectedPartner.id)) {
    conversationUsers.push(selectedPartner);
}


return conversationUsers

    .sort(
        (a, b) =>
            getUserDisplayName(a)
                .localeCompare(
                    getUserDisplayName(b)
                )
    );


}

async function renderMessaging() {


const current =
    getCurrentUser();


const warning =
    $("#message-login-warning");


const content =
    $("#messaging-content");


if (!current) {

    warning.classList.remove("hidden");

    content.classList.add("hidden");

    return;

}

await refreshConversationProfiles();


warning.classList.add("hidden");

content.classList.remove("hidden");


const partners =
    getConversationParticipants();


const search =
    (
        $("#conversation-search")
            .value ||
        ""
    )
        .trim()
        .toLowerCase();


const filtered =
    partners.filter(
        partner =>
            getUserDisplayName(partner)
                .toLowerCase()
                .includes(search)
    );


const list =
    $("#conversation-items");


list.innerHTML = "";


if (!filtered.length) {

    list.innerHTML = `

        <div
            class="empty-state"
            style="
                margin:12px;
                padding:20px 10px;
                border:none;
            ">

            No conversations yet.

            <br>

            Message a seller from a listing.

        </div>

    `;

} else {

    filtered.forEach(partner => {

        const messages =
            getMessages()

                .filter(message =>

                    (
                        String(message.senderId) ===
                        String(current.id) &&

                        String(message.receiverId) ===
                        String(partner.id)
                    )

                    ||

                    (
                        String(message.receiverId) ===
                        String(current.id) &&

                        String(message.senderId) ===
                        String(partner.id)
                    )

                )

                .sort(
                    (a, b) =>
                        new Date(a.createdAt) -
                        new Date(b.createdAt)
                );


        const last =
            messages[messages.length - 1];


        const item =
            document.createElement("div");


        item.className =
            `convo-item ${
                selectedConversationUserId ===
                String(partner.id)
                    ? "active"
                    : ""
            }`;


        item.innerHTML = `

            <div class="avatar">
                ${avatarMarkup(partner)}
            </div>

            <div style="min-width:0;">

                <div class="convo-name">
                    ${escapeHTML(
                        getUserDisplayName(partner)
                    )}
                </div>

                <div class="convo-preview">
                    ${escapeHTML(
                        last?.text ||
                        "Start a conversation"
                    )}
                </div>

            </div>

        `;


        item.addEventListener(
            "click",
            () => {

                selectedConversationUserId =
                    partner.id;

                renderMessaging();

            }
        );


        list.appendChild(item);

    });

}


const currentPartner =
    selectedConversationUserId
        ? partners.find(user =>
            String(user.id) === String(selectedConversationUserId)
        )
        : null;


if (!currentPartner && filtered.length) {
    renderChat(filtered[0]);
    return;
}


renderChat(
    currentPartner ||
    null
);


}

function renderChat(partner) {


const current =
    getCurrentUser();


if (!partner || !current) {

    $("#chat-partner-name")
        .textContent =
        "Select a conversation";


    $("#chat-partner-status")
        .textContent = "";


    $("#chat-body").innerHTML = `

        <div class="empty-state">
            Select a conversation to start messaging.
        </div>

    `;


    return;

}


$("#chat-partner-name")
    .textContent =
    getUserDisplayName(partner);

const chatPartnerAvatar = $("#chat-partner-avatar");

if (chatPartnerAvatar) {
    chatPartnerAvatar.innerHTML = avatarMarkup(partner);
}


$("#chat-partner-status")
    .textContent =
    "TIPian";


const messages =
    getMessages()

        .filter(message =>

            (
                String(message.senderId) ===
                String(current.id) &&

                String(message.receiverId) ===
                String(partner.id)
            )

            ||

            (
                String(message.receiverId) ===
                String(current.id) &&

                String(message.senderId) ===
                String(partner.id)
            )

        )

        .sort(
            (a, b) =>
                new Date(a.createdAt) -
                new Date(b.createdAt)
        );


const body =
    $("#chat-body");


body.innerHTML = "";


if (!messages.length) {

    body.innerHTML = `

        <div
            class="empty-state"
            style="
                margin:auto;
                border:none;
            ">

            Say hi to
            ${escapeHTML(
                getUserFirstName(partner)
            )}!

        </div>

    `;

} else {

    messages.forEach(message => {

        const mine =
            String(message.senderId) ===
            String(current.id);


        const row =
            document.createElement("div");


        row.className =
            `msg-row ${
                mine
                    ? "right"
                    : "left"
            }`;


        row.innerHTML = `

            <div
                class="msg-bubble ${
                    mine
                        ? "yellow"
                        : "gray"
                }">

                ${escapeHTML(
                    message.text
                )}

            </div>

            <div class="msg-time">

                ${formatTime(
                    message.createdAt
                )}

            </div>

        `;


        body.appendChild(row);

    });


    body.scrollTop =
        body.scrollHeight;

}


}

function openConversationWith(userId) {


if (!requireLogin()) return;


if (
    userId ===
    getCurrentUserId()
) {

    toast(
        "You cannot message yourself.",
        "error"
    );

    return;

}


selectedConversationUserId =
    userId;


goToPage("messaging");


}

/* ==========================================================
EVENTS
========================================================== */

document.addEventListener(
"DOMContentLoaded",
async () => {

    await syncCurrentUserFromSession();


    /* PRELOADER */

    setTimeout(() => {

        const pre =
            $("#preloader");


        if (pre) {

            pre.style.transition =
                "opacity 300ms ease";


            pre.style.opacity = "0";


            setTimeout(
                () => pre.remove(),
                320
            );

        }

    }, 350);


    /* INITIAL RENDER */
    renderAll();
    const requestedPage = new URLSearchParams(window.location.search).get("page");
    if (["dashboard", "browse", "saved", "listing", "create", "messaging", "profile"].includes(requestedPage)) {
        goToPage(requestedPage);
    }

    /* SIDEBAR NAVIGATION */

    $("#menu-toggle-btn")
        .addEventListener(
            "click",
            toggleNavigationMenu
        );

    $("#sidebar-close-btn")
        .addEventListener(
            "click",
            closeNavigationMenu
        );

    $("#sidebar-overlay")
        .addEventListener(
            "click",
            closeNavigationMenu
        );

    document.addEventListener(
        "keydown",
        event => {
            if (event.key === "Escape") {
                closeNavigationMenu();
            }
        }
    );

    $$(".nav-item")
        .forEach(link => {

            link.addEventListener(
                "click",
                event => {

                    event.preventDefault();


                    const page =
                        link.dataset.page;


                    if (
                        page === "create" &&
                        !requireLogin()
                    ) return;


                    goToPage(page);

                }
            );

        });


    /* TOP HEADER NAVIGATION */

    $$(".header-nav-link")
        .forEach(link => {

            link.addEventListener(
                "click",
                () => {

                    const page =
                        link.dataset.headerPage;


                    if (
                        page === "create" &&
                        !requireLogin()
                    ) return;


                    goToPage(page);

                }
            );

        });


    /* HEADER BRAND */

    $$(".header-brand")
        .forEach(brand => {

            brand.addEventListener(
                "click",
                () =>
                    goToPage("dashboard")
            );

        });


    /* GENERIC DATA-GOTO */

    $$("[data-goto]")
        .forEach(el => {

            el.addEventListener(
                "click",
                event => {

                    event.preventDefault();


                    const page =
                        el.dataset.goto;


                    if (
                        page === "create" &&
                        !requireLogin()
                    ) return;


                    goToPage(page);


                    if (
                        el.dataset.authTab
                    ) {

                        switchAuthTab(
                            el.dataset.authTab
                        );

                    }

                }
            );

        });


    /* CATEGORY FILTERS */

    $$(".category-item")
        .forEach(category => {

            category.addEventListener(
                "click",
                () => {

                    $("#filter-category")
                        .value =
                        category.dataset.category ||
                        "";


                    goToPage("browse");

                    renderBrowse();

                }
            );

        });


    /* AUTH TABS */

    $("#tab-login")
        .addEventListener(
            "click",
            () =>
                switchAuthTab("login")
        );


    $("#tab-signup")
        .addEventListener(
            "click",
            () =>
                switchAuthTab("signup")
        );


    /* TERMS AND CONDITIONS */

    const termsModal = $("#terms-modal");

    const closeTermsModal = () =>
        termsModal.classList.add("hidden");

    $("#open-terms-btn")
        .addEventListener(
            "click",
            () => termsModal.classList.remove("hidden")
        );

    $("#close-terms-btn")
        .addEventListener("click", closeTermsModal);

    $("#terms-done-btn")
        .addEventListener("click", closeTermsModal);

    termsModal.addEventListener(
        "click",
        event => {
            if (event.target === termsModal) {
                closeTermsModal();
            }
        }
    );

    document.addEventListener(
        "keydown",
        event => {
            if (event.key === "Escape") {
                closeTermsModal();
            }
        }
    );


    /* IMAGE PREVIEW */

    const imageLightbox = $("#image-lightbox");

    $("#image-lightbox-close")
        .addEventListener("click", closeImageViewer);

    imageLightbox.addEventListener(
        "click",
        event => {
            if (event.target === imageLightbox) {
                closeImageViewer();
            }
        }
    );

    document.addEventListener(
        "keydown",
        event => {
            if (event.key === "Escape") {
                closeImageViewer();
            }
        }
    );


    /* PASSWORD VISIBILITY */

    $("#toggle-password")
        .addEventListener(
            "click",
            () => {

                const input =
                    $("#login-password");


                const visible =
                    input.type ===
                    "password";


                input.type =
                    visible
                        ? "text"
                        : "password";


                $("#toggle-password")
                    .textContent =
                    visible
                        ? "🙈"
                        : "👁";

            }
        );


    /* LOGIN */

    $("#login-form")
        .addEventListener(
            "submit",
            async event => {

                event.preventDefault();


                const email =
                    $("#login-email")
                        .value
                        .trim()
                        .toLowerCase();


                const password =
                    $("#login-password")
                        .value;

                const role =
                    $("#login-role")
                        .value;


                if (!validateTipEmail(email)) {

                    toast(
                        "Please use your TIP institutional email.",
                        "error"
                    );

                    return;

                }


                const formData = new URLSearchParams({ email, password, role });

                let response;
                let result;

                try {
                    response = await fetch("php/auth/login.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: formData
                    });
                    result = await response.json();
                } catch (error) {
                    toast("The server is unavailable. Please try again.", "error");
                    return;
                }

                if (!response.ok || !result.success || !result.user) {
                    toast(result.message || "Unable to log in.", "error");
                    return;
                }

                const user = {
                    id: String(result.user.id),
                    firstName: result.user.first_name,
                    middleName: result.user.middle_name || "",
                    lastName: result.user.last_name,
                    name: [result.user.first_name, result.user.middle_name, result.user.last_name]
                        .filter(Boolean)
                        .join(" "),
                    studentId: result.user.student_id,
                    email: result.user.email,
                    course: result.user.course,
                    campus: result.user.campus,
                    role: result.user.role,
                    profilePhoto: result.user.profile_photo || ""
                };

                const users = getUsers().filter(account => account.id !== user.id);
                users.push(user);
                saveUsers(users);
                localStorage.setItem(STORAGE.session, user.id);


                toast(
                    `Welcome back, ${getUserFirstName(user)}!`
                );


                event.target.reset();


                renderAll();

            if (user.role === "admin") {
                window.location.href = "admin-dashboard.php";
               } else {
                goToPage("dashboard");
            }

            }
        );


    /* SIGNUP */

    $("#signup-form")
        .addEventListener(
            "submit",
            async event => {

                event.preventDefault();


                const firstName =
                    $("#signup-first-name")
                        .value
                        .trim();


                const middleName =
                    $("#signup-middle-name")
                        .value
                        .trim();


                const lastName =
                    $("#signup-last-name")
                        .value
                        .trim();


                const studentId =
                    $("#signup-student-id")
                        .value
                        .trim();


                const email =
                    $("#signup-email")
                        .value
                        .trim()
                        .toLowerCase();


                const password =
                    $("#signup-password")
                        .value;


                const password2 =
                    $("#signup-password2")
                        .value;


                const course =
                    $("#signup-course")
                        .value
                        .trim();


                const campus =
                    $("#signup-campus")
                        .value;


                const terms =
                    $("#signup-terms")
                        .checked;


                if (
                    !firstName ||
                    !lastName ||
                    !studentId ||
                    !email ||
                    !password ||
                    !password2 ||
                    !course ||
                    !campus
                ) {

                    toast(
                        "Please complete all required fields.",
                        "error"
                    );

                    return;

                }


                if (!validateStudentId(studentId)) {

                    toast(
                        "Please enter a valid Student ID.",
                        "error"
                    );

                    return;

                }


                if (!validateTipEmail(email)) {

                    toast(
                        "Only @tip.edu.ph emails are allowed.",
                        "error"
                    );

                    return;

                }


                if (password.length < 6) {

                    toast(
                        "Password must be at least 6 characters.",
                        "error"
                    );

                    return;

                }


                if (password !== password2) {

                    toast(
                        "Passwords do not match.",
                        "error"
                    );

                    return;

                }


                if (!terms) {

                    toast(
                        "Please agree to the Terms and Conditions.",
                        "error"
                    );

                    return;

                }


                let response;
                let result;

                try {
                    response = await fetch("php/auth/register.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams({
                            first_name: firstName,
                            middle_name: middleName,
                            last_name: lastName,
                            student_id: studentId,
                            email,
                            password,
                            password2,
                            course,
                            campus
                        })
                    });
                    result = await response.json();
                } catch (error) {
                    toast("The server is unavailable. Please try again.", "error");
                    return;
                }

                if (!response.ok || !result.success || !result.user) {
                    toast(result.message || "Unable to create the account.", "error");
                    return;
                }

                const user = {
                    id: String(result.user.id),
                    firstName: result.user.first_name,
                    middleName: result.user.middle_name || "",
                    lastName: result.user.last_name,
                    name: [result.user.first_name, result.user.middle_name, result.user.last_name]
                        .filter(Boolean)
                        .join(" "),
                    studentId: result.user.student_id,
                    email: result.user.email,
                    course: result.user.course,
                    campus: result.user.campus,
                    role: result.user.role,
                    profilePhoto: result.user.profile_photo || ""
                };

                const users = getUsers().filter(account => account.id !== user.id);
                users.push(user);
                saveUsers(users);
                localStorage.setItem(STORAGE.session, user.id);


                toast(
                    "Account created successfully!"
                );


                event.target.reset();


                renderAll();

                goToPage("dashboard");

            }
        );


    /* FORGOT PASSWORD */

    $("#forgot-password-link")
        .addEventListener(
            "click",
            event => {

                event.preventDefault();


                toast(
                    "Password reset will be connected to PHP later.",
                    "error"
                );

            }
        );


    /* DASHBOARD SEARCH */

    $("#dashboard-search-input")
        .addEventListener(
            "keydown",
            event => {

                if (
                    event.key ===
                    "Enter"
                ) {

                    $("#browse-search-input")
                        .value =
                        event.target.value;


                    goToPage("browse");

                    renderBrowse();

                }

            }
        );


    /* BROWSE */

    $("#toggle-filters-btn")
        .addEventListener(
            "click",
            () => {
                const panel = $("#listing-filters");
                const button = $("#toggle-filters-btn");
                const isOpening = panel.classList.contains("is-collapsed");

                panel.classList.toggle("is-collapsed", !isOpening);
                button.classList.toggle("active", isOpening);
                button.setAttribute("aria-expanded", String(isOpening));
            }
        );

    $("#apply-filter-btn")
        .addEventListener(
            "click",
            renderBrowse
        );


    $("#reset-filter-btn")
        .addEventListener(
            "click",
            resetFilters
        );


    $("#browse-search-input")
        .addEventListener(
            "input",
            renderBrowse
        );


    /* LISTING */

    $("#message-seller-btn")
        .addEventListener(
            "click",
            () => {

                const listingId =
                    localStorage.getItem(
                        "resource_selected_listing"
                    );


                const listing =
                    getListings().find(
                        item =>
                            item.id ===
                            listingId
                    );


                if (!listing) return;


                if (!requireLogin())
                    return;


                openConversationWith(
                    listing.sellerId
                );

            }
        );


    $("#detail-seller-box")
        .addEventListener(
            "click",
            () => {

                const listingId =
                    localStorage.getItem(
                        "resource_selected_listing"
                    );


                const listing =
                    getListings().find(
                        item =>
                            item.id ===
                            listingId
                    );


                if (!listing) return;


                localStorage.setItem(
                    "resource_profile_user",
                    listing.sellerId
                );


                goToPage("profile");

            }
        );


    $("#save-item-btn")
        .addEventListener(
            "click",
            () => {
                const listingId =
                    localStorage.getItem(
                        "resource_selected_listing"
                    );

                toggleSavedItem(listingId);

            }
        );


    $("#mark-sold-btn")
        .addEventListener(
            "click",
            () => {
                const listingId = localStorage.getItem(
                    "resource_selected_listing"
                );

                markListingAsSold(listingId);
            }
        );


    $("#delete-listing-btn")
        .addEventListener(
            "click",
            () => {

                const current =
                    getCurrentUser();


                const listingId =
                    localStorage.getItem(
                        "resource_selected_listing"
                    );


                const listings =
                    getListings();


                const listing =
                    listings.find(
                        item =>
                            item.id ===
                            listingId
                    );


                if (
                    !current ||
                    !listing ||
                    listing.sellerId !==
                    current.id
                ) return;


                const yes =
                    confirm(
                        `Delete "${listing.title}"?`
                    );


                if (!yes) return;


                saveListings(
                    listings.filter(
                        item =>
                            item.id !==
                            listingId
                    )
                );


                localStorage.removeItem(
                    "resource_selected_listing"
                );


                toast(
                    "Listing deleted."
                );


                renderAll();

                goToPage("dashboard");

            }
        );


    /* CREATE LISTING */

    $("#create-listing-form")
        .addEventListener(
            "submit",
            async event => {

                event.preventDefault();


                const user =
                    getCurrentUser();


                if (!user) {

                    requireLogin();

                    return;

                }


                const title =
                    $("#create-title")
                        .value
                        .trim();


                const courseCode =
                    $("#create-coursecode")
                        .value
                        .trim();


                const department =
                    $("#create-department")
                        .value
                        .trim() ||
                    user.course;


                const category =
                    $("#create-category")
                        .value;


                const condition =
                    $("#create-condition")
                        .value;


                const price =
                    Number(
                        $("#create-price")
                            .value
                    );


                const description =
                    $("#create-description")
                        .value
                        .trim();


                const campus =
                    $("#create-campus")
                        .value;


                if (
                    !title ||
                    !category ||
                    !condition ||
                    !description ||
                    !Number.isFinite(price) ||
                    price <= 0
                ) {

                    toast(
                        "Please complete all required listing fields.",
                        "error"
                    );

                    return;

                }


                const formData = new FormData();
                formData.append("title", title);
                formData.append("course_code", courseCode);
                formData.append("department", department);
                formData.append("category", category);
                formData.append("condition", condition);
                formData.append("price", String(price));
                formData.append("description", description);
                formData.append("campus", campus);

                selectedListingFiles
                    .slice(0, 3)
                    .forEach(file => formData.append("images[]", file));

                let response;
                let result;

                try {
                    response = await fetch("php/listings/create.php", {
                        method: "POST",
                        body: formData
                    });
                    result = await response.json();
                } catch (error) {
                    toast("The server is unavailable. Please try again.", "error");
                    return;
                }

                if (!response.ok || !result.success) {
                    toast(result.message || "Unable to publish the listing.", "error");
                    return;
                }

                const listing = {

                    id:
                        String(result.listing_id),

                    sellerId:
                        user.id,

                    sellerName:
                        getUserDisplayName(
                            user
                        ),

                    title,

                    courseCode,

                    department,

                    category,

                    condition,

                    price,

                    description,

                    campus,

                    image:
                        result.images?.[0] ||
                        FALLBACK_IMAGE,

                    images:
                        result.images?.length
                            ? result.images.slice(0, 3)
                            : [FALLBACK_IMAGE],

                    status: "active",

                    createdAt:
                        new Date()
                            .toISOString()

                };


                const listings =
                    getListings();


                listings.push(
                    listing
                );


                saveListings(
                    listings
                );


                localStorage.setItem(
                    "resource_selected_listing",
                    listing.id
                );


                toast(
                    "Listing published successfully!"
                );


                resetCreateForm();

                renderAll();

                goToPage("listing");

            }
        );


    /* SAVE DRAFT */

    $("#save-draft-btn")
        .addEventListener(
            "click",
            () => {

                if (!requireLogin())
                    return;


                const draft = {

                    id:
                        createId("draft"),

                    userId:
                        getCurrentUserId(),

                    title:
                        $("#create-title")
                            .value
                            .trim(),

                    savedAt:
                        new Date()
                            .toISOString()

                };


                const drafts =
                    readStorage(
                        STORAGE.drafts,
                        []
                    );


                drafts.push(
                    draft
                );


                writeStorage(
                    STORAGE.drafts,
                    drafts
                );


                toast(
                    "Draft saved in this browser."
                );

            }
        );


    /* CANCEL CREATE */

    $("#cancel-create-btn")
        .addEventListener(
            "click",
            () => {

                resetCreateForm();

                goToPage("dashboard");

            }
        );


    /* PHOTO INPUTS */

    bindPhotoInputs();


    /* PROFILE TABS */

    $$(".ptab")
        .forEach(tab => {

            tab.addEventListener(
                "click",
                () => showProfileTab(tab.dataset.tab)
            );

        });


    /* PROFILE SETTINGS */

    $("#profile-photo-input")
        .addEventListener(
            "change",
            async event => {
                const file = event.target.files?.[0];

                if (!file) return;

                if (file.size > 1.5 * 1024 * 1024) {
                    toast("Please choose a photo smaller than 1.5 MB.", "error");
                    event.target.value = "";
                    return;
                }

                let dataUrl = "";

                try {
                    dataUrl = await readImageAsDataURL(file);
                } catch {
                    toast("Could not read that photo.", "error");
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append("photo", file);

                    const response = await fetch("php/users/upload-photo.php", {
                        method: "POST",
                        body: formData
                    });

                    const result = await response.json();
                    const profilePhoto = result?.profile_photo || dataUrl;

                    if (!response.ok || !result?.success) {
                        updateCurrentUser({ profilePhoto: dataUrl });
                        renderAll();
                        goToPage("profile");
                        toast(result?.message || "Profile photo saved locally.", "error");
                        return;
                    }

                    updateCurrentUser({ profilePhoto });
                    renderAll();
                    goToPage("profile");
                    toast("Profile photo updated.");
                } catch {
                    updateCurrentUser({ profilePhoto: dataUrl });
                    renderAll();
                    goToPage("profile");
                    toast("Profile photo saved locally.", "error");
                }
            }
        );


    $("#course-update-form")
        .addEventListener(
            "submit",
            event => {
                event.preventDefault();

                const course = $("#profile-course-input").value.trim();

                if (!course) {
                    toast("Please enter your course or program.", "error");
                    return;
                }

                updateCurrentUser({ course });
                renderProfile();
                showProfileTab("settings");
                toast("Course updated.");
            }
        );


    $("#password-update-form")
        .addEventListener(
            "submit",
            async event => {
                event.preventDefault();

                const currentPassword = $("#current-password-input").value;
                const newPassword = $("#new-password-input").value;
                const confirmPassword = $("#confirm-password-input").value;

                if (newPassword.length < 6) {
                    toast("Your new password must be at least 6 characters.", "error");
                    return;
                }

                if (newPassword !== confirmPassword) {
                    toast("Your new passwords do not match.", "error");
                    return;
                }

                try {
                    const response = await fetch("php/users/change-password.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams({
                            current_password: currentPassword,
                            new_password: newPassword,
                            confirm_password: confirmPassword
                        })
                    });
                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        toast(result.message || "Unable to update your password.", "error");
                        return;
                    }
                } catch (error) {
                    toast("The server is unavailable. Please try again.", "error");
                    return;
                }

                event.target.reset();
                toast("Password updated.");
            }
        );


    $("#notification-settings-form")
        .addEventListener(
            "submit",
            event => {
                event.preventDefault();

                updateCurrentUser({
                    notifications: {
                        messages: $("#notify-messages").checked,
                        listingActivity: $("#notify-listings").checked
                    }
                });

                toast("Notification settings saved.");
            }
        );


    $("#privacy-settings-form")
        .addEventListener(
            "submit",
            event => {
                event.preventDefault();

                updateCurrentUser({
                    privacy: {
                        showProfilePhoto: $("#privacy-photo").checked,
                        showCourse: $("#privacy-course").checked
                    }
                });

                renderTopHeader();
                toast("Privacy settings saved.");
            }
        );


    $("#logout-btn")
        .addEventListener(
            "click",
            logout
        );


    /* MESSAGING */

    $("#conversation-search")
        .addEventListener(
            "input",
            renderMessaging
        );


    $("#chat-form")
        .addEventListener(
            "submit",
            event => {

                event.preventDefault();


                const current =
                    getCurrentUser();


                if (
                    !current ||
                    !selectedConversationUserId
                ) {

                    toast(
                        "Select a conversation first.",
                        "error"
                    );

                    return;

                }


                const input =
                    $("#chat-input");


                const text =
                    input.value.trim();


                if (!text) return;


                const messages =
                    getMessages();


                messages.push({

                    id:
                        createId("message"),

                    conversationId:
                        getConversationKey(
                            current.id,
                            selectedConversationUserId
                        ),

                    senderId:
                        current.id,

                    receiverId:
                        selectedConversationUserId,

                    text,

                    createdAt:
                        new Date()
                            .toISOString()

                });


                saveMessages(
                    messages
                );


                input.value = "";


                renderMessaging();


                $("#chat-body")
                    .scrollTop =
                    $("#chat-body")
                        .scrollHeight;

            }
        );


    /* ATTACHMENT PLACEHOLDER */

    $("#attach-image-btn")
        .addEventListener(
            "click",
            () =>
                $("#hidden-attach-input")
                    .click()
        );


    $("#hidden-attach-input")
        .addEventListener(
            "change",
            event => {

                const file =
                    event.target.files?.[0];


                if (!file) return;


                toast(
                    "Image attachment is a frontend placeholder. PHP will handle real uploads later."
                );


                event.target.value = "";

            }
        );

}


);

/* ==========================================================
RENDER ALL
========================================================== */

function renderAll() {


renderTopHeader();

renderDashboard();

renderBrowse();

renderSavedItems();

renderListingDetail();

prepareCreatePage();

renderProfile();

renderMessaging();

updateSavedBadge();


}

/* ==========================================================
OPTIONAL DEMO ACCOUNTS
========================================================== */

function setupDemoAccounts() {


const users =
    getUsers();


if (users.length >= 2) {

    toast(
        "You already have at least two accounts."
    );

    return;

}


const demoUsers = [

    {

        id:
            createId("user"),

        firstName:
            "Sabrina",

        middleName:
            "Alea",

        lastName:
            "Timbol",

        name:
            "Sabrina Alea Timbol",

        studentId:
            "DEMO10001",

        email:
            "sabrina@tip.edu.ph",

        password:
            "123456",

        course:
            "BSIT",

        campus:
            "Manila",

        createdAt:
            new Date()
                .toISOString()

    },


    {

        id:
            createId("user"),

        firstName:
            "Ritz",

        middleName:
            "Tan",

        lastName:
            "Santos",

        name:
            "Ritz Tan Santos",

        studentId:
            "DEMO10002",

        email:
            "ritz@tip.edu.ph",

        password:
            "123456",

        course:
            "BSCpE",

        campus:
            "Manila",

        createdAt:
            new Date()
                .toISOString()

    }

];


saveUsers([
    ...users,
    ...demoUsers
]);


toast(
    "Demo accounts created. Password: 123456"
);


}

window.setupDemoAccounts =
setupDemoAccounts;