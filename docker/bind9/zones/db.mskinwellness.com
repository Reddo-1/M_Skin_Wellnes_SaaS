$TTL 86400
@       IN      SOA     ns1.mskinwellness.com. root.mskinwellness.com. (
                        2026060501
                        3600
                        1800
                        1209600
                        86400 )
@       IN      NS      ns1.mskinwellness.com.
ns1     IN      A       10.1.0.53
@       IN      A       10.1.0.50
www     IN      A       10.1.0.50
