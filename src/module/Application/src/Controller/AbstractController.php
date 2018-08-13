<?php

namespace Application\Controller;

use Zend\Log\LoggerInterface;
use Zend\Log\LoggerAwareInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\ServiceManager\ServiceManager;

class AbstractController extends AbstractActionController implements LoggerAwareInterface
{
    /**
     * @var ServiceManager
     */
    protected $logger;
    protected $serviceManager;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }   
    
    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * @param mixed $serviceManager
     * @return $this
     */
    public function setServiceManager($serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }
}