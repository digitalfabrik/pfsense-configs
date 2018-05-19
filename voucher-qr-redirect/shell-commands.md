* Start nginx: `/usr/local/sbin/nginx -c /root/nginx-CaptivePortal-redirect.conf`
* View nginx processes: `ps -A | grep nginx`
* Test QR redirect: `curl -L -vv "redirect.wlan.tuerantuer.org/?voucher=test"`