# Making HTTP requests in Cherry

HTTP requests are handled by the `Cherry\Net\Http\HttpRequest` class. This class in turn makes use of a `Cherry\Net\Http\Client\BaseClient` implementation such as the `StreamClient` or `CurlClient`. Which implementation being used is determined automatically unless one is explicitly specified in the configuration.

@dot
StreamClient->HttpRequest
CurlClient->HttpRequest
HttpRequest->HttpDownload
@enddot

## The HttpRequest API

The `HttpRequest` API is heavily inspired by the XmlHttpRequest2 API used on the web today.

    $req = new HttpRequest();
    $req->open('GET', $url);
    $req->send();
    echo $req->getResponse();

### Opening and sending the request

Opening is done with `open($method,$url)` and it ensures the request has got the essentials ready. After the call to `open()` you can modify headers and settings for the request.

The methods available for this is:

- `setHeader($header,$value)` - set a request header
- `setOption($option,$value)` or `setOption($array)` - set option for request and client.

To send the request, use `send([$data[,$ctype]])`. This method will return true if the request was successful.

## The ClientBase API

The `ClientBase` API is more raw, but also more powerful than the `HttpRequest` API. Cookies are supported (and can be made persistent via a cookie jar file) and all aspects of the request can be tweaked.

    $hc = ClientBase::factory();
    $hc->setUrl($url);
    $hc->setCookieJar(null); // null=session
    $hc->setHeader('Referer', $ref); // all headers can be set
    $s = $hc->execute();
    if ($s) echo $hc->getResponse();
    var_dump($hc->getTimings()); // see below

### Cookies and the cookie jar

The `ClientBase` class has all the logic needed to manage cookies. The way this is done is by calling the `setCookieJar()` method with a single argument being a string pointing to a file on the system.

@note Don't use the systems temp folder for the cookie jar as these locations can be read by other users and thus sessions hijacked.

Manipulating cookies is done with `setCookie($key,$val)` and `getCookie($key)`. There is also `setCookieRaw($str)` and `getCookieRaw($key)` that operates on full cookie header values.

When a cookie is set, the jar is updated with the cookie data to be read back the next time the same jar is used. If no jar is provided, the cookies will live for as long as the object instance lives.

### Timing and profiling

The method `getTimings()` is available and returns timing information in milliseconds.

- `started` - always 0.
- `connected` - the number of ms before the server accepted the connection.
- `request_sent` - number of ms until the request was sent
- `headers` - number of ms til server replied with the status and headers.
- `content_begin` - number of ms when the content started
- `content_ends` - elapsed ms when the content ended.

## Options

- `HTTP_USE_CACHE` (HttpRequest) - if true, the request data will be cached by the appropriate caching mechanism. This doesn't respect the wishes of the response headers yet.
- `HTTP_PROXY` (ClientBase) - should point to a proxy uri, such as "localhost:12345" or "tcp://localhost:23456". The tcp proxy is the only reliable protocol.
- `HTTPS_VERIFY_CERT` (ClientBase) - if false, bad certificates will not fail the request.
- `HTTPS_VERIFY_FP` (ClientBase) - soft check to verify the certificate fingerprint.