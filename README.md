# coremetrics-laravel

Installation (Host app):

```
"require": {
        "coremetrics/coremetrics-laravel": "@dev",
```

```
    "repositories": [
        {
            "type": "path",
            "url": "../coremetrics-package",
            "options": {
                "symlink": true
            }
        }
    ]
 ```

Daemon (Host app):

 ```
 php artisan coremetrics:daemon
 ```

* This later will be removed as a step

Data will be sent to the following hard-coded endpoint:
https://github.com/coremetrics/coremetrics-laravel/blob/master/src/Agent.php#L83
