<?php

namespace Coremetrics\CoremetricsLaravel;

class TagCollection
{
    const REQUEST_STARTED = 1;
    const REQUEST_ROUTE_MATCHED = 2;
    const REQUEST_HANDLED = 3;
    const APP_TERMINATING = 4;
    const QUERY = 5;
    const TRANSACTION_ROLLED_BACK = 6;
    const TRANSACTION_COMMITTED = 7;
    const TRANSACTION_BEGINNING = 8;
    const CACHE_MISSED = 9;
    const CACHE_HIT = 10;
    const MAIL_SENDING = 11;
    const MAIL_SENT = 12;
    const MSG_LOGGED = 13;
    const MIDDLEWARE_START = 14;
    const MIDDLEWARE_END = 15;
    const REDIS_COMMAND = 16;
}