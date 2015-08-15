## Fitbit API ##

Very basic Fitbit API. There are working Oauth 2 and few Fitbit endpoints.

## Examples ##

First you have to generate login url.

```
$fitbit = new Fitbit('Client ID', 'Client (Consumer) Secret');
echo $fitbit->getLoginUrl('http://yourcallbackurl.cz', ['profile']);
```

User is redirected to your callback url with code in paramters. Get this code and request token from fitbit.

```
$token = $fitbit->getToken('CODE', 'http://yourcallbackurl.cz');
$fitbit->setToken($token);
```

Token have only 1 hour duration and you have to use refresh token endpoint

```
if ($_SESSION['fitbit']->dateExpire <= time()) {
    $_SESSION['fitbit'] = $fitbit->refreshToken($_SESSION['fitbit']);
    $_SESSION['fitbit']->dateExpire = (time() - 60) + $_SESSION['fitbit']->expires_in;
}
```

After you have token, you can use Fitbit endpoints.

```
print_r($fitbit->getProfile());
```
