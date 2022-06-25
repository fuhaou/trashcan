<?php

namespace App\Constants;

class CacheConst
{
    const IP_LOCAL_KEY = 'ip_'; // this is prefix, need to add IP local
    const IP_LOCAL_KEY_TIMELIFE = 900; // 60*15 = 15 minutes

    const CRED_KEY = 'cred_'; // this is prefix, need to add shop eid / type / action
    const CRED_KEY_TIMELIFE = 900; // 60*15 = 15 minutes

    const SHOP_LIST_KEY = 'shop_list_'; // this is prefix, need to add page / limit / ...
    const SHOP_LIST_KEY_TIMELIFE = 900; // 60*15 = 15 minutes

    const ACCOUNT_LIST_KEY = 'account_list_'; // this is prefix, need to add page / limit / ...
    const ACCOUNT_LIST_KEY_TIMELIFE = 900; // 60*15 = 15 minutes
}
