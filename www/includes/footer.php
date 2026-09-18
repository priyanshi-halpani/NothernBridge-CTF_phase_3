<?php
/*
|--------------------------------------------------------------------------
| Shared site footer
|--------------------------------------------------------------------------
| Closes the document opened by each page. No internal paths are referenced.
|--------------------------------------------------------------------------
*/

?>
<style>
.nc-footer {
    margin-top: auto;
    background: #203329;
    color: #f6f3ec;
    text-align: center;
    padding: 1.1rem 1rem;
    font-size: 0.8rem;
    letter-spacing: 0.03em;
    font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
}
.nc-footer .nc-foot-brand {
    font-family: Georgia, 'Times New Roman', serif;
    letter-spacing: 0.08em;
    color: #a97c33;
    font-weight: 700;
}
.nc-footer a {
    color: #f6f3ec;
    text-decoration: underline;
    text-decoration-color: #a97c33;
}
.nc-footer a:hover {
    color: #a97c33;
}
</style>

<footer class="nc-footer">
    <p>
        <span class="nc-foot-brand">Northenbridge College</span>
        &nbsp;&middot;&nbsp;
        &copy; <?= date('Y') ?> Northenbridge College
        &nbsp;&middot;&nbsp;
        <a href="index.php">Home</a>
    </p>
</footer>
</body>
</html>