--- title: REST Methods menuTitle: Controllers category: API position: 12 ---

## Introduction

The API response format must stay consistent along the application. Ideally it would be good to follow a standard as
the [JSON:API](https://jsonapi.org/format/) so your frontend app could align with the API.

Restify provides several different approaches to respond consistent to the application's incoming request. By default,
Restify's base rest controller class uses a `RestResponse` structure which provides a convenient method to respond to
the HTTP request with a variety of handy magic methods.

## Restify Response Quickstart

To learn about Restify's handy response, let's look at a complete example of responding a request and returning the data
back to the client.

### Defining The Route

First, let's assume we have the following routes defined in our `routes/api.php` file:

```php
Route::post('users', 'UserController@store');

Route::get('users/{id}', 'UserController@show');
```

The `GET` route will return back a user for the given `id`.

### Creating The Controller

Next, let's take a look at a simple `API` controller that handles this route. We'll leave the `show` and `store` methods
empty for now:

```php
<?php

namespace App\Http\Controllers;

use Binaryk\LaravelRestify\Controllers\RestController;

class UserController extends RestController
{
    /**
     * Store a newly created user in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        // The only way to do great work is to love what you do.
    }

    /**
     * Display the user entity
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        // Very little is needed to make a happy life.
    }
}
```

### Writing The API Response Logic

Now we are ready to fill in our `show` method with the logic to respond with the user resource. To do this, we will use
the `respond` method provided by the parent `Binaryk\LaravelRestify\Controllers\RestController` class. A JSON response
will be sent for `API` request containing `data` and `errors` properties.

To get a better understanding of the `respond` method, let's jump back into the `show` method:

```php
/**
 * Display the user entity
 *
 * @param int $id
 * @return Response
 */
public function show($id)
{
    return $this->response(User::find($id));
}
```

As you can see, we pass the desired data into the `respond` method. This method will wrap the passed data into a JSON
object and attach it to the `data` response property.

### Receiving API Response

Once the `respond` method wrapping the data, the HTTP request will receive back a response having always the structure:

```json
{
  "data": {
    "id": 1,
    "name": "User name",
    "email": "kshlerin.hertha@example.com",
    "email_verified_at": "2019-12-20 09:48:54",
    "created_at": "2019-12-20 09:48:54",
    "updated_at": "2020-01-10 12:01:17"
  }
}

```

or:

```json
{
  "errors": []
}
```

## Response factory

In addition the parent `RestController` provides a powerful `response` factory method. To understand this let's return
back to our `store` method from the `UserController`:

```php
/**
 * Store a newly created resource in storage.
 *
 * @param Request $request
 * @return Response
 */
public function store(Request $request)
{
    return $this->response();
}
```

The `response()` method will be an instance of `Binaryk\LaravelRestify\Controllers\RestResponse`. For more information
on working with this object instance,
[check out its documentation](#rest-response-methods).

```php
$this->response()
->data($user)
->message('This is the first user');
```

The response will look like:

```json
{
  "data": {
    "id": 1,
    "name": "User name",
    "email": "kshlerin.hertha@example.com",
    "email_verified_at": "2019-12-20 09:48:54",
    "created_at": "2019-12-20 09:48:54",
    "updated_at": "2020-01-10 12:01:17"
  },
  "meta": {
    "message": "This is the first user"
  }
}
```

### Displaying Response Errors

As we saw above, the response always contains an `errors` property. This can be either an empty array, or a list with
errors. For example, what if the incoming request parameters do not pass the given validation rules? This can be handled
by the `errors` proxy method:

```php
/**
 * Store a newly created resource in storage.
 *
 * @param Request $request
 * @return Response
 */
public function store(Request $request)
{
    try {
        $this->validate($request, [
            'title' => 'required|unique:users|max:255',
        ]);

        // The user is valid
    } catch (ValidationException $exception) {
        // The user is not valid
        return $this->errors($exception->errors());
    }
}
```

And returned `API` response will have the `400` HTTP code and the following format:

```json
{
  "errors": {
    "title": [
      "The title field is required."
    ]
  }
}
```

## Custom Header

Sometimes you may need to respond with a custom header, for example according
with [JSON:API](https://jsonapi.org/format/#crud-creating-responses-201)
after storing an entity, we should respond with a `Location` header which has the value endpoint to the resource:

```php
return $this->response()
    ->header('Location', 'api/users/1')
    ->data($user);
```

## Optional Attributes

By default Restify returns `data` and `errors` attributes in the API response. It also wrap the message into a `meta`
object. But what if we have to send some custom attributes. In addition to generating default fields, you may add extra
fields to the response by using `setMeta` method from the `RestResponse` object:

```php
return $this->response()
    ->data($user)
    ->setMeta('related', [ 'William Shakespeare', 'Agatha Christie', 'Leo Tolstoy' ]);
```

## Hiding Default Attribute

Restify has a list of predefined attributes: `'line', 'file', 'stack', 'data', 'errors', 'meta'`.

Some of those are hidden in production: `'line', 'file', 'stack'`, since they are only used for tracking exceptions.

If you would like the API response to not contain any of these fields (or hiding a specific one, `errors` for example),
this can be done by setting in the application provider the:

```php
RestResponse::$RESPONSE_DEFAULT_ATTRIBUTES = ['data', 'meta'];
```

## Rest Response Methods

The `$this->response()` returns an instance of `Binaryk\LaravelRestify\Controllers\RestResponse`. This expose multiple
magic methods for your consistent API response.

### Attach data

As we already have seen, attaching data to the response can be done by using:

```php
->data($info)
```

### Headers setup

Header could be set by using `header` method, it accept two arguments, the header name and header value:

```php
->header('Location', 'api/users/1')
```

### Meta information

In addition with `data` you may want to send some extra attributes to the client, a message for example, or anything
else:

```php
->setMeta('name', 'Eduard Lupacescu')
```

```php
->message(__('Silence is golden.'))
```

## Response code modifiers

Very often we have to send an informative response code. The follow methods are used for setting the response code:

### Refresh 103

```php
->refresh()
````

### Success 200

```php
->success()
````

### Created 201

```php
->created()
````

### Deleted (No Content) 204

```php
->deleted()
````

```php
->blank()
````

### Invalid 400

```php
->invalid()
````

### Unauthorized 401

```php
->unauthorized()
````

### Forbidden 403

```php
->forbidden()
````

### Missing 404

```php
->missing()
````

### Throttle 429

```php
->throttle()
```

### Unavailable 503

```php
->unavailable()
````

## Debugging

The follow methods could be used to debug some information in the dev mode:

### Line debugging

```php
$lineNumber = 201;
$this->line($lineNumber)
```

### Debug to file

This could be used for debugging the file name

```php
$this->file($exception->getFile())
```

### Stack traces

With this you may log the exception stach trace

```php
$this->stack($exception->getTraceAsString())
```

### Errors methods

The follow methods could be used for adding errors to the response:

### Adding multiple errors

Adding a set of errors at once:

```php
$this->errors([ 'Something went wrong' ])
```

### addError function

Adding error by error in a response instance:

```php
$this->addError('Something went wrong')
```

## Custom Paginator

Sometimes you have a custom paginator collection, and you want to keep the same response format as the `Repository`does.

You can use this static call:

```php
        $paginator = User::query()->paginate(5);

        $response = Binaryk\LaravelRestify\Controllers\RestResponse::index(
            $paginator
        );

```

The `$paginator` argument should be an instance of: `Illuminate\Pagination\AbstractPaginator`.

The expected response will contain:

```json
{
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost",
    "per_page": 5,
    "to": 1,
    "total": 1
  },
  "links": {
    "first": "http://localhost?page=1",
    "last": "http://localhost?page=1",
    "prev": null,
    "next": null
  },
  "data": []
}

```

