# Dapr Magic Auth

A service to help generate "magic login links" for services. This is just an example microservice, not intended for
production. The intention here is to illustrate how we can share simpler microservices that allow for plug-n-play
functionality that can be easily hooked.

## Requirements

- [Dapr](https://dapr.io)
- A store that supports actors

## Flow Overview

### Definitions

- userId: A static user identifier
- deviceId: A static device identifier to send to
- nonce: A number used only once
- code: A code to send to the device

### Authenticating

1. In your application, determine the `userId` and `deviceId` to send to.
2. Construct a `nonce` that you give to the client.
3. Send the device the `code` and `userId` you get from the `/start/` endpoint, in the form of a link.
4. When the user submits the `code` and `userId`, send it to the `/authenticate/` endpoint.
5. Using websockets (via webhooks) or polling (via `/isAuthenticated/` endpoint), allow the user to login.

## Features

This uses several environment variables for configuration:

- `AUTH_EXPIRATION_TIME`: The number of seconds the `code` is valid for after creating it.
- `AUTH_MAX_RETRIES`: The number of times a user may attempt to send the wrong code.
- `AUTH_SUCCESS_CALLBACK`: A Dapr url that receives a call after a successful login.
- `AUTH_READY_CALLBACK`: A Dapr url that receives a call when a user begins to login.
- `AUTH_FAILED_CALLBACK`: A Dapr url that receives a call when a user sends the wrong code.

## Callbacks

There are several callbacks that can provide realtime visibility into the auth service. You can use this to issue tokens
or even invalidate tokens depending on your use-case. They accept Dapr urls (the path after `http://localhost/` which
you can find in [the API reference](https://docs.dapr.io/reference/api/)) which you can use to notify other services.

### Callback data

Callbacks receive the following data:

```json
{
  "userId": "The user id",
  "deviceId": "The device id",
  "code": "The login code",
  "nonce": "The login nonce"
}
```

## Testing

You can run this locally via `docker-compose`:

### Calling the service directly

```sh
docker-compose up -d
curl -X POST "http://localhost/start/user/device/nonce" -H  "accept: application/json"
# {"code":"36234D28-D0A8-45F5-9C4D-2ACF9D10D072"}
curl -X GET "http://localhost/isAuthenticated/user/device/nonce" -H  "accept: application/json"
# {"isAuthenticated":false}
curl -X POST "http://localhost/authenticate/user/36234D28-D0A8-45F5-9C4D-2ACF9D10D072" -H  "accept: application/json"
# {"isAuthenticated":true}
curl -X GET "http://localhost/isAuthenticated/user/device/nonce" -H  "accept: application/json"
# {"isAuthenticated":true}
```

## Deploying

1. Configure a state store component with actors enabled
2. Modify and deploy the example deployment: `kubectl apply -f deploy/deployment.yml`
3. Forward the remote service's port: `kubectl port-forward --namespace default deployment/auth-service 8080:80`
4. Curl the service and make sure it is
   working: `curl -X POST "http://localhost:8080/start/user/device/nonce" -H  "accept: application/json"`
5. Profit!
