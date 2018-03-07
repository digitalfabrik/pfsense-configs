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

<form name='proxyForm' method="post" action="<?php echo($_GET['action']) ?>">
    <input name="auth_user" type="hidden" value="<?php echo($_GET['auth_user']) ?>">
    <input name="auth_pass" type="hidden" value="<?php echo($_GET['auth_pass']) ?>">
    <input name="auth_voucher" type="hidden" value="<?php echo($_GET['auth_voucher']) ?>">
    <input name="redirurl" type="hidden" value="<?php echo($_GET['redirurl']) ?>">
    <input name="zone" type="hidden" value="<?php echo($_GET['zone']) ?>">

    <!--Captiveportal needs this input and javascript does not submit if type=submit-->
    <input name="accept" type="hidden">

    <input name="doYourThing" type="submit" value="Klicken Sie hier, falls Sie nicht automatisch weitergeleitet werden!">
</form>