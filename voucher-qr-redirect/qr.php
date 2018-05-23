<?php
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo('Invalid request method!');
    http_response_code(500);
    die();
}

http_response_code(200);
?>

<script type="text/javascript">
    window.onload = function () {
        document.proxyForm.submit();
    }
</script>

<form name='proxyForm' method="post" action="/captiveportal-migrate.php">
    <input name="auth_user" type="hidden" value="">
    <input name="auth_pass" type="hidden" value="">
    <input name="auth_voucher" type="hidden" value="<?php echo($_GET['voucher']) ?>">
    <input name="redirurl" type="hidden" value="https://www.integreat-app.de/">
    <input name="zone" type="hidden" value="tatdf">
    <input name="action" type="hidden" value="<?php echo($_GET['action']) ?>">

    <!--Captiveportal needs this input and javascript does not submit if type=submit-->
    <input name="accept" type="hidden" value="dummy">

    <input name="doYourThing" type="submit" value="Klicken Sie hier, falls Sie nicht automatisch weitergeleitet werden!">
</form>