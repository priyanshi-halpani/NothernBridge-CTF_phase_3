<?php
/*
|--------------------------------------------------------------------------
| Shared site header / navigation
|--------------------------------------------------------------------------
| Inlined after <body> on every public-facing page. Provides a consistent
| college brand bar and primary navigation. Never references internal paths.
|--------------------------------------------------------------------------
*/

$nc_current = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$nc_student = isset($_SESSION['student_id']);

function nc_nav_class(string $target, string $current): string
{
    $active = $current === $target ? ' active' : '';
    return 'nc-nav-link' . $active;
}

?>
<style>
.nc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.7rem 1.4rem;
    background: #203329;
    color: #f6f3ec;
    font-family: Georgia, 'Times New Roman', serif;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.18);
    position: relative;
    z-index: 50;
}
.nc-header .nc-brand {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    text-decoration: none;
    color: inherit;
}
.nc-crest {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.2rem;
    height: 2.2rem;
    border: 2px solid #a97c33;
    border-radius: 50%;
    color: #a97c33;
    font-weight: 700;
    font-size: 0.72rem;
    letter-spacing: 0.06em;
    background: rgba(169, 124, 51, 0.08);
}
.nc-wordmark {
    font-weight: 700;
    font-size: 1.05rem;
    letter-spacing: 0.08em;
    line-height: 1.05;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}
.nc-wordmark small {
    font-size: 0.58rem;
    letter-spacing: 0.32em;
    color: #a97c33;
    font-weight: 400;
}
.nc-nav {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem;
    font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
    font-size: 0.86rem;
}
.nc-nav a {
    color: #f6f3ec;
    text-decoration: none;
    padding: 0.36rem 0.75rem;
    border-radius: 4px;
    transition: background 0.15s ease, color 0.15s ease;
}
.nc-nav a:hover {
    background: rgba(255, 255, 255, 0.12);
}
.nc-nav a.active {
    color: #a97c33;
}
.nc-nav a.nc-cta {
    background: #a97c33;
    color: #1c2a24;
    font-weight: 600;
}
.nc-nav a.nc-cta:hover {
    background: #b98f47;
    color: #1c2a24;
}
@media (max-width: 640px) {
    .nc-header { flex-direction: column; align-items: flex-start; }
    .nc-nav { width: 100%; }
}
</style>

<header class="nc-header">
    <a class="nc-brand" href="index.php" title="Northenbridge College home">
        <span class="nc-crest">NC</span>
        <span class="nc-wordmark">
            NORTHENBRIDGE
            <small>COLLEGE</small>
        </span>
    </a>

    <nav class="nc-nav" aria-label="Primary">
        <a class="<?= nc_nav_class('index.php', $nc_current) ?>" href="index.php">Home</a>
        <a class="<?= nc_nav_class('academics.php', $nc_current) ?>" href="academics.php">Academics</a>
        <a class="<?= nc_nav_class('admissions.php', $nc_current) ?>" href="admissions.php">Admissions</a>
        <a class="<?= nc_nav_class('events.php', $nc_current) ?>" href="events.php">Events</a>
        <a class="<?= nc_nav_class('about.php', $nc_current) ?>" href="about.php">About</a>
        <a class="<?= nc_nav_class('contact.php', $nc_current) ?>" href="contact.php">Contact</a>

        <?php if ($nc_student): ?>
            <a class="<?= nc_nav_class('profile.php', $nc_current) ?>" href="profile.php">Profile</a>
            <a class="<?= nc_nav_class('marks.php', $nc_current) ?>" href="marks.php">Exam Result</a>
            <a class="nc-nav-link nc-cta" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="<?= nc_nav_class('register.php', $nc_current) ?>" href="register.php">Register</a>
            <a class="nc-nav-link nc-cta" href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</header>