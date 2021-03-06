<?php
namespace IComeFromTheNet\PointsMachine\Tests;

use DateTime;
use IComeFromTheNet\PointsMachine\DB\Entity\RuleChain;
use IComeFromTheNet\PointsMachine\Tests\Base\TestWithContainer;
use DBALGateway\Feature\BufferedQueryLogger;


class EntityRuleChainTest extends TestWithContainer
{
    
    protected $aFixtures = ['example-system.php','rule-chain-before.php'];
    
    
    
    
    public function testEntityOperations()
    {
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        
        $oExpectedDataset = $this->getDataSet( ['example-system.php','rule-chain-after.php'])
                                 ->getTable('pt_rule_chain');
                                 
        $oLog = new BufferedQueryLogger();
        $oLog->setMaxBuffer(100);
        $this->oLog = $oLog;
        $oGateway->getAdapater()->getConfiguration()->setSQLLogger($oLog);
            
        $this->entitySaveNewEntityTest();
        $this->entitySaveFailedWhenNonCurrentEpisode();
        $this->entityUpdateExistingEpisodeTest();
        $this->entityUpdateCauseNewVersionTest();
        $this->entityCreateFailsOnFKTest();
        $this->entityRemoveFailsOnRelationsKeyCheckTest();
        $this->entityRemoveSucessfulTest();
        
        $sSql  = ' SELECT episode_id, rule_chain_id, event_type_id, system_id, ';
        $sSql .= '       chain_name, chain_name_slug, rounding_option, cap_value, enabled_from, enabled_to  ' ;
        $sSql .= ' FROM pt_rule_chain';
        
        $this->assertTablesEqual($this->getConnection()->createQueryTable('pt_rule_chain',$sSql),$oExpectedDataset);
    }
    
    //--------------------------------------------------------------------------
    # Database Operations
    
    protected function entitySaveNewEntityTest()
    {
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        $oLogger    = $oContainer->getAppLogger();
        $oProcessingDate = new DateTime();
       
        // Build the entity to save
        $sRuleChainId = '21EED041-2453-92CE-F803-DCCE7C6E4E41';
        $sEventTypeId = '93B19460-04F4-85CD-6553-00D7125CFDAE';
        $sSystemId    = '9B753E70-881B-F53E-2D46-8151BED1BBAF';
        $sChainName   = 'New Chain'; 
        $sChainNameSlug = 'new_chain';
        $iRoundingOption = 2;
        $fCapValue = 7; 
        $oEnabledFrom = (new DateTime('now'));
        $oEnabledTo = (new DateTime('3000-01-01'));
      
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
      
        $oEntity->sRuleChainID   = $sRuleChainId;
        $oEntity->sSystemID      = $sSystemId;
        $oEntity->sEventTypeID   = $sEventTypeId;
        $oEntity->sChainName     = $sChainName;
        $oEntity->sChainNameSlug = $sChainNameSlug;
        $oEntity->iRoundingOption = $iRoundingOption;
        $oEntity->fCapValue      = $fCapValue;
        $oEntity->oEnabledFrom  = $oEnabledFrom;
        $oEntity->oEnabledTo    = $oEnabledTo ;
        
        // save the entity
        $bResult = $oEntity->save();
        $aResult = $oEntity->getLastQueryResult();
      
        
        $this->assertTrue($aResult['result']);
        $this->assertEquals('Created new RuleChain Episode',$aResult['msg']);
        $this->assertTrue($bResult);
        $this->assertEquals(7,$oEntity->iEpisodeID);

        
    } 
    
    protected function entitySaveFailedWhenNonCurrentEpisode()
    {
        
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        $oLogger    = $oContainer->getAppLogger();
        $oProcessingDate = new DateTime();
       
        // Build the entity to save
        
        $sRuleChainId = 'C4039C0B-FF81-43CE-7CB3-B85EB3802C71';
        $sEventTypeId = '93B19460-04F4-85CD-6553-00D7125CFDAE';
        $sSystemId    = '9B753E70-881B-F53E-2D46-8151BED1BBAF';
        $sChainName   = 'New Chain'; 
        $sChainNameSlug = 'new_chain';
        $iRoundingOption = 2;
        $fCapValue    = 7; 
        $oEnabledFrom = new DateTime('now - 7 day');
        $oEnabledTo   = new DateTime('now - 1 day');
      
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
      
        $oEntity->sRuleChainID   = $sRuleChainId;
        $oEntity->sSystemID      = $sSystemId;
        $oEntity->sEventTypeID   = $sEventTypeId;
        $oEntity->sChainName     = $sChainName;
        $oEntity->sChainNameSlug = $sChainNameSlug;
        $oEntity->iRoundingOption = $iRoundingOption;
        $oEntity->fCapValue      = $fCapValue;
        $oEntity->oEnabledFrom  = $oEnabledFrom;
        $oEntity->oEnabledTo    = $oEnabledTo ;
        $oEntity->iEpisodeID    = 3;
      
        
        // save the entity
        $bResult = $oEntity->save();
        $aResult = $oEntity->getLastQueryResult();
      
        
        $this->assertFalse($aResult['result']);
        $this->assertEquals('Unable to decide which operation to use',$aResult['msg']);
        $this->assertFalse($bResult);
    
    
        
    }
    
    protected function entityUpdateExistingEpisodeTest()
    {
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        $oLogger    = $oContainer->getAppLogger();
        $oProcessingDate = new DateTime();
       
        // Build the entity to save
        
         $oEntity = new RuleChain($oGateway,$oLogger);
      
        $sRuleChainId = 'C1EA95B8A-C10E-ED88-EE9E-9761F31453D4';
        $sEventTypeId = '93B19460-04F4-85CD-6553-00D7125CFDAE';
        $sSystemId    = '9B753E70-881B-F53E-2D46-8151BED1BBAF';
        $sChainName   = 'Updated Chain';
        $sChainNameSlug = 'updated_chain';
        $iRoundingOption = 3;
        $fCapValue    = null; 
        $oEnabledFrom = new DateTime('now');
        $oEnabledTo   = new DateTime('3000-01-01');
        
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
      
        $oEntity->sRuleChainID   = $sRuleChainId;
        $oEntity->sSystemID      = $sSystemId;
        $oEntity->sEventTypeID   = $sEventTypeId;
        $oEntity->sChainName     = $sChainName;
        $oEntity->sChainNameSlug = $sChainNameSlug;
        $oEntity->iRoundingOption = $iRoundingOption;
        $oEntity->fCapValue      = $fCapValue;
        $oEntity->oEnabledFrom  = $oEnabledFrom;
        $oEntity->oEnabledTo    = $oEnabledTo ;
        $oEntity->iEpisodeID    = 4;
        
        // save the entity
        $bResult = $oEntity->save();
        $aResult = $oEntity->getLastQueryResult();
      
        
        $this->assertTrue($aResult['result']);
        $this->assertEquals('Updated existing RuleChain Episode',$aResult['msg']);
        $this->assertTrue($bResult);
        
        
    }
    
    protected function entityUpdateCauseNewVersionTest()
    {
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        $oLogger    = $oContainer->getAppLogger();
        $oProcessingDate = new DateTime();
       
        // Build the entity to save
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
        $sRuleChainId = '8FE3DACA-7455-0E21-474B-D1B0D694E5C5'; 
        $sEventTypeId = '93B19460-04F4-85CD-6553-00D7125CFDAE';
        $sSystemId    = '9B753E70-881B-F53E-2D46-8151BED1BBAF';
        $sChainName   = 'new version chain';
        $sChainNameSlug = 'new_version_chain';
        $iRoundingOption = 1;
        $fCapValue    = null; 
        $oEnabledFrom = new DateTime('now - 1 day');
        $oEnabledTo   = new DateTime('3000-01-01');
        
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
      
        $oEntity->sRuleChainID   = $sRuleChainId;
        $oEntity->sSystemID      = $sSystemId;
        $oEntity->sEventTypeID   = $sEventTypeId;
        $oEntity->sChainName     = $sChainName;
        $oEntity->sChainNameSlug = $sChainNameSlug;
        $oEntity->iRoundingOption = $iRoundingOption;
        $oEntity->fCapValue      = $fCapValue;
        $oEntity->oEnabledFrom  = $oEnabledFrom;
        $oEntity->oEnabledTo    = $oEnabledTo ;
        $oEntity->iEpisodeID    = 5;
        
        // save the entity
        $bResult = $oEntity->save();
        $aResult = $oEntity->getLastQueryResult();
        
        
        $this->assertTrue($aResult['result']);
        $this->assertEquals('Created new RuleChain Episode',$aResult['msg']);
        $this->assertTrue($bResult);
        
    }
    
     protected function entityCreateFailsOnFKTest()
    {
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        $oLogger    = $oContainer->getAppLogger();
        $oProcessingDate = new DateTime();
       
        // Build the entity to save
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
        $sRuleChainId = 'FFC6810F-5C11-B8E0-FCA6-08EC00879D1F'; 
        $sEventTypeId = 'D06F2B49-A257-9F09-BFF8-C555D8512D75'; // non current event type
        $sSystemId    = 'F69385AC-329F-5CD4-0E6F-64DAD1714093'; // non current system
        $sChainName   = 'bad relations';
        $sChainNameSlug = 'bad_relations';
        $iRoundingOption = 1;
        $fCapValue    = null; 
        
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
      
        $oEntity->sRuleChainID   = $sRuleChainId;
        $oEntity->sSystemID      = $sSystemId;
        $oEntity->sEventTypeID   = $sEventTypeId;
        $oEntity->sChainName     = $sChainName;
        $oEntity->sChainNameSlug = $sChainNameSlug;
        $oEntity->iRoundingOption = $iRoundingOption;
        $oEntity->fCapValue      = $fCapValue;
        
        // save the entity
        $bResult = $oEntity->save();
        $aResult = $oEntity->getLastQueryResult();
        
        $this->assertFalse($aResult['result']);
        $this->assertEquals('Temporal Referential integrity violated check System,EventType',$aResult['msg']);
        $this->assertFalse($bResult);
        
    }
    
    
    protected function entityRemoveFailsOnRelationsKeyCheckTest()
    {
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        $oLogger    = $oContainer->getAppLogger();
        $oProcessingDate = new DateTime();
       
        // Build the entity to save
        
        $oEntity = new RuleChain($oGateway,$oLogger);
        
        $sRuleChainId = '6BFF307B-E04F-9D98-5C6D-0C3B8D3AF5BE'; //Withdrawal Event Chain from example system
        $sEventTypeId = 'D06F2B49-A257-9F09-BFF8-C555D8512D75'; 
        $sSystemId    = 'F69385AC-329F-5CD4-0E6F-64DAD1714093'; 
        $sChainName   = 'bad relations';
        $sChainNameSlug = 'bad_relations';
        $iRoundingOption = 1;
        $fCapValue    = null; 
        $oEnabledFrom = new DateTime('now');
        $oEnabledTo   = new DateTime('3000-01-01');
        
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
      
        $oEntity->sRuleChainID   = $sRuleChainId;
        $oEntity->sSystemID      = $sSystemId;
        $oEntity->sEventTypeID   = $sEventTypeId;
        $oEntity->sChainName     = $sChainName;
        $oEntity->sChainNameSlug = $sChainNameSlug;
        $oEntity->iRoundingOption = $iRoundingOption;
        $oEntity->fCapValue      = $fCapValue;
        $oEntity->oEnabledFrom  = $oEnabledFrom;
        $oEntity->oEnabledTo    = $oEnabledTo ;
        $oEntity->iEpisodeID     = 1;
        
        // save the entity
        $bResult = $oEntity->remove();
        $aResult = $oEntity->getLastQueryResult();
      
        
        $this->assertFalse($aResult['result']);
        $this->assertEquals('Temporal Referential integrity violated check RuleChainMember',$aResult['msg']);
        $this->assertFalse($bResult);
    }
    
    protected function entityRemoveSucessfulTest()
    {
        $oContainer = $this->getContainer();
        $oGateway   = $oContainer->getGatewayCollection()->getGateway('pt_rule_chain');
        $oLogger    = $oContainer->getAppLogger();
        $oProcessingDate = new DateTime();
       
        // Build the entity to save
        
        $oEntity = new RuleChain($oGateway,$oLogger);
        
        $sRuleChainId = '5D3EA054-1D4D-5296-FA5F-30A2BA603755'; 
        $sEventTypeId = 'D06F2B49-A257-9F09-BFF8-C555D8512D75'; 
        $sSystemId    = 'F69385AC-329F-5CD4-0E6F-64DAD1714093'; 
        $sChainName   = 'chain can be closed';
        $sChainNameSlug = 'chain_can_be_closed';
        $iRoundingOption = 2;
        $fCapValue    = -5; 
        $oEnabledFrom = new DateTime('now - 1 day');
        $oEnabledTo   = new DateTime('3000-01-01');
        
        
        $oEntity = new RuleChain($oGateway,$oLogger);
      
      
        $oEntity->sRuleChainID   = $sRuleChainId;
        $oEntity->sSystemID      = $sSystemId;
        $oEntity->sEventTypeID   = $sEventTypeId;
        $oEntity->sChainName     = $sChainName;
        $oEntity->sChainNameSlug = $sChainNameSlug;
        $oEntity->iRoundingOption = $iRoundingOption;
        $oEntity->fCapValue      = $fCapValue;
        $oEntity->oEnabledFrom  = $oEnabledFrom;
        $oEntity->oEnabledTo    = $oEnabledTo ;
        $oEntity->iEpisodeID     = 6;
        
        
        // save the entity
        $bResult = $oEntity->remove();
        $aResult = $oEntity->getLastQueryResult();
      
        
        $this->assertTrue($aResult['result']);
        $this->assertEquals('Closed this episode',$aResult['msg']);
        $this->assertTrue($bResult);
    }
       
    
}
/* End of File */