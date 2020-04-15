# coremetrics-package / larameter

Installation (Host app):
```
"require": {
        "aivis/larameter": "@dev",
```

```
    "repositories": [
        {
            "type": "path",
            "url": "/home/vagrant/code/larameter",
            "options": {
                "symlink": true
            }
        }
    ]
 ```
 
 Daemon (Host app):
 ```
 php artisan larameter:daemon
 ```
 * This later will be removed as a step 
 
 
Data will be sent to the following hard-coded endpoint:
https://github.com/aivis/coremetrics-package/blob/master/src/Larameter/Agent.php#L83
