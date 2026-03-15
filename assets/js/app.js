(function () {
  if (localStorage.getItem("he_dark") === "1")
    document.body.classList.add("dark");
})();

function initTheme() {
  if (localStorage.getItem("he_dark") === "1") {
    document.body.classList.add("dark");
  }
}

function toggleDark() {
  document.body.classList.toggle("dark");
  localStorage.setItem(
    "he_dark",
    document.body.classList.contains("dark") ? "1" : "0",
  );
  const ic = document.getElementById("dmIcon");
  if (ic)
    ic.className = document.body.classList.contains("dark")
      ? "bi bi-sun-fill"
      : "bi bi-moon-fill";
}

const ICONS = {
  cleaning: `<svg viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="12" fill="#FDECC8"/><path d="M12 28h16M20 8v4M14 12l-2 8h16l-2-8H14z" stroke="#E8960F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 20v6M20 20v6M24 20v6" stroke="#E8960F" stroke-width="1.5" stroke-linecap="round"/><circle cx="20" cy="10" r="2" fill="#E8960F"/></svg>`,
  plumbing: `<svg viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="12" fill="#FDECC8"/><path d="M14 10v8a6 6 0 006 6h0a6 6 0 006-6V10" stroke="#E8960F" stroke-width="2" stroke-linecap="round"/><rect x="12" y="8" width="4" height="4" rx="1" fill="#E8960F"/><rect x="24" y="8" width="4" height="4" rx="1" fill="#E8960F"/><path d="M20 24v6M17 30h6" stroke="#E8960F" stroke-width="2" stroke-linecap="round"/></svg>`,
  electrical: `<svg viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="12" fill="#FDECC8"/><path d="M22 9l-6 11h7l-5 11 10-13h-7L22 9z" fill="#E8960F" stroke="#E8960F" stroke-width="1" stroke-linejoin="round"/></svg>`,
  painting: `<svg viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="12" fill="#FDECC8"/><path d="M13 27l4-4 10-10a2 2 0 00-3-3L14 20l-4 4 3 3z" stroke="#E8960F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="28" cy="29" r="3" fill="#E8960F" opacity=".5"/><path d="M27 29c0-3 3-4 3-6" stroke="#E8960F" stroke-width="1.5" stroke-linecap="round"/></svg>`,
  appliance: `<svg viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="12" fill="#FDECC8"/><rect x="11" y="10" width="18" height="20" rx="3" stroke="#E8960F" stroke-width="2"/><path d="M11 16h18" stroke="#E8960F" stroke-width="2"/><circle cx="15" cy="13" r="1.5" fill="#E8960F"/><circle cx="20" cy="23" r="4" stroke="#E8960F" stroke-width="1.5"/><path d="M20 21v2l1.5 1.5" stroke="#E8960F" stroke-width="1.5" stroke-linecap="round"/></svg>`,
  gardening: `<svg viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="12" fill="#FDECC8"/><path d="M20 30V18M20 18c0-6 8-8 8-8s0 8-8 8zM20 18c0-5-6-8-6-8s0 7 6 8z" stroke="#E8960F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 30h12" stroke="#E8960F" stroke-width="2" stroke-linecap="round"/></svg>`,
};

const SVCS = {
  Cleaning: { ic: ICONS.cleaning, hr: 200, flat: 599, key: "cleaning" },
  Plumbing: { ic: ICONS.plumbing, hr: 250, flat: 450, key: "plumbing" },
  Electrical: { ic: ICONS.electrical, hr: 300, flat: 750, key: "electrical" },
  Painting: { ic: ICONS.painting, hr: 220, flat: 800, key: "painting" },
  "Appliance Repair": {
    ic: ICONS.appliance,
    hr: 280,
    flat: 650,
    key: "appliance",
  },
  Gardening: { ic: ICONS.gardening, hr: 180, flat: 850, key: "gardening" },
};

const SVC_IMGS = {
  cleaning:
    "https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=400&q=80",
  plumbing:
    "https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=400&q=80",
  electrical:
    "https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=400&q=80",
  painting:
    "https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=400&q=80",
  appliance:
    "https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&q=80",
  gardening:
    "https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&q=80",
};

const OB_IMGS = [
  "https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=500&q=80",
  "https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=500&q=80",
  "https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=500&q=80",
];

if (!window.HE) {
  window.HE = {
    bookings: [
      {
        id: 1,
        svc: "Cleaning",
        key: "cleaning",
        status: "pending",
        date: "Feb 25, 2026",
        time: "9:00 AM",
        price: 400,
        addr: "123 Mauban St.",
        desc: "Full house cleaning",
        rateType: "hourly",
        dur: 2,
      },
      {
        id: 2,
        svc: "Plumbing",
        key: "plumbing",
        status: "progress",
        date: "Feb 24, 2026",
        time: "2:00 PM",
        price: 450,
        addr: "45 Barangay Uno",
        desc: "Fix kitchen sink",
        rateType: "flat",
        dur: null,
      },
      {
        id: 3,
        svc: "Electrical",
        key: "electrical",
        status: "done",
        date: "Feb 20, 2026",
        time: "10:00 AM",
        price: 750,
        addr: "Brgy. Cagsiay I",
        desc: "Rewire outlets",
        rateType: "flat",
        dur: null,
      },
    ],
    nid: 4,
    user: {
      name: "Juan dela Cruz",
      email: "juan@email.com",
      phone: "09171234567",
      address: "123 Mauban, Quezon",
    },
    notifications: [
      {
        id: 1,
        title: "Booking Confirmed",
        msg: "Your cleaning service on Feb 25 is confirmed.",
        time: "2h ago",
        read: false,
        icon: "cleaning",
      },
      {
        id: 2,
        title: "Service In Progress",
        msg: "Your plumbing service has started.",
        time: "5h ago",
        read: false,
        icon: "plumbing",
      },
      {
        id: 3,
        title: "Service Completed",
        msg: "Electrical wiring job is done! Rate your experience.",
        time: "4d ago",
        read: true,
        icon: "electrical",
      },
    ],
  };
}

function _loadBookmarks() {
  try {
    return JSON.parse(localStorage.getItem("he_bookmarks") || "[]");
  } catch (e) {
    return [];
  }
}
function _saveBookmarks(ids) {
  localStorage.setItem("he_bookmarks", JSON.stringify(ids));
}
function isBookmarked(id) {
  return _loadBookmarks().includes(id);
}
function toggleBookmark(id) {
  let bms = _loadBookmarks();
  const had = bms.includes(id);
  bms = had ? bms.filter((x) => x !== id) : [...bms, id];
  _saveBookmarks(bms);
  document.querySelectorAll(`.bk-btn[data-id="${id}"]`).forEach((btn) => {
    btn.classList.toggle("saved", !had);
    const icon = btn.querySelector("i");
    if (icon) icon.className = !had ? "bi bi-bookmark-fill" : "bi bi-bookmark";
    if (!had) icon && icon.classList.add("anim");
  });
  showToast(had ? "Booking removed from saved" : "🔖 Booking saved!");
  return !had;
}

function bookmarkBtn(id) {
  const saved = isBookmarked(id);
  return `<button class="bk-btn${saved ? " saved" : ""}" data-id="${id}" onclick="toggleBookmark(${id})" title="${saved ? "Remove bookmark" : "Save booking"}">
    <i class="bi bi-bookmark${saved ? "-fill" : ""}"></i>
  </button>`;
}

function openSaved() {
  const m = document.getElementById("savedModal");
  if (m) {
    renderSaved();
    m.classList.add("on");
  }
}
function closeSaved() {
  const m = document.getElementById("savedModal");
  if (m) m.classList.remove("on");
}
function renderSaved() {
  const cnt = document.getElementById("savedList");
  const hdrCnt = document.getElementById("savedCount");
  if (!cnt) return;
  const ids = _loadBookmarks();
  const all = window.HE.bookings || [];
  const items = all.filter((b) => ids.includes(b.id));
  if (hdrCnt) hdrCnt.textContent = `${items.length} saved`;
  if (!items.length) {
    cnt.innerHTML = `<div class="saved-empty">
      <div class="saved-empty-icon">🔖</div>
      <div class="saved-empty-txt">No saved bookings yet<br><span style="font-weight:400;font-size:12px;">Tap the bookmark icon on any booking to save it</span></div>
    </div>`;
    return;
  }
  cnt.innerHTML = items
    .map(
      (b) => `
    <div class="saved-item" onclick="closeSaved();goPage('bookings.php?id=${b.id}')">
      <div class="saved-item-ic"><img src="${SVC_IMGS[b.key]}" alt="${b.svc}"></div>
      <div class="saved-item-info">
        <div class="saved-item-nm">${b.svc}</div>
        <div class="saved-item-meta">${b.date} · ${b.time} · ${b.addr}</div>
      </div>
      <div>
        <div class="saved-item-price">₱${b.price.toLocaleString()}</div>
        <div style="font-size:10px;text-align:right;margin-top:3px;color:${b.status === "done" ? "#10B981" : b.status === "progress" ? "#F5A623" : "#6B7280"};font-weight:700;text-transform:capitalize;">${b.status}</div>
      </div>
    </div>
  `,
    )
    .join("");
}

function navTo(page, el) {
  document.querySelectorAll(".ni").forEach((n) => n.classList.remove("on"));
  if (el) el.classList.add("on");
  const pages = {
    home: "home.php",
    bookings: "bookings.php",
    notifications: "notifications.php",
    profile: "profile.php",
  };
  if (pages[page]) goPage(pages[page]);
}

function goPage(file) {
  const loader = document.getElementById("ml");
  if (loader) loader.classList.add("on");
  setTimeout(() => {
    window.location.href = file;
  }, 320);
}

function openSearch() {
  const m = document.getElementById("searchModal");
  if (!m) return;
  m.classList.add("on");
  setTimeout(() => {
    const inp = document.getElementById("srchInp");
    if (inp) inp.focus();
  }, 350);
  renderSearch("");
}
function closeSearch() {
  const m = document.getElementById("searchModal");
  if (m) m.classList.remove("on");
}
function renderSearch(q) {
  const res = document.getElementById("srchResults");
  if (!res) return;
  const qLow = q.toLowerCase().trim();
  const allSvcs = [
    {
      name: "Cleaning",
      desc: "Deep home & office cleaning",
      key: "cleaning",
      flat: 599,
      hr: 200,
    },
    {
      name: "Plumbing",
      desc: "Pipe repair, clogs & more",
      key: "plumbing",
      flat: 450,
      hr: 250,
    },
    {
      name: "Electrical",
      desc: "Wiring, outlets & installations",
      key: "electrical",
      flat: 750,
      hr: 300,
    },
    {
      name: "Painting",
      desc: "Interior & exterior painting",
      key: "painting",
      flat: 800,
      hr: 220,
    },
    {
      name: "Appliance Repair",
      desc: "Fix any home appliance",
      key: "appliance",
      flat: 650,
      hr: 280,
    },
    {
      name: "Gardening",
      desc: "Landscaping & garden care",
      key: "gardening",
      flat: 850,
      hr: 180,
    },
  ];
  const filtered = qLow
    ? allSvcs.filter(
        (s) =>
          s.name.toLowerCase().includes(qLow) ||
          s.desc.toLowerCase().includes(qLow),
      )
    : allSvcs;
  if (!filtered.length) {
    res.innerHTML = `<div class="srch-empty">No services found for "<strong>${q}</strong>"</div>`;
    return;
  }
  const lbl = qLow
    ? `${filtered.length} result${filtered.length !== 1 ? "s" : ""} for "${q}"`
    : "All Services";
  res.innerHTML =
    `<div class="srch-lbl" style="margin-bottom:10px;">${lbl}</div>` +
    filtered
      .map(
        (s) => `
      <div class="srch-item" onclick="closeSearch();goPage('bookings.php?svc=${encodeURIComponent(s.name)}&newbooking=1')">
        <div class="srch-ic"><img src="${SVC_IMGS[s.key]}" alt="${s.name}"></div>
        <div class="srch-info">
          <div class="srch-nm">${s.name}</div>
          <div class="srch-sub">${s.desc}</div>
        </div>
        <div class="srch-price">from ₱${s.flat.toLocaleString()}</div>
      </div>`,
      )
      .join("");
}

function openSupport() {
  const m = document.getElementById("supportModal");
  if (m) m.classList.add("on");
}
function closeSupport() {
  const m = document.getElementById("supportModal");
  if (m) m.classList.remove("on");
}
function openHelp() {
  closeSupport();
  const m = document.getElementById("helpModal");
  if (m) m.classList.add("on");
}
function closeHelp() {
  const m = document.getElementById("helpModal");
  if (m) m.classList.remove("on");
}
function openContact() {
  closeSupport();
  const m = document.getElementById("contactModal");
  if (m) m.classList.add("on");
}
function closeContact() {
  const m = document.getElementById("contactModal");
  if (m) m.classList.remove("on");
}
function openRating() {
  closeSupport();
  const m = document.getElementById("ratingModal");
  if (m) {
    m.classList.add("on");
    resetStars();
  }
}
function closeRating() {
  const m = document.getElementById("ratingModal");
  if (m) m.classList.remove("on");
}

let _ratVal = 0;
function resetStars() {
  _ratVal = 0;
  document
    .querySelectorAll(".rat-star")
    .forEach((s) => s.classList.remove("lit"));
  const ta = document.getElementById("ratTa");
  if (ta) ta.value = "";
}
function setRating(n) {
  _ratVal = n;
  document
    .querySelectorAll(".rat-star")
    .forEach((s, i) => s.classList.toggle("lit", i < n));
}
function submitRating() {
  if (!_ratVal) {
    showToast("Please select a star rating first!");
    return;
  }
  closeRating();
  setTimeout(() => showToast(`Thanks for your ${_ratVal}⭐ rating!`), 400);
}

function showToast(msg) {
  let t = document.getElementById("heToast");
  if (!t) {
    t = document.createElement("div");
    t.id = "heToast";
    t.style.cssText =
      "position:absolute;bottom:90px;left:50%;transform:translateX(-50%);background:#F5A623;color:#fff;padding:10px 20px;border-radius:30px;font-size:13px;font-weight:700;z-index:9999;white-space:nowrap;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:opacity .3s;";
    document.querySelector(".shell").appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = "1";
  clearTimeout(t._to);
  t._to = setTimeout(() => {
    t.style.opacity = "0";
  }, 2500);
}

function toggleFaq(el) {
  el.classList.toggle("open");
}
function openAllServices() {
  const m = document.getElementById("allServicesModal");
  if (m) m.classList.add("on");
}
function closeAllServices() {
  const m = document.getElementById("allServicesModal");
  if (m) m.classList.remove("on");
}
function submitContact() {
  const name = document.getElementById("contName")?.value?.trim();
  const msg = document.getElementById("contMsg")?.value?.trim();
  if (!msg) {
    showToast("Please describe your concern first.");
    return;
  }
  closeContact();
  setTimeout(
    () => showToast(`Message sent! We'll reply soon, ${name || "friend"} 👋`),
    400,
  );
}

let _chatTab = "support"; // single channel now
let _chatHistory = {
  support: [
    {
      from: "bot",
      time: _chatNow(),
      text: "Hello! How can I help you today?",
    },
  ],
};
let _chatTyping = false;

function _chatNow() {
  const d = new Date();
  return d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });
}

// _chatAutoReplies removed — using Groq AI for all responses

const _chatQuickReplies = {
  support: [
    "Show my bookings",
    "Cancel a booking",
    "Current promos",
    "How do I book?",
  ],
};

function openChat(tab) {
  _chatTab = tab || "support";
  const m = document.getElementById("chatModal");
  if (m) {
    m.classList.add("on");
    _renderChatTab();
    // Hide the badge
    const badge = document.querySelector(".chat-badge");
    if (badge) badge.style.display = "none";
    setTimeout(() => {
      const i = document.getElementById("chatInp");
      if (i) i.focus();
    }, 400);
  }
}
function closeChat() {
  const m = document.getElementById("chatModal");
  if (m) m.classList.remove("on");
}
function switchChatTab(tab) {
  // Single chat channel — no tab switching needed
  _renderChatMsgs();
  _renderChatQuick();
}
function _renderChatTab() {
  _renderChatMsgs();
  _renderChatQuick();
}
function _renderChatMsgs() {
  const box = document.getElementById("chatMsgs");
  if (!box) return;
  const msgs = _chatHistory[_chatTab] || [];
  box.innerHTML =
    `<div class="chat-date-div">Today</div>` +
    msgs
      .map((m) => {
        const mine = m.from === "me";
        // Allow HTML in bot messages (for booking cards etc)
        const bubbleContent =
          !mine && m.html ? m.html : m.text.replace(/\n/g, "<br>");
        return `<div class="chat-msg${mine ? " mine" : ""}">
        ${!mine ? `<div class="chat-msg-av"><i class="bi bi-headset"></i></div>` : ""}
        <div style="max-width:85%;">
          <div class="chat-bubble" style="${
            mine
              ? "background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;border-radius:18px;border-bottom-right-radius:6px;box-shadow:0 4px 14px rgba(232,130,12,.3);padding:10px 14px;"
              : "background:#FFF3E0;color:#1a1a2e;border:1.5px solid #FDECC8;border-radius:18px;border-bottom-left-radius:6px;padding:10px 14px;"
          }">${bubbleContent}<span class="chat-bubble-time" style="display:block;font-size:10px;margin-top:3px;text-align:right;color:${mine ? "rgba(255,255,255,.65)" : "#FDBA74"};">${m.time}</span></div>
        </div>
      </div>`;
      })
      .join("");
  box.scrollTop = box.scrollHeight;
}
function _renderChatQuick() {
  const qr = document.getElementById("chatQuick");
  if (!qr) return;
  const reps = _chatQuickReplies[_chatTab] || [];
  qr.innerHTML = reps
    .map(
      (r) =>
        `<span class="chat-qr" onclick="sendQuickReply('${r}')">${r}</span>`,
    )
    .join("");
}
function sendQuickReply(text) {
  const inp = document.getElementById("chatInp");
  if (inp) inp.value = text;
  sendChat();
}
function sendChat() {
  const inp = document.getElementById("chatInp");
  if (!inp) return;
  const text = inp.value.trim();
  if (!text) return;
  inp.value = "";
  inp.style.height = "auto";

  _chatHistory[_chatTab].push({ from: "me", time: _chatNow(), text });
  _renderChatMsgs();
  _showChatTyping();

  // Pass last 12 messages as context
  const history = _chatHistory[_chatTab]
    .slice(-13, -1)
    .filter((m) => m.from === "me" || m.from === "bot")
    .map((m) => ({ role: m.from === "me" ? "user" : "model", text: m.text }));

  fetch("api/groq_chat.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ message: text, history: history }),
  })
    .then((r) => r.json())
    .then((data) => {
      _hideChatTyping();
      if (!data.success && !data.reply) {
        _chatHistory[_chatTab].push({
          from: "bot",
          time: _chatNow(),
          text:
            data.message ||
            "Sorry, I could not get a response. Please try again.",
        });
        _renderChatMsgs();
        return;
      }

      const reply = data.reply || "Sorry, I could not get a response.";

      // Handle successful cancel action
      if (data.action_result === "cancelled" && data.booking_id) {
        const successHtml = `
          <div style="background:#d1fae5;border-radius:12px;padding:12px;margin-bottom:6px;border:1.5px solid #6ee7b7;">
            <div style="font-size:13px;font-weight:700;color:#059669;margin-bottom:4px;">✅ Booking Cancelled</div>
            <div style="font-size:12px;color:#E8960F;">Booking #${data.booking_id} has been successfully cancelled.</div>
          </div>
          <div style="font-size:13px;color:#1a1a2e;">${reply}</div>`;
        _chatHistory[_chatTab].push({
          from: "bot",
          time: _chatNow(),
          text: reply,
          html: successHtml,
        });
      } else {
        _chatHistory[_chatTab].push({
          from: "bot",
          time: _chatNow(),
          text: reply,
        });
      }

      // Update quick replies based on context
      _updateSmartQuickReplies(reply);
      _renderChatMsgs();
    })
    .catch(() => {
      _hideChatTyping();
      _chatHistory[_chatTab].push({
        from: "bot",
        time: _chatNow(),
        text: "Sorry, I am having trouble connecting right now. Please try again in a moment.",
      });
      _renderChatMsgs();
    });
}

function _updateSmartQuickReplies(lastReply) {
  const qr = document.getElementById("chatQuick");
  if (!qr) return;
  const lower = lastReply.toLowerCase();
  let suggestions = [];

  if (
    lower.includes("cancel") &&
    (lower.includes("yes") || lower.includes("confirm"))
  ) {
    suggestions = ["YES, cancel it", "No, keep it"];
  } else if (lower.includes("booking") && lower.includes("status")) {
    suggestions = ["Show all bookings", "Cancel a booking", "Book a service"];
  } else if (lower.includes("book") || lower.includes("service")) {
    suggestions = [
      "Show my bookings",
      "What services do you offer?",
      "Current promos",
    ];
  } else {
    suggestions = [
      "Show my bookings",
      "Cancel a booking",
      "Current promos",
      "Contact support",
    ];
  }

  qr.innerHTML = suggestions
    .map(
      (s) =>
        `<span class="chat-qr" onclick="sendQuickReply('${s}')">${s}</span>`,
    )
    .join("");
}
function _showChatTyping() {
  if (_chatTyping) return;
  _chatTyping = true;
  const box = document.getElementById("chatMsgs");
  if (!box) return;
  const el = document.createElement("div");
  el.className = "chat-msg";
  el.id = "chatTypingEl";
  el.innerHTML = `<div class="chat-msg-av"><i class="bi bi-headset"></i></div>
    <div class="chat-typing">
      <div class="chat-bubble">
        <div class="type-dot"></div><div class="type-dot"></div><div class="type-dot"></div>
      </div>
    </div>`;
  box.appendChild(el);
  box.scrollTop = box.scrollHeight;
}
function _hideChatTyping() {
  _chatTyping = false;
  const el = document.getElementById("chatTypingEl");
  if (el) el.remove();
}

const ALL_OFFERS = [
  {
    id: 1,
    cat: "flash",
    badge: "Flash Sale",
    badgeType: "flash",
    name: "Deep Home Cleaning Bundle",
    desc: "Full house cleaning including kitchen, bathrooms & living areas. Professional-grade equipment.",
    img: "https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=400&q=80",
    oldPrice: 1200,
    price: 599,
    tag: "🧹 Cleaning",
    exp: "Ends tonight",
    svc: "Cleaning",
  },
  {
    id: 2,
    cat: "promo",
    badge: "20% Off",
    badgeType: "",
    name: "First Time Customer Special",
    desc: "Get 20% off your first booking with code EASE20. Valid for all services!",
    img: "https://images.unsplash.com/photo-1556911220-bff31c812dba?w=400&q=80",
    oldPrice: 1000,
    price: 800,
    tag: "🎉 All Services",
    exp: "New customers only",
    svc: "Cleaning",
  },
  {
    id: 3,
    cat: "new",
    badge: "New",
    badgeType: "new",
    name: "Plumbing Full Inspection",
    desc: "Complete pipe & drainage inspection + minor repairs included. No hidden fees.",
    img: "https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=400&q=80",
    oldPrice: 700,
    price: 399,
    tag: "🔧 Plumbing",
    exp: "Ends Feb 28",
    svc: "Plumbing",
  },
  {
    id: 4,
    cat: "promo",
    badge: "10% Off",
    badgeType: "",
    name: "Basic Electrical Service",
    desc: "Minor repairs & outlet installations. Perfect for quick fixes around the house.",
    img: "https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=400&q=80",
    oldPrice: 500,
    price: 450,
    tag: "⚡ Electrical",
    exp: "Use code ELECTRIC10",
    svc: "Electrical",
  },
  {
    id: 5,
    cat: "promo",
    badge: "Save 30%",
    badgeType: "",
    name: "Electrical Safety Check",
    desc: "Full home wiring inspection & hazard report. Peace of mind for your family.",
    img: "https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=400&q=80",
    oldPrice: 1000,
    price: 700,
    tag: "⚡ Electrical",
    exp: "Limited slots",
    svc: "Electrical",
  },
  {
    id: 6,
    cat: "limited",
    badge: "Limited",
    badgeType: "limited",
    name: "Garden Makeover Package",
    desc: "Transform your outdoor space. Trimming, weeding, soil treatment & décor included.",
    img: "https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&q=80",
    oldPrice: 1500,
    price: 850,
    tag: "🌱 Gardening",
    exp: "5 slots left",
    svc: "Gardening",
  },
  {
    id: 7,
    cat: "flash",
    badge: "Flash Sale",
    badgeType: "flash",
    name: "Interior Painting — 1 Room",
    desc: "Professional painters, premium paint included. One room, one day.",
    img: "https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=400&q=80",
    oldPrice: 1400,
    price: 799,
    tag: "🎨 Painting",
    exp: "Ends Mar 1",
    svc: "Painting",
  },
  {
    id: 8,
    cat: "promo",
    badge: "15% Off",
    badgeType: "new",
    name: "Kitchen Appliance Bundle",
    desc: "Refrigerator + stove maintenance and repair. Save when you book both!",
    img: "https://images.unsplash.com/photo-1556911220-bff31c812dba?w=400&q=80",
    oldPrice: 800,
    price: 680,
    tag: "⚙️ Appliance",
    exp: "Bundle deal",
    svc: "Appliance Repair",
  },
  {
    id: 9,
    cat: "promo",
    badge: "Best Value",
    badgeType: "new",
    name: "Appliance Repair + Tune-up",
    desc: "Fix any malfunctioning appliance + free general maintenance checkup.",
    img: "https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&q=80",
    oldPrice: 900,
    price: 550,
    tag: "⚙️ Appliance",
    exp: "Ongoing",
    svc: "Appliance Repair",
  },
  {
    id: 10,
    cat: "new",
    badge: "New",
    badgeType: "new",
    name: "Move-in Cleaning Special",
    desc: "Deep clean your new home before moving in. Includes disinfection & odor removal.",
    img: "https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=400&q=80",
    oldPrice: 2000,
    price: 1299,
    tag: "🏠 Cleaning",
    exp: "New offer!",
    svc: "Cleaning",
  },
  {
    id: 11,
    cat: "promo",
    badge: "25% Off",
    badgeType: "",
    name: "Spring Garden Cleanup",
    desc: "Seasonal special! Clear debris, trim hedges, and prepare your garden for spring.",
    img: "https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=400&q=80",
    oldPrice: 1200,
    price: 900,
    tag: "🌸 Gardening",
    exp: "Spring special",
    svc: "Gardening",
  },
  {
    id: 12,
    cat: "limited",
    badge: "Limited",
    badgeType: "limited",
    name: "Full Home Electrical Rewire",
    desc: "Complete rewiring for older homes. Certified electrician, 1-year warranty.",
    img: "https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?w=400&q=80",
    oldPrice: 5000,
    price: 3200,
    tag: "⚡ Electrical",
    exp: "3 slots left",
    svc: "Electrical",
  },
  {
    id: 13,
    cat: "flash",
    badge: "Flash Sale",
    badgeType: "flash",
    name: "Emergency Plumbing Response",
    desc: "24/7 emergency service for urgent plumbing issues. Fast response guaranteed!",
    img: "https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?w=400&q=80",
    oldPrice: 600,
    price: 449,
    tag: "🚨 Plumbing",
    exp: "Today only",
    svc: "Plumbing",
  },
  {
    id: 14,
    cat: "promo",
    badge: "Save 35%",
    badgeType: "",
    name: "Complete House Painting",
    desc: "Interior & exterior painting package. Transform your entire home!",
    img: "https://images.unsplash.com/photo-1589939705384-5185137a7f0f?w=400&q=80",
    oldPrice: 8000,
    price: 5200,
    tag: "🏡 Painting",
    exp: "Big project special",
    svc: "Painting",
  },
];

let _offerFilter = "all";
function openAllOffers(filter) {
  _offerFilter = filter || "all";
  _cachedOffers = null; // always refresh from DB
  const m = document.getElementById("allOffersModal");
  if (m) {
    m.classList.add("on");
    loadAndRenderOffers();
  }
}
function closeAllOffers() {
  const m = document.getElementById("allOffersModal");
  if (m) m.classList.remove("on");
}
function setOfferFilter(cat) {
  _offerFilter = cat;
  document
    .querySelectorAll(".ao-tab")
    .forEach((t) => t.classList.toggle("on", t.dataset.cat === cat));
  loadAndRenderOffers();
}

let _cachedOffers = null;
function loadAndRenderOffers() {
  const cnt = document.getElementById("offersListCnt");
  if (!cnt) return;
  cnt.innerHTML =
    '<div style="text-align:center;padding:30px;color:#6b7280;font-size:13px;"><i class="bi bi-arrow-clockwise"></i> Loading offers...</div>';

  const doRender = (offers) => {
    _cachedOffers = offers;
    const SVC_IMGS_MAP = {
      Cleaning:
        "https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=300&q=80",
      Plumbing:
        "https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=300&q=80",
      Electrical:
        "https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=300&q=80",
      Painting:
        "https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=300&q=80",
      Gardening:
        "https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=300&q=80",
    };
    if (!offers.length) {
      cnt.innerHTML =
        '<div style="text-align:center;padding:40px 20px;color:#6b7280;"><i class="bi bi-tag" style="font-size:36px;display:block;margin-bottom:10px;opacity:.3;"></i><p style="font-size:13px;">No active offers right now.<br>Check back soon!</p></div>';
      return;
    }
    cnt.innerHTML = offers
      .map((o) => {
        const discLbl =
          o.discount_type === "percent"
            ? `${o.discount_value}% OFF`
            : `₱${parseFloat(o.discount_value).toLocaleString()} OFF`;
        const expLbl = o.expires_at
          ? "🕐 Expires " + o.expires_at.split(" ")[0]
          : "✅ No expiry";
        const minLbl =
          parseFloat(o.min_booking_price) > 0
            ? `Min. ₱${parseFloat(o.min_booking_price).toLocaleString()} booking`
            : "No minimum";
        const img = SVC_IMGS_MAP["Cleaning"];
        return `
        <div class="offer-card" style="background:var(--bg-card,#fff);border-radius:16px;overflow:hidden;margin-bottom:14px;box-shadow:0 2px 12px rgba(0,0,0,.07);">
          <div style="background:linear-gradient(135deg,#F5A623,#FDECC8);padding:18px 20px;display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="color:rgba(255,255,255,.85);font-size:10px;font-weight:800;letter-spacing:.8px;margin-bottom:4px;">SPECIAL OFFER</div>
              <div style="color:#fff;font-size:18px;font-weight:800;font-family:'Poppins',sans-serif;">${discLbl}</div>
              <div style="color:rgba(255,255,255,.85);font-size:12px;margin-top:2px;">${o.title}</div>
            </div>
            <div style="background:rgba(255,255,255,.2);border-radius:12px;padding:10px 14px;text-align:center;">
              <div style="color:#fff;font-size:11px;font-weight:700;">CODE</div>
              <div style="color:#fff;font-size:16px;font-weight:900;letter-spacing:1px;">${o.code}</div>
            </div>
          </div>
          <div style="padding:12px 16px;">
            ${o.description ? `<div style="font-size:12px;color:#6b7280;margin-bottom:8px;">${o.description}</div>` : ""}
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#6b7280;font-weight:600;">
              <span>📋 ${minLbl}</span>
              <span>${expLbl}</span>
            </div>
          </div>
        </div>`;
      })
      .join("");
  };

  // Use cache if available, otherwise fetch
  if (_cachedOffers !== null) {
    doRender(_cachedOffers);
    return;
  }
  fetch("api/bookings_api.php?action=offers")
    .then((r) => r.json())
    .then((data) => doRender(data.success ? data.offers : []))
    .catch(() => {
      cnt.innerHTML =
        '<div style="text-align:center;padding:30px;color:#ef4444;font-size:13px;">Could not load offers.</div>';
    });
}

function renderOffers() {
  loadAndRenderOffers();
} // backward compat

function injectGlobalModals() {
  const shell = document.querySelector(".shell");
  if (!shell || document.getElementById("searchModal")) return;

  const srch = document.createElement("div");
  srch.id = "searchModal";
  srch.onclick = function (e) {
    if (e.target === this) closeSearch();
  };
  srch.innerHTML = `
    <div class="srch-panel">
      <div class="srch-top">
        <div class="srch-inp-wrap">
          <i class="bi bi-search srch-icon"></i>
          <input id="srchInp" class="srch-inp" placeholder="Search services..." oninput="renderSearch(this.value)">
        </div>
        <span class="srch-cancel" onclick="closeSearch()">Cancel</span>
      </div>
      <div class="srch-results" id="srchResults"></div>
    </div>
    <div class="srch-dismiss" onclick="closeSearch()"></div>`;
  shell.appendChild(srch);

  const sup = document.createElement("div");
  sup.id = "supportModal";
  sup.onclick = function (e) {
    if (e.target === this) closeSupport();
  };
  sup.innerHTML = `
    <div class="sup-sheet">
      <div class="sup-hand"></div>
      <div class="sup-ttl">Support Center</div>
      <div class="sup-sub">How can we help you today?</div>
      <div class="sup-item" onclick="openHelp()">
        <div class="sup-ic">❓</div>
        <div class="sup-txt"><div class="sup-nm">Help Center</div><div class="sup-ds">Browse FAQs and guides</div></div>
        <i class="bi bi-chevron-right sup-arr"></i>
      </div>
      <div class="sup-item" onclick="openContact()">
        <div class="sup-ic">💬</div>
        <div class="sup-txt"><div class="sup-nm">Contact Us</div><div class="sup-ds">Send us a message</div></div>
        <i class="bi bi-chevron-right sup-arr"></i>
      </div>
      <div class="sup-item" onclick="closeSupport();openChat('support')">
        <div class="sup-ic">🎧</div>
        <div class="sup-txt"><div class="sup-nm">Live Chat — Support</div><div class="sup-ds">Chat with customer support now</div></div>
        <i class="bi bi-chevron-right sup-arr"></i>
      </div>
      <div class="sup-item" onclick="closeSupport();openChat('admin')">
        <div class="sup-ic">🏠</div>
        <div class="sup-txt"><div class="sup-nm">Live Chat — Admin</div><div class="sup-ds">Message the HomeEase admin team</div></div>
        <i class="bi bi-chevron-right sup-arr"></i>
      </div>
      <div class="sup-item" onclick="openRating()">
        <div class="sup-ic">⭐</div>
        <div class="sup-txt"><div class="sup-nm">Rate HomeEase</div><div class="sup-ds">Share your experience</div></div>
        <i class="bi bi-chevron-right sup-arr"></i>
      </div>
    </div>`;
  shell.appendChild(sup);

  const help = document.createElement("div");
  help.id = "helpModal";
  help.onclick = function (e) {
    if (e.target === this) closeHelp();
  };
  const faqs = [
    [
      "How do I book a service?",
      "Tap the + button or select any service from the Home screen. Fill in your preferred date, time, address, and submit your booking.",
    ],
    [
      "Can I cancel a booking?",
      "Yes! Go to Bookings, find your pending booking, and tap Cancel. Cancellations within 1 hour of the scheduled time may incur a fee.",
    ],
    [
      "How are prices calculated?",
      "We offer both hourly and flat-rate pricing. Hourly rates depend on the service and duration. Flat rates are fixed regardless of time.",
    ],
    [
      "How do I contact my service provider?",
      "Once your booking is confirmed, you'll receive the provider's contact number via notification.",
    ],
    [
      "What payment methods are accepted?",
      "We accept GCash, Maya, bank transfer, and cash on service. Online payment options appear at checkout.",
    ],
    [
      "Is my data safe?",
      "Yes! We use industry-standard encryption. Your personal data is never sold to third parties.",
    ],
    [
      "How do I use bookmarks?",
      "Tap the 🔖 bookmark icon on any booking card to save it. Access all saved bookings from your Profile → Saved Bookings.",
    ],
    [
      "How does Live Chat work?",
      "Open Support → Live Chat to instantly message our support team or admin. We reply within minutes during business hours.",
    ],
  ];
  help.innerHTML = `
    <div class="help-sheet">
      <div class="help-hand"></div>
      <div style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:var(--txt-primary);margin-bottom:4px;">Help Center</div>
      <div style="font-size:13px;color:var(--txt-muted);margin-bottom:18px;">Frequently asked questions</div>
      ${faqs
        .map(
          ([q, a]) => `
      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">${q}<i class="bi bi-chevron-down faq-icon"></i></div>
        <div class="faq-a">${a}</div>
      </div>`,
        )
        .join("")}
      <button class="btn-p" style="margin-top:22px;" onclick="closeHelp();openContact()">Still need help? Contact Us</button>
    </div>`;
  shell.appendChild(help);

  const cont = document.createElement("div");
  cont.id = "contactModal";
  cont.onclick = function (e) {
    if (e.target === this) closeContact();
  };
  cont.innerHTML = `
    <div class="cont-sheet">
      <div class="cont-hand"></div>
      <div style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:var(--txt-primary);margin-bottom:4px;">Contact Us</div>
      <div style="font-size:13px;color:var(--txt-muted);margin-bottom:18px;">Reach us through any channel</div>
      <div class="cont-channel" onclick="closeContact();openChat('support')">
        <div class="cont-ch-ic"><i class="bi bi-chat-dots-fill"></i></div>
        <div><div class="cont-ch-nm">Live Chat</div><div class="cont-ch-ds">Instant messaging with support</div></div>
      </div>
      <div class="cont-channel" onclick="showToast('Opening Messenger...')">
        <div class="cont-ch-ic"><i class="bi bi-messenger"></i></div>
        <div><div class="cont-ch-nm">Facebook Messenger</div><div class="cont-ch-ds">Usually replies within minutes</div></div>
      </div>
      <div class="cont-channel" onclick="showToast('Calling support hotline...')">
        <div class="cont-ch-ic"><i class="bi bi-telephone-fill"></i></div>
        <div><div class="cont-ch-nm">Call Us</div><div class="cont-ch-ds">+63 2 8XXX-XXXX · Mon-Sat 8AM-8PM</div></div>
      </div>
      <div class="cont-channel" onclick="showToast('Opening email...')">
        <div class="cont-ch-ic"><i class="bi bi-envelope-fill"></i></div>
        <div><div class="cont-ch-nm">Email Support</div><div class="cont-ch-ds">support@homeease.ph</div></div>
      </div>
      <div class="cont-or">or send a message below</div>
      <input class="cont-inp" placeholder="Your name" id="contName">
      <textarea class="cont-inp cont-ta" placeholder="Describe your concern..." id="contMsg"></textarea>
      <button class="btn-p" onclick="submitContact()">Send Message</button>
    </div>`;
  shell.appendChild(cont);

  const rat = document.createElement("div");
  rat.id = "ratingModal";
  rat.onclick = function (e) {
    if (e.target === this) closeRating();
  };
  rat.innerHTML = `
    <div class="rat-sheet">
      <div class="rat-hand"></div>
      <div style="font-size:48px;margin-bottom:4px;">🏠</div>
      <div style="font-family:'Poppins',sans-serif;font-size:19px;font-weight:800;color:var(--txt-primary);">Rate HomeEase</div>
      <div style="font-size:13px;color:var(--txt-muted);margin-top:4px;">How would you rate your overall experience?</div>
      <div class="rat-stars">
        <span class="rat-star" onclick="setRating(1)">⭐</span>
        <span class="rat-star" onclick="setRating(2)">⭐</span>
        <span class="rat-star" onclick="setRating(3)">⭐</span>
        <span class="rat-star" onclick="setRating(4)">⭐</span>
        <span class="rat-star" onclick="setRating(5)">⭐</span>
      </div>
      <textarea class="rat-ta" id="ratTa" placeholder="Tell us what you think... (optional)"></textarea>
      <button class="btn-p" onclick="submitRating()">Submit Rating</button>
    </div>`;
  shell.appendChild(rat);

  const as = document.createElement("div");
  as.id = "allServicesModal";
  as.onclick = function (e) {
    if (e.target === this) closeAllServices();
  };
  const popAll = [
    {
      svc: "Cleaning",
      title: "Deep Home Cleaning",
      img: "https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=300&q=80",
      price: "₱599",
    },
    {
      svc: "Plumbing",
      title: "Pipe Leak Repair",
      img: "https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=300&q=80",
      price: "₱450",
    },
    {
      svc: "Electrical",
      title: "Electrical Wiring",
      img: "https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=300&q=80",
      price: "₱750",
    },
    {
      svc: "Gardening",
      title: "Garden Makeover",
      img: "https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=300&q=80",
      price: "₱850",
    },
    {
      svc: "Painting",
      title: "Interior Painting",
      img: "https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=300&q=80",
      price: "₱800",
    },
    {
      svc: "Appliance Repair",
      title: "Appliance Repair",
      img: "https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=300&q=80",
      price: "₱650",
    },
  ];
  as.innerHTML = `
    <div class="as-sheet">
      <div class="as-hand"></div>
      <div style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:var(--txt-primary);">All Popular Services</div>
      <div class="as-grid">
        ${popAll
          .map(
            (p) => `
        <div class="as-card" onclick="closeAllServices();goPage('bookings.php?svc=${encodeURIComponent(p.svc)}&newbooking=1')">
          <img src="${p.img}" alt="${p.title}">
          <div class="as-info"><div class="as-nm">${p.title}</div><div class="as-pr">from ${p.price}</div></div>
        </div>`,
          )
          .join("")}
      </div>
    </div>`;
  shell.appendChild(as);

  const saved = document.createElement("div");
  saved.id = "savedModal";
  saved.onclick = function (e) {
    if (e.target === this) closeSaved();
  };
  saved.innerHTML = `
    <div class="saved-sheet">
      <div class="saved-hand"></div>
      <div class="saved-hdr">
        <div class="saved-hdr-ttl">🔖 Saved Bookings</div>
        <div class="saved-hdr-cnt" id="savedCount">0 saved</div>
      </div>
      <div id="savedList"></div>
    </div>`;
  shell.appendChild(saved);

  const offers = document.createElement("div");
  offers.id = "allOffersModal";
  offers.onclick = function (e) {
    if (e.target === this) closeAllOffers();
  };
  offers.innerHTML = `
    <div class="ao-sheet">
      <div class="ao-hand"></div>
      <div class="ao-hdr">
        <div class="ao-ttl">🏷️ Special Offers</div>
      </div>
      <div class="ao-sub">Exclusive deals for HomeEase customers</div>
      <div id="offersListCnt"></div>
    </div>`;
  shell.appendChild(offers);

  const chat = document.createElement("div");
  chat.id = "chatModal";
  chat.onclick = function (e) {
    if (e.target === this) closeChat();
  };
  chat.innerHTML = `
    <div class="chat-sheet">
      <div style="position:relative;">
        <div class="chat-hdr-hand"></div>
      </div>
      <div class="chat-hdr">
        <div class="chat-av">
          <i class="bi bi-headset"></i>
          <div class="chat-av-dot"></div>
        </div>
        <div class="chat-hdr-info">
          <div class="chat-hdr-nm" id="chatAgentNm">HomeEase Customer Service</div>
          <div class="chat-hdr-st">● Online</div>
        </div>
        <button style="background:none;border:none;color:var(--txt-muted);font-size:22px;cursor:pointer;" onclick="closeChat()">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="chat-msgs" id="chatMsgs"></div>
      <div class="chat-quick" id="chatQuick"></div>
      <div class="chat-inp-bar">
        <textarea class="chat-inp" id="chatInp" placeholder="Type your message..." rows="1"
          oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat();}"></textarea>
        <button class="chat-send" onclick="sendChat()"><i class="bi bi-send-fill"></i></button>
      </div>
    </div>`;
  shell.appendChild(chat);
}

let pinStep = "set",
  firstPin = "",
  curPin = "";
function PP(n) {
  if (curPin.length >= 4) return;
  curPin += n;
  updPinDots();
  if (curPin.length === 4) {
    setTimeout(() => {
      if (pinStep === "set") {
        firstPin = curPin;
        curPin = "";
        pinStep = "confirm";
        document.getElementById("pinTtl").textContent = "Confirm Your PIN";
        document.getElementById("pinSb").textContent =
          "Enter the same PIN again to confirm";
        updPinDots();
      } else {
        if (curPin === firstPin) {
          goPage("home.php");
        } else {
          document.querySelectorAll(".pin-d").forEach((d) => {
            d.classList.add("err");
            setTimeout(() => d.classList.remove("err"), 500);
          });
          curPin = "";
          document.getElementById("pinSb").textContent =
            "PINs don't match. Try again.";
          updPinDots();
        }
      }
    }, 120);
  }
}
function PD() {
  curPin = curPin.slice(0, -1);
  updPinDots();
}
function updPinDots() {
  const dots = document.querySelectorAll(".pin-d");
  if (dots) dots.forEach((d, i) => d.classList.toggle("f", i < curPin.length));
}

function tPwd(id, icon) {
  const inp = document.getElementById(id);
  inp.type = inp.type === "password" ? "text" : "password";
  icon.classList.toggle("bi-eye-fill");
  icon.classList.toggle("bi-eye-slash-fill");
}
function fmtTime(t) {
  const [h, m] = t.split(":").map(Number);
  const ap = h >= 12 ? "PM" : "AM";
  return `${h % 12 || 12}:${String(m).padStart(2, "0")} ${ap}`;
}
function fmtDate(d) {
  return new Date(d).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

const FB_SVG = `<svg viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.413c0-3.026 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.514c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>`;
const GG_SVG = `<svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>`;
const AP_SVG = `<svg viewBox="0 0 24 24" fill="#000"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>`;

window.addEventListener("DOMContentLoaded", injectGlobalModals);
