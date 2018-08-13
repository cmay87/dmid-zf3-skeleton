<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\View\Model\ViewModel;

class QueueController extends AbstractController
{
    public function historyAction()
    {
        return new ViewModel();
    }

    public function indexAction()
    {
        return new ViewModel();
    }    
    
    public function ordersAction()
    {
        return new ViewModel();
    }
    
    public function pendingAction()
    {
        return new ViewModel();
    }
}
