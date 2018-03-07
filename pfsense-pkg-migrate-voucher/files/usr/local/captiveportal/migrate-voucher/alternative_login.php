<?php
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo('Invalid request method!');
    http_response_code(500);
    die();
}

http_response_code(200);

if (!isset($_GET['message'])) {
    ?>
    <script type="text/javascript">
        window.onload = function () {
            document.forms["proxy-form"].submit();
        }
    </script>
    <?php
}

echo($_GET['message'])
?>

<form name='proxy-form' method="post" action="<?php echo($_GET['action']) ?>">
    <input name="auth_user" type="hidden" value="<?php echo($_GET['auth_user']) ?>">
    <input name="auth_pass" type="hidden" value="<?php echo($_GET['auth_pass']) ?>">
    <input name="auth_voucher" type="hidden" value="<?php echo($_GET['auth_voucher']) ?>">
    <input name="redirurl" type="hidden" value="<?php echo($_GET['redirurl']) ?>">
    <input name="zone" type="hidden" value="<?php echo($_GET['zone']) ?>">
    <input name="accept" type="submit" value="Continue">

    <input name="message" type="hidden" value="<?php echo($_GET['message']) ?>">
</form>