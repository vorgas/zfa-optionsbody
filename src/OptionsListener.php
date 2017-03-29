<?php
namespace vorgas\ZfaOptionsBody;

use Zend\EventManager\EventManager;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request;
use Zend\Http\Response;

/**
 *
 * @author pastech
 *        
 */
class OptionsListener
{
    /**
     * Establishes the event listener
     * 
     * @param MvcEvent $event
     */
    public function __construct(MvcEvent $event)
    {
        $handler = get_class($this). '::onRoute';
        $app = $event->getTarget();
        $eventManager = $app->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_FINISH, $handler, -100);
    }

    
    /**
     * If an http method of OPTIONS is used, get the response body
     * 
     * This is a static function because I am too stupid to figure out how
     * to use a normal function in $eventManager->attach() during the constructor.
     * I tried using [$this, 'onRoute'] but it complained about getting an
     * array, even though that's what the docs say to do.
     * 
     * @param MvcEvent $event
     * @return Response
     */
    public static function onRoute(MvcEvent $event): Response
    {
        $request = $event->getRequest();
        $method = $request->getMethod();
        $response = $event->getResponse();
        
        if ($method === Request::METHOD_OPTIONS) {
            $content = OptionsBody::buildBody($event);
            $response->setContent($content);
        }
        
        return $response;
    }
}

