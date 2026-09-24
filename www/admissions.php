<?php
// ---------------------------------------------------------
// Northenbridge College — Admissions
// ---------------------------------------------------------

require_once __DIR__ . '/includes/init.php';

$steps = [
    [
        "num"  => "01",
        "title" => "Create an Account",
        "desc" => "Register on the student portal and receive your Northenbridge credentials.",
    ],
    [
        "num"  => "02",
        "title" => "Submit Enrollment Details",
        "desc" => "Provide your contact information, date of birth, address, and chosen department.",
    ],
    [
        "num"  => "03",
        "title" => "Receive Your Student ID",
        "desc" => "A unique student ID and institutional email are generated for your record.",
    ],
    [
        "num"  => "04",
        "title" => "Begin Your Studies",
        "desc" => "Log in to the portal, view your profile, and access your examination results.",
    ],
];

$requirements = [
    "A completed online admissions application.",
    "Secondary school transcript or equivalent documentation.",
    "Government-issued photo identification.",
    "A current contact number and residential address.",
    "Intent to enroll in one of our accredited departments.",
];

$deadlines = [
    ["item" => "Early action application", "date" => "June 30, 2026"],
    ["item" => "Regular application deadline", "date" => "August 15, 2026"],
    ["item" => "Financial aid FAFSA filing", "date" => "August 28, 2026"],
    ["item" => "Fall semester registration closes", "date" => "September 20, 2026"],
];

$year = date("Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admissions — Northenbridge College</title>
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

    /* ---------- Page hero ---------- */
    .page-hero {
        background: linear-gradient(180deg, var(--hedge) 0%, var(--hedge-dark) 100%);
        color: var(--parchment);
        padding: 3rem 1.5rem 2.6rem;
    }

    .page-hero-inner {
        max-width: 1100px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 2.5rem;
        align-items: center;
    }

    .page-hero h1 {
        font-size: 2.3rem;
        margin: 0 0 0.8rem;
        line-height: 1.2;
    }

    .hero-motto {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.78rem;
        letter-spacing: 0.12em;
        color: var(--brass);
        margin-bottom: 0.6rem;
    }

    .page-hero p.lede {
        font-size: 1.05rem;
        color: #dfe6e1;
        max-width: 46ch;
        margin: 0;
    }

    .hero-panel {
        border: 1px solid #46594e;
        background: rgba(255,255,255,0.03);
        padding: 1.5rem;
        border-radius: 4px;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
    }

    .hero-panel h3 {
        font-family: "Iowan Old Style", Georgia, serif;
        color: var(--brass);
        margin: 0 0 0.8rem;
        font-size: 1.05rem;
    }

    .hero-panel p {
        margin: 0;
        color: #e5e9e6;
        font-size: 0.9rem;
    }

    .hero-cta {
        display: inline-block;
        margin-top: 1rem;
        background: var(--brass);
        color: #221a0d;
        text-decoration: none;
        padding: 0.55rem 1.2rem;
        border-radius: 3px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .hero-cta:hover { background: #c19248; }

    /* ---------- Section ---------- */
    .section {
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

    /* ---------- Steps ---------- */
    .steps-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }

    .step-card {
        border: 1px solid var(--line);
        background: #fffdf8;
        padding: 1.4rem;
        border-radius: 3px;
        border-top: 3px solid var(--hedge);
    }

    .step-num {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--brass);
        letter-spacing: 0.03em;
    }

    .step-card h3 {
        font-size: 1.05rem;
        margin: 0.5rem 0;
        color: var(--hedge-dark);
    }

    .step-card p {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.87rem;
        color: var(--muted);
        margin: 0;
    }

    /* ---------- Two-column layout ---------- */
    .split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        align-items: start;
    }

    .panel {
        border: 1px solid var(--line);
        background: #fffdf8;
        padding: 1.5rem 1.6rem;
        border-radius: 3px;
        border-top: 3px solid var(--brass);
    }

    .panel h3 {
        font-size: 1.15rem;
        margin: 0 0 0.9rem;
        color: var(--hedge-dark);
    }

    .panel ul {
        margin: 0;
        padding-left: 1.2rem;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.92rem;
    }

    .panel li { margin-bottom: 0.45rem; color: #3c4841; }

    .deadline-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding: 0.55rem 0;
        border-bottom: 1px solid var(--line);
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.9rem;
    }

    .deadline-row:last-child { border-bottom: none; }

    .deadline-item { color: var(--hedge); font-weight: 600; }

    .deadline-date { color: var(--muted); }

    /* ---------- Responsive ---------- */
    @media (max-width: 860px) {
        .page-hero-inner { grid-template-columns: 1fr; }
        .steps-grid { grid-template-columns: repeat(2, 1fr); }
        .split { grid-template-columns: 1fr; }
    }

    @media (max-width: 560px) {
        .steps-grid { grid-template-columns: 1fr; }
        .page-hero h1 { font-size: 1.9rem; }
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

<section class="page-hero">
    <div class="page-hero-inner">
        <div>
            <div class="hero-motto sans">ADMISSIONS</div>
            <h1>Your Path to Northenbridge</h1>
            <p class="lede">Admission is rolling for the fall term. Create your student account and enroll in your chosen department in under ten minutes.</p>
        </div>
        <div class="hero-panel">
            <h3>Fall <?php echo $year; ?> Term</h3>
            <p>Applications for the fall semester are open. The regular application deadline is August 15, <?php echo $year; ?>.</p>
            <a class="hero-cta" href="register.php">Start Application</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <h2>How to Apply</h2>
    </div>
    <div class="steps-grid">
        <?php foreach ($steps as $s): ?>
        <div class="step-card">
            <div class="step-num"><?php echo htmlspecialchars($s['num']); ?></div>
            <h3><?php echo htmlspecialchars($s['title']); ?></h3>
            <p><?php echo htmlspecialchars($s['desc']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="split">
        <div class="panel">
            <h3>Admissions Requirements</h3>
            <ul>
                <?php foreach ($requirements as $r): ?>
                <li><?php echo htmlspecialchars($r); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="panel">
            <h3>Key Dates &amp; Deadlines</h3>
            <?php foreach ($deadlines as $d): ?>
            <div class="deadline-row">
                <span class="deadline-item"><?php echo htmlspecialchars($d['item']); ?></span>
                <span class="deadline-date"><?php echo htmlspecialchars($d['date']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>