# coremetrics-laravel

## Installation
```
"require": {
    "coremetrics/coremetrics-laravel": "@dev"
}
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

## Publish Configuration
This is necessary to set the channel token, so the server will accept the metrics.

```
php artisan vendor:publish --provider="Coremetrics\CoremetricsLaravel\CoremetricsLaravelServiceProvider" --tag="config"
```

## Add Token
You must add a channel token from the Coremetrics server to the `.env` file under the key `COREMETRICS_TOKEN`. This will be a UUID.

## Server Endpoint
You must set a server endpoint in the `.env` file under the key `COREMETRICS_URL`.

## Daemon:
If the command isn't running then the agent will be started automatically. There is currently no mechanism to turn off 
the agent after it is started in this way, so it is recommended to start the agent manually with the following commmand
in order to be able to stop it. If you don't, it will keep running and fill up logs with debug statements.

 ```
 php artisan cm:daemon:start
 ```
