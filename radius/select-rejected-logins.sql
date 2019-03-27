SELECT username, 
(SELECT `value` FROM radius.radcheck where attribute = "Max-All-Session" AND radius.radcheck.username = radius.radpostauth.username) as max_time,
(SELECT acctstarttime FROM radius.radacct where radius.radacct.username = radius.radpostauth.username order by acctstarttime limit 1) as start_time
FROM radius.radpostauth 
where username != "" and reply = "Access-Reject"
order by authdate desc limit 100;