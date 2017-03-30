<?php
namespace vorgas\ZfaOptionsBody;

use Zend\EventManager\EventManager;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateTrait;

/**
 *
 * @author pastech
 *        
 */
class OptionsListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;
    
    /**
     * Establishes the event listener
     * 
     * @param MvcEvent $event
     */
    public function __construct(MvcEvent $event)
    {
        $app = $event->getTarget();
        $eventManager = $app->getEventManager();
        $this->attach($eventManager);
    }

    /**
     * @param  EventManagerInterface $events
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'onRoute'], -100);
    }
    

    /**
     * If an http method of OPTIONS is used, get the response body
     * 
     * @param MvcEvent $event
     * @return Response
     */
    public function onRoute(MvcEvent $event): Response
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

