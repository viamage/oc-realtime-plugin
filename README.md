## oc-realtime

Websockets broadcasting plugin for OctoberCMS. Powered by [Ratchet](http://socketo.me/).

This plugin is an alternative for "out-of-box" laravel supported Pusher.io and Sockets.io.

![screen](https://i.viamage.com/jz/screen-2018-05-18-14-36-06.png)

#### Requirements:

- php7.1+
- php-zmq

#### Setup

- Go to Settings and put Websocket Server IP and Port. Defaults are 0.0.0.0:6010
- Run server. You can put that into your supervisor or run manually for development.

```
php artisan realtime:run-server
```

- Setup your Nginx for SSL connections and domain. Or Apache, but why would you use Apache. I will give you Nginx config, figure Apache one yourself if you really have to.

Replace WEBSOCKET_IP:WEBSOCKET_PORT with IP and Port on which websocket server stands and YOUR_DOMAIN with your domain and obviously setup proper certificates.

```
server {
       listen 8443 ssl http2;
       server_name YOUR_DOMAIN;

       location / {
               proxy_set_header Host $host;
               proxy_set_header X-Real-IP $remote_addr;
               proxy_pass http://WEBSOCKET_IP:WEBSOCKET_PORT/;
               proxy_http_version 1.1;
               proxy_set_header Upgrade $http_upgrade;
               proxy_set_header Connection "upgrade";
       }

       ssl_certificate /PATH/TO/CERT/fullchain.pem;
       ssl_certificate_key /PATH/TO/KEY/privkey.pem;

       ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
       ssl_prefer_server_ciphers on;
       ssl_ciphers "EECDH+ECDSA+AESGCM:EECDH+aRSA+AESGCM:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA256:EECDH+ECDSA+SHA384:EECDH+ECDSA+SHA256:EECDH+aRSA+SHA384:EDH+aRSA+AESGCM:EDH+aRSA+SHA256:EDH+aRSA:EECDH:!aNULL:!eNULL:!MEDIUM:!LOW:!3DES:!MD5:!EXP:!PSK:!SRP:!DSS:!RC4:!SEED";
       ssl_session_timeout 1d;
       ssl_stapling on;
       ssl_stapling_verify on;
       add_header Strict-Transport-Security max-age=15768000;

       # You can uncomment below if you know what you're doing
       #ssl_dhparam /etc/ssl/certs/dhparam.pem;
       #ssl_session_cache shared:SSL:50m;

       gzip on;
       gzip_disable "msie6";

       gzip_vary on;
       gzip_proxied any;
       gzip_comp_level 6;
       gzip_buffers 16 8k;
       gzip_http_version 1.1;
       gzip_types text/plain text/css application/json application/x-javascript text/xml application/xml application/xml+rss text/javascript application/javascript;

       client_max_body_size 10M;
}

```

- Add above config to sites-enabled, and voila, you should have your connection available under `wss://YOUR_DOMAIN:8443`

- Add `vm_autobahn` component to your theme layout. It will inject autobahn.js and viamage_realtime cookie which stores encoded token. Don't forget about including {% scripts %}!


#### Usage

- On frontend side, you should put this to subscribe to given topic and fill it up to do stuff. You can use october ajax framework inside to update partials and so on. 

```
{% put scripts
<script>
  var conn = new ab.Session('wss://your_domain:8443',
    function () {
      conn.subscribe('YOUR_TOPIC', function (topic, data) {
        // do something here!
      })
    },
    function () {
      console.warn('WebSocket connection closed')
    },
    {'skipSubprotocolCheck': true}
  )
</script>
{% endput %}
```


- On backend side, you should use `Viamage\RealTime\Pusher` class.

Example:

```
class WebhookController {
  public function onIncomingWebhook($payload){
    $this->updateModels($payload);
    \Viamage\RealTime\Classes\Pusher::push([
        'topic' => 'SomeTopicUsersAreSubscribedTo',
        'details' => 'Some other thing available under data.details in JS'  
    ]);
  }
}
```

#### Per user pushes

This plugin can use user->persist_code for per user updates. If you want to push to specific user, pass `user_id` in array passed to Pusher.

This will push event to user-specific channel (which is currently called using token, eg `test_channel_{{ user.realtimeToken.token }}`)

*We support RainLab.User and Keios.ProUser*

```
class WebhookController {
  public function onIncomingWebhook($payload){
    $this->updateModels($payload);
    \Viamage\RealTime\Classes\Pusher::push([
        'topic' => 'SomeTopicUsersAreSubscribedTo'
        'details' => 'Some other thing available under data.details in JS'
        'user_id' => $payload['user_id']  
    ]);
  }
}
```

#### TODOs

- Calling and messaging from user is not supported right now out of box. You can extend RunServer command and replace PusherBus class with your own class that supports incoming messages in in buildBus() method.

- We've done limited testing with RainLab.User, as we use ProUser on all our productions. Feel free to let us know if anything is not working as it should. 

- Unit tests would be nice.
