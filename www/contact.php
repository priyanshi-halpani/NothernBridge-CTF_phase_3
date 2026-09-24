<?php
// ---------------------------------------------------------
// Northenbridge College — Contact
// ---------------------------------------------------------

require_once __DIR__ . '/includes/init.php';

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $error = 'Please complete all fields before sending.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $sent = true;
    }
}

$year = date("Y");
$offices = [
    ["dept" => "Admissions Office", "email" => "admissions@northenbridge", "phone" => "+1 (555) 010-2201", "hours" => "Mon–Fri, 8:30 AM – 4:30 PM"],
    ["dept" => "Bursar's Office", "email" => "bursar@northenbridge", "phone" => "+1 (555) 010-2202", "hours" => "Mon–Fri, 9:00 AM – 3:00 PM"],
    ["dept" => "Student Records", "email" => "records@northenbridge", "phone" => "+1 (555) 010-2203", "hours" => "Mon–Fri, 8:30 AM – 4:30 PM"],
    ["dept" => "IT Support Desk", "email" => "helpdesk@northenbridge", "phone" => "+1 (555) 010-2204", "hours" => "Mon–Fri, 7:30 AM – 6:00 PM"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact — Northenbridge College</title>
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
        max-width: 60ch;
        margin: 0;
    }

    /* ---------- Contact layout ---------- */
    .contact-wrap {
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

    .contact-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 2rem;
        align-items: start;
    }

    /* ---------- Form ---------- */
    .form-card {
        border: 1px solid var(--line);
        background: #fffdf8;
        padding: 1.8rem 1.9rem;
        border-radius: 3px;
        border-top: 3px solid var(--brass);
    }

    .form-card h3 {
        font-size: 1.15rem;
        margin: 0 0 0.4rem;
        color: var(--hedge-dark);
    }

    .form-card > p {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.9rem;
        color: var(--muted);
        margin: 0 0 1.4rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.1rem;
    }

    .field { display: flex; flex-direction: column; }
    .field.full { grid-column: 1 / -1; }

    label {
        margin-bottom: 0.4rem;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.82rem;
        font-weight: 600;
    }

    input, textarea {
        width: 100%;
        padding: 0.65rem 0.8rem;
        border: 1px solid var(--line);
        border-radius: 3px;
        background: #ffffff;
        color: var(--ink);
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.92rem;
        outline: none;
    }

    input:focus, textarea:focus {
        border-color: var(--brass);
        box-shadow: 0 0 0 2px rgba(169,124,51,0.10);
    }

    textarea { min-height: 130px; resize: vertical; }

    .submit-btn {
        margin-top: 1.3rem;
        background: var(--hedge);
        color: #fff;
        border: none;
        padding: 0.7rem 1.6rem;
        border-radius: 3px;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .submit-btn:hover { background: var(--hedge-dark); }

    .alert {
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.88rem;
        padding: 0.85rem 1rem;
        margin-bottom: 1.2rem;
        border-radius: 3px;
    }

    .alert.error {
        background: #f7e9e6;
        border-left: 4px solid #7d3027;
        color: #7d3027;
    }

    .alert.success {
        background: #e7f0e9;
        border-left: 4px solid #245837;
        color: #245837;
    }

    /* ---------- Info ---------- */
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }

    .info-card {
        border: 1px solid var(--line);
        background: #fffdf8;
        padding: 1.2rem 1.3rem;
        border-radius: 3px;
        border-left: 3px solid var(--hedge);
    }

    .info-card h3 {
        font-size: 1.02rem;
        margin: 0 0 0.35rem;
        color: var(--hedge-dark);
    }

    .info-card p {
        margin: 0.15rem 0;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.87rem;
        color: var(--muted);
    }

    .info-card p span {
        display: inline-block;
        min-width: 5rem;
        color: var(--hedge);
        font-weight: 600;
    }

    .campus-card {
        border: 1px solid var(--line);
        background: var(--hedge-dark);
        color: var(--parchment);
        padding: 1.4rem 1.4rem;
        border-radius: 3px;
        border-top: 3px solid var(--brass);
    }

    .campus-card h3 {
        font-size: 1.05rem;
        margin: 0 0 0.6rem;
    }

    .campus-card p {
        margin: 0.2rem 0;
        font-family: "Segoe UI", Helvetica, Arial, sans-serif;
        font-size: 0.88rem;
        color: #d8ded9;
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 860px) {
        .contact-grid { grid-template-columns: 1fr; }
        .page-hero h1 { font-size: 1.9rem; }
    }

    @media (max-width: 560px) {
        .form-grid { grid-template-columns: 1fr; }
        .field.full { grid-column: auto; }
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
        <div class="hero-motto sans">CONTACT US</div>
        <h1>We're Here to Help</h1>
        <p class="lede">Questions about admissions, records, fees, or your student account? Reach the right office directly, or send us a message through the form.</p>
    </div>
</section>

<div class="contact-wrap">
    <div class="contact-grid">
        <div>
            <div class="section-head">
                <h2>Send a Message</h2>
            </div>

            <div class="form-card">
                <h3>General Inquiries</h3>
                <p>We respond to messages during regular business hours, usually within one working day.</p>

                <?php if ($error !== ''): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($sent): ?>
                <div class="alert success">Thank you — your message has been received and will be routed to the appropriate office.</div>
                <?php endif; ?>

                <form method="POST" action="contact.php">
                    <div class="form-grid">
                        <div class="field">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        <div class="field">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div class="field full">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                        </div>
                        <div class="field full">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>

        <div>
            <div class="section-head">
                <h2>Office Directory</h2>
            </div>

            <div class="info-list">
                <?php foreach ($offices as $o): ?>
                <div class="info-card">
                    <h3><?php echo htmlspecialchars($o['dept']); ?></h3>
                    <p><span>Email</span> <?php echo htmlspecialchars($o['email']); ?></p>
                    <p><span>Phone</span> <?php echo htmlspecialchars($o['phone']); ?></p>
                    <p><span>Hours</span> <?php echo htmlspecialchars($o['hours']); ?></p>
                </div>
                <?php endforeach; ?>

                <div class="campus-card">
                    <h3>Main Campus</h3>
                    <p>120 High Meadow Road, Whitfield</p>
                    <p>Admission visits by appointment, Monday through Saturday.</p>
                    <p>Est. <?php echo $year - 58; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>