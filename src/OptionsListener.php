<?php
namespace vorgas\ZfaOptionsBody;

use Zend\Mvc\MvcEvent;
use Zend\Http\Request;

/**
 *
 * @author pastech
 *        
 */
class OptionsListener
{

    /**
     */
    public function __construct(MvcEvent $event)
    {
        $app = $event->getTarget();
        $services = $app->getServiceManager();
        $eventManager = $app->getEventManager();
        
        $eventManager->attach(MvcEvent::EVENT_FINISH, function($e) {
            $request = $e->getRequest();
            $method = $request->getMethod();
        
            if ($method === Request::METHOD_OPTIONS) {
                $response = $e->getResponse();
        
                $content = json_encode(['content' => 'some content']);
                $response->setContent($content);
                return $response;
            }
        }, -100);
    }
}

