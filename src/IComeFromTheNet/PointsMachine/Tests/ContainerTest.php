<?php
namespace IComeFromTheNet\PointsMachine\Tests;

use Mrkrstphr\DbUnit\DataSet\ArrayDataSet;
use IComeFromTheNet\PointsMachine\Tests\Base\TestWithContainer;


class ContainerTest extends TestWithContainer
{
   
    public function getDataSet()
    {
       return new ArrayDataSet([
           __DIR__.'/ExampleFixture.php',
        ]);
    }
    
    
    public function testContainerDBGateways()
    {
        $oContainer = $this->getContainer();
        
        $oGateway = $oContainer->getGatewayCollection()->getGateway('pt_system');
        $this->assertInstanceOf('IComeFromTheNet\PointsMachine\DB\Gateway\PointSystemGateway',$oGateway);
        
        $oGateway = $oContainer->getGatewayCollection()->getGateway('pt_system_zone');
        $this->assertInstanceOf('IComeFromTheNet\PointsMachine\DB\Gateway\PointSystemZoneGateway',$oGateway);
         
        $oGateway = $oContainer->getGatewayCollection()->getGateway('pt_event_type');
        $this->assertInstanceOf('IComeFromTheNet\PointsMachine\DB\Gateway\EventTypeGateway',$oGateway);
    }
    
    
    
}
/* End of class */