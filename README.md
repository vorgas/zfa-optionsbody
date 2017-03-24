# zfa-optionsbody

Simple tool to allow a body in response to an OPTIONS request with Apigility

## Synopsis

Apigility, by default, does not provide a body to an OPTIONS request. It
only provides allowed headers. This hooks into Apigility files to create
documentation automatically.

## Example

*needs a link to a working public api*

## Motivation

I wanted to be able to supply more than just allowed methods in the headers 
when an OPTIONS request is made to my Apigility project. I view this area as
documentation for consumers of my Api.

Because Apigility already has documentation sections, I figured I could just
hook into these and create an automatic response.

## Installation

*When I get this to work with composer, put in the code*

You will also need to modify vendor/zfcampus/zf-rest/src/Listener/OptionsListener.php
```php
protected function getOptionsResponse(MvcEvent $event, array $options)
{
    $response = $event->getResponse();
    // Add this next line
    $response->setContent(\OptionsBody\OptionsBody::buildBody($event));
    $this->createAllowHeader($options, $response);
    return $response;
}
```
## Tests

*Describe and show how to run the tests with code examples.*

## Contributors

*Let people know how they can dive into the project, include important links to things like issue trackers, irc, twitter accounts if applicable.*

## License

The MIT License (MIT)
Copyright (c) 2017 Mike Hill

## To Do
Convert to a legitimate class instead of a static class
Move array parsing into sub classes
Provide better default values
Create unit tests
Utilize composer's replace feature to override the OptionsListener
