<?php
// ---------------------------------------------------------
// Northenbridge College — Home Page
// ---------------------------------------------------------

require_once __DIR__ . '/includes/init.php';

$announcements = [
    "Fall semester registration closes September 20 — enroll through the student portal before the deadline.",
    "The Bursar's Office will be closed Monday, September 14 for a staff training day.",
    "Library extended hours begin this week: open until midnight Sunday through Thursday.",
    "New shuttle route added between North Campus and the Innovation Building, effective immediately.",
    "Financial aid disbursements for the fall term begin processing September 18.",
    "Career Services is now booking one-on-one resume reviews for graduating seniors.",
];

$events = [
    [
        "title" => "Fall Open House",
        "date"  => "Sept 20, 2026",
        "desc"  => "Tour the campus, meet faculty, and sit in on a live lecture in the sciences building.",
    ],
    [
        "title" => "Founders' Day Lecture",
        "date"  => "Sept 27, 2026",
        "desc"  => "Alumna Dr. Priya Menon returns to campus to speak on climate resilience engineering.",
    ],
    [
        "title" => "Career & Internship Fair",
        "date"  => "Oct 3, 2026",
        "desc"  => "Over 40 employers on site in the Commons. Bring copies of your resume.",
    ],
    [
        "title" => "Homecoming Weekend",
        "date"  => "Oct 17–18, 2026",
        "desc"  => "Reunion brunch, the alumni match on Whitfield Field, and the evening bonfire.",
    ],
];

$announcement = $announcements[array_rand($announcements)];
$year = date("Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Northenbridge College</title>
<style>
    :root {
        --ink:        #1c2a24;
        --parchment:  #f6f3ec;
        --hedge:      #2f4a3d;
        --hedge-dark: #203329;
        --brass:      #a97c33;
        --line:       #d9d2c2;
        --muted:      #5b6b62;
        font-size: 16px;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        background: var(--parchment);
        color: var(--ink);
        font-family: "Iowan Old Style", "Palatino Linotype", Georgia, serif;
        line-height: 1.55;
    }

    h1, h2, h3, .brand-word {
        font-family: "Iowan Old Style", Georgia, serif;
        font-weight: 600;
        letter-spacing: 0.01em;
    }

    .sans {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
    }

    a { color: inherit; }

    /* ---------- Nav ---------- */
    header.site-nav {
        background: var(--hedge-dark);
        color: var(--parchment);
        border-bottom: 3px solid var(--brass);
    }

    .nav-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0.9rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
    }

    .brand {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        text-decoration: none;
        color: var(--parchment);
    }

    .brand-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: 1.5px solid var(--brass);
        border-radius: 50%;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--brass);
    }

    .brand-word {
        font-size: 1.25rem;
    }

    .brand-word small {
        display: block;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.62rem;
        letter-spacing: 0.14em;
        color: #b9c4bb;
        font-weight: 400;
    }

    nav.primary-links {
        display: flex;
        align-items: center;
        gap: 1.75rem;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.93rem;
    }

    nav.primary-links a {
        text-decoration: none;
        color: #d8ded9;
        padding: 0.3rem 0;
        border-bottom: 2px solid transparent;
        transition: border-color 0.15s ease, color 0.15s ease;
    }

    nav.primary-links a:hover {
        color: #ffffff;
        border-bottom-color: var(--brass);
    }

    .nav-actions {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .btn-login {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.88rem;
        background: var(--brass);
        color: #221a0d;
        padding: 0.5rem 1.1rem;
        border-radius: 3px;
        text-decoration: none;
        font-weight: 600;
        white-space: nowrap;
        transition: background 0.15s ease;
    }

    .btn-login:hover {
        background: #c19248;
    }

    .menu-toggle {
        display: none;
        background: none;
        border: 1px solid #4b5f54;
        color: var(--parchment);
        font-size: 1.1rem;
        padding: 0.35rem 0.6rem;
        border-radius: 3px;
        cursor: pointer;
    }

    /* ---------- Hero ---------- */
    .hero {
        background: linear-gradient(180deg, var(--hedge) 0%, var(--hedge-dark) 100%);
        color: var(--parchment);
        padding: 3.5rem 1.5rem 3rem;
    }

    .hero-inner {
        max-width: 1100px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 2.5rem;
        align-items: center;
    }

    .hero h1 {
        font-size: 2.5rem;
        margin: 0 0 0.9rem;
        line-height: 1.2;
    }

    .hero p.lede {
        font-size: 1.08rem;
        color: #dfe6e1;
        max-width: 46ch;
    }

    .hero-motto {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.78rem;
        letter-spacing: 0.12em;
        color: var(--brass);
        margin-bottom: 0.6rem;
    }

    .hero-panel {
        border: 1px solid #46594e;
        background: rgba(255,255,255,0.03);
        padding: 1.5rem;
        border-radius: 4px;
    }

    .hero-panel dl {
        margin: 0;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.9rem;
    }

    .hero-panel dt {
        color: var(--brass);
        margin-top: 0.85rem;
    }

    .hero-panel dt:first-child { margin-top: 0; }

    .hero-panel dd {
        margin: 0.15rem 0 0;
        color: #e5e9e6;
    }

    /* ---------- Announcement strip ---------- */
    .announcement {
        background: var(--parchment);
        border-bottom: 1px solid var(--line);
    }

    .announcement-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0.85rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.93rem;
    }

    .announcement-tag {
        flex-shrink: 0;
        color: var(--hedge);
        font-weight: 700;
        border-right: 1px solid var(--line);
        padding-right: 0.8rem;
    }

    .announcement-inner p {
        margin: 0;
        color: #3c4841;
    }

    /* ---------- Events ---------- */
    .events-section {
        max-width: 1100px;
        margin: 0 auto;
        padding: 3rem 1.5rem 3.5rem;
    }

    .section-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        border-bottom: 1px solid var(--line);
        padding-bottom: 0.7rem;
        margin-bottom: 1.6rem;
    }

    .section-head h2 {
        font-size: 1.5rem;
        margin: 0;
        color: var(--hedge-dark);
    }

    .section-head a {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.88rem;
        text-decoration: none;
        color: var(--hedge);
        border-bottom: 1px solid var(--hedge);
    }

    .event-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }

    .event-card {
        border: 1px solid var(--line);
        background: #fffdf8;
        padding: 1.2rem;
        border-radius: 3px;
        border-top: 3px solid var(--hedge);
    }

    .event-date {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.78rem;
        color: var(--brass);
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .event-card h3 {
        font-size: 1.08rem;
        margin: 0.4rem 0 0.5rem;
        color: var(--hedge-dark);
    }

    .event-card p {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.87rem;
        color: var(--muted);
        margin: 0;
    }

    /* ---------- Footer ---------- */
    footer {
        background: var(--hedge-dark);
        color: #b9c4bb;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.83rem;
    }

    .footer-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 860px) {
        .hero-inner { grid-template-columns: 1fr; }
        .event-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 720px) {
        nav.primary-links { display: none; }
        .menu-toggle { display: inline-block; }
    }

    @media (max-width: 560px) {
        .event-grid { grid-template-columns: 1fr; }
        .hero h1 { font-size: 2rem; }
        .announcement-inner { flex-direction: column; align-items: flex-start; }
        .announcement-tag { border-right: none; padding-right: 0; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { transition: none !important; }
    }

    :focus-visible {
        outline: 2px solid var(--brass);
        outline-offset: 2px;
    }
</style>
</head>
<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<section class="hero">
    <div class="hero-inner">
        <div>
            <div class="hero-motto sans">ESTABLISHED IN PURPOSE, BUILT ON RIGOR</div>
            <h1>Northenbridge College</h1>
            <p class="lede">A small college with a clear focus: close mentorship, disciplined coursework, and a campus where every student is known by name.</p>
        </div>
        <div class="hero-panel">
            <dl>
                <dt>Undergraduate programs</dt>
                <dd>28 majors across 5 schools</dd>
                <dt>Student-faculty ratio</dt>
                <dd>11 : 1</dd>
                <dt>Fall term begins</dt>
                <dd>September 8, <?php echo $year; ?></dd>
            </dl>
        </div>
    </div>
</section>

<div class="announcement">
    <div class="announcement-inner">
        <span class="announcement-tag">Announcement</span>
        <p><?php echo htmlspecialchars($announcement); ?></p>
    </div>
</div>

<section class="events-section" id="events">
    <div class="section-head">
        <h2>Upcoming on Campus</h2>
        <a href="#events">View full calendar</a>
    </div>

    <div class="event-grid">
        <?php foreach ($events as $e): ?>
        <div class="event-card">
            <div class="event-date"><?php echo htmlspecialchars($e['date']); ?></div>
            <h3><?php echo htmlspecialchars($e['title']); ?></h3>
            <p><?php echo htmlspecialchars($e['desc']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
