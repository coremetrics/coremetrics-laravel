<?php

namespace Larameter;

class TagCollection
{
    CONST REQUEST_STARTED = 1;
    CONST REQUEST_ROUTE_MATCHED = 2;
    CONST REQUEST_HANDLED = 3;
    CONST APP_TERMINATING = 4;
    CONST QUERY = 5;
    const TRANSACTION_ROLLED_BACK = 6;
    const TRANSACTION_COMMITED = 7;
    const TRANSACTION_BEGINNING = 8;
    const CACHE_MISSED = 9;
    const CACHE_HIT = 10;
    const MAIL_SENDING  = 11;
    const MAIL_SENT = 12;
    const MSG_LOGGED = 13;
    const MIDDLEWARE_START = 14;
    const MIDDLEWARE_END = 15;

}