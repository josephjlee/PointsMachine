<?php
namespace IComeFromTheNet\PointsMachine;

use DateTime;
use Pimple;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use DBALGateway\Table\GatewayProxyCollection;
use DBALGateway\Metadata\Schema;

use IComeFromTheNet\PointsMachine\DB\Builder\PointSystemBuilder;
use IComeFromTheNet\PointsMachine\DB\Gateway\PointSystemGateway;

use IComeFromTheNet\PointsMachine\DB\Builder\PointSystemZoneBuilder;
use IComeFromTheNet\PointsMachine\DB\Gateway\PointSystemZoneGateway;

use IComeFromTheNet\PointsMachine\DB\Gateway\EventTypeGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\EventTypeBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\ScoringEventGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\ScoringEventBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\ScoreGroupGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\ScoreGroupBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\ScoreGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\ScoreBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\AdjustmentGroupGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\AdjustmentGroupBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\AdjustmentGroupLimitGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\AdjustmentGroupLimitBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\AdjustmentRuleGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\AdjustmentRuleBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\AdjustmentZoneGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\AdjustmentZoneBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\CalculationGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\CalculationBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\RuleChainGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\RuleChainBuilder;

use IComeFromTheNet\PointsMachine\DB\Gateway\RuleChainMemberGateway;
use IComeFromTheNet\PointsMachine\DB\Builder\RuleChainMemberBuilder;


class PointsMachineContainer extends Pimple
{
    
    /**
     * Class constructor
     * 
     * @param Symfony\Component\EventDispatcher\EventDispatcherInterface
     * @param Doctrine\DBAL\Connection $oAdapter
     * @param Psr\Log\LoggerInterface $oLogger
     */
    public function __construct(EventDispatcherInterface $oDispatcher, Connection $oAdapter, LoggerInterface $oLogger)
    {
        $this['database'] = $oAdapter;
        $this['logger'] = $oLogger;
        $this['event'] = $oDispatcher;
    }
    
    
    /**
     * Returns the database adapter
     * 
     * @return Doctrine\DBAL\Connection
     */ 
    public function getDatabaseAdaper()
    {
        return $this['database'];
    }
    
    
    
    /**
     * Returns the Applogger
     * 
     * @return Psr\Log\LoggerInterface
     */ 
    public function getAppLogger()
    {
        return $this['logger'];
    }
    
    /**
     * Returns the event dispatcher
     * 
     * @return Symfony\Component\EventDispatcher\EventDispatcherInterface
     */ 
    public function getEventDispatcher()
    {
        return $this['event'];
    }
    
    /**
     * Return the Gateway Collection
     * 
     * @return DBALGateway\Table\GatewayProxyCollection
     */ 
    public function getGatewayCollection()
    {
        return $this['gateway_collection'];
    }
    
    
    public function boot(DateTime $oProcessingDate, $aTableMap = null)
    {
        $this['processing_date'] = $oProcessingDate;
        
        if(null === $aTableMap) {
            $aTableMap = array(
               'pt_system'              => 'pt_system'  
              ,'pt_system_zone'         => 'pt_system_zone' 
              ,'pt_event_type'          => 'pt_event_type'
              ,'pt_event'               => 'pt_event'
              ,'pt_score_group'         => 'pt_score_group'
              ,'pt_score'               => 'pt_score'
              ,'pt_rule_group'          => 'pt_rule_group'
              ,'pt_rule_group_limits'   => 'pt_rule_group_limits'
              ,'pt_rule'                => 'pt_rule'
              ,'pt_rule_sys_zone'       => 'pt_rule_sys_zone'
              ,'pt_scoring_transaction' => 'pt_scoring_transaction'
              ,'pt_rule_chain'          => 'pt_rule_chain'
              ,'pt_chain_member'        => 'pt_chain_member'
              
            );
        }
        $this['table_map']       = $aTableMap;
        
        #
        # Boostrap the table Gateways
        #
        $c           = $this;
        $oSchema     = new Schema();
        $oGatewayCol = new GatewayProxyCollection($oSchema);
        
        $oGatewayCol->addGateway('pt_system',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_system'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Systems Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('system_id','guid',array());
            $table->addColumn('system_name','string',array("length" => 100));
            $table->addColumn('system_name_slug','string',array("length" => 100));
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
        
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('system_id','enabled_from'),'pt_system_uiq1');
           
            $oBuilder = new PointSystemBuilder();
            $oGateway = new PointSystemGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('s');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_system_zone',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_system_zone'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Systems Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id'   ,'integer' ,array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('zone_id'       ,'guid'    ,array());
            $table->addColumn('zone_name'     ,'string'  ,array("length" => 100));
            $table->addColumn('zone_name_slug','string'  ,array("length" => 100));
            $table->addColumn('system_id'     ,'guid'    ,array("unsigned" => true));
            $table->addColumn('enabled_from'  ,'datetime',array());
            $table->addColumn('enabled_to'    ,'datetime',array());
        
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('zone_id','enabled_from'),'pr_sys_zone_uk1');
            $table->addForeignKeyConstraint($aTableMap['pt_system'],array('system_id'),array('system_id'),array(),'pt_sys_zone_fk1');
            
            $oBuilder = new PointSystemZoneBuilder();
            $oGateway = new PointSystemZoneGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('z');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_event_type',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_event_type'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Systems Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('event_type_id','guid',array());
            $table->addColumn('event_name','string',array("length" => 100));
            $table->addColumn('event_name_slug','string',array("length" => 100));
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
            
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('event_type_id','enabled_from'),'pt_event_type_uiq1');
        
            $oBuilder = new EventTypeBuilder();
            $oGateway = new EventTypeGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('et');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_event',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_event'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('event_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('event_type_id','guid',array("unsigned" => true));
            $table->addColumn('event_created','datetime',array());
            $table->addColumn('process_date','datetime',array('comment' => 'Processing date for the calculator'));
            $table->addColumn('occured_date','datetime',array('comment' => 'When event occured'));
      
            $table->setPrimaryKey(array('event_id'));
            $table->addForeignKeyConstraint($aTableMap['pt_event_type'],array('event_type_id'),array('event_type_id'),array(),'pt_event_fk1');
            
            $oBuilder = new ScoringEventBuilder();
            $oGateway = new ScoringEventGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('se');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_score_group',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_score_group'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('score_group_id','guid',array());
            $table->addColumn('group_name','string',array("length" => 100));
            $table->addColumn('group_name_slug','string',array("length" => 100));
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());  
            
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('score_group_id','enabled_from'),'pt_score_gp_uiq1');
           
            
            $oBuilder = new ScoreGroupBuilder();
            $oGateway = new ScoreGroupGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('sg');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_score',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_score'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('score_id','guid',array());
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
            $table->addColumn('score_name','string',array("length" => 100));
            $table->addColumn('score_name_slug','string',array("length" => 100));
            $table->addColumn('score_value','float',array('signed' => true,'comment' => 'based value for calculations can be + or -'));
            $table->addColumn('score_group_id','guid',array("unsigned" => true));
            
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('score_id','enabled_from'),'pt_score_uiq1');
            $table->addForeignKeyConstraint($aTableMap['pt_score_group'],array('score_group_id'),array('score_group_id'),array(),'pt_score_fk1');
            
            $oBuilder = new ScoreBuilder();
            $oGateway = new ScoreGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('sc');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_rule_group',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_rule_group'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('rule_group_id','guid',array());
            $table->addColumn('rule_group_name','string',array("length" => 100));
            $table->addColumn('rule_group_name_slug','string',array("length" => 100));
            $table->addColumn('enabled_from'  ,'datetime',array());
            $table->addColumn('enabled_to'    ,'datetime',array());
            $table->addColumn('max_multiplier','float',array("unsigned" => true, 'comment' => 'Max value of multiplier once all rules are combined in this group allows group capping'));
            $table->addColumn('min_multiplier','float',array("unsigned" => true, 'comment' => 'Min value of multiplier once all rules are combined in this group allows group capping'));
            $table->addColumn('max_modifier'  ,'float',array("unsigned" => true, 'comment' => 'Max value of modifier once all rules are combined in this group allows group capping'));
            $table->addColumn('min_modifier'  ,'float',array("unsigned" => true, 'comment' => 'Min value of modifier once all rules are combined in this group allows group capping'));
            $table->addColumn('max_count'     ,'integer',array("unsigned" => true, 'comment' => 'Max number of scroing rules that can be used that linked to this group'));
            $table->addColumn('order_method'  ,'smallint',array("unsigned" => true, 'comment' => 'method of order to use 1= max 0=min'));
            $table->addColumn('is_mandatory'  ,'smallint',array("unsigned" => true,'comment' => 'Group always be applied unless not linked to system and score groups'));
           
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('rule_group_id','enabled_from'),'pt_rule_gp_uiq1');
                
            $oBuilder = new AdjustmentGroupBuilder();
            $oGateway = new AdjustmentGroupGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('rg');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_rule_group_limits',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_rule_group_limits'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('rule_group_id','guid',array());
            $table->addColumn('score_group_id','guid',array()); 
            $table->addColumn('system_id','guid',array()); 
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
           
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('rule_group_id','system_id','score_group_id','enabled_from'),'pt_rule_gp_limit_uiq1');
            $table->addForeignKeyConstraint($aTableMap['pt_rule_group'],array('rule_group_id'),array('rule_group_id'),array(),'pt_rule_gp_limit_fk1');
            $table->addForeignKeyConstraint($aTableMap['pt_score_group'],array('score_group_id'),array('score_group_id'),array(),'pt_rule_gp_limit_fk2');
            $table->addForeignKeyConstraint($aTableMap['pt_system'],array('system_id'),array('system_id'),array(),'pt_rule_gp_limit_fk3');
                 
            $oBuilder = new AdjustmentGroupLimitBuilder();
            $oGateway = new AdjustmentGroupLimitGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('rgl');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_rule',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_rule'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('rule_id','guid',array());
            $table->addColumn('rule_group_id','guid',array());
            $table->addColumn('rule_name','string',array("length" => 100));
            $table->addColumn('rule_name_slug','string',array("length" => 100));
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
            $table->addColumn('multiplier'  ,'float',array("unsigned" => true, 'comment' => 'Value to multiply the base value by'));
            $table->addColumn('modifier'    ,'integer',array("unsigned" => true, 'comment' => 'value to add to the base'));
            $table->addColumn('invert_flag' ,'smallint',array("unsigned" => true, 'comment' => 'Operation is inverted ie multiplier becomes a divisor'));
               
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('rule_id','rule_group_id','enabled_from'),'pt_rule_uiq1');
            $table->addForeignKeyConstraint($aTableMap['pt_rule_group'],array('rule_group_id'),array('rule_group_id'),array(),'pt_rule_fk1');
          
            $oBuilder = new AdjustmentRuleBuilder();
            $oGateway = new AdjustmentRuleGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('ar');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_rule_sys_zone',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_rule_sys_zone'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('rule_id','guid',array());
            $table->addColumn('zone_id','guid',array()); 
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
            
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('zone_id','rule_id','enabled_from'),'pt_rule_sys_zone_uiq1');
            $table->addForeignKeyConstraint($aTableMap['pt_rule'],array('rule_id'),array('rule_id'),array(),'pt_rule_sys_zone_fk1');
            $table->addForeignKeyConstraint($aTableMap['pt_system_zone'],array('zone_id'),array('zone_id'),array(),'pt_rule_sys_zone_fk2');

            $oBuilder = new AdjustmentZoneBuilder();
            $oGateway = new AdjustmentZoneGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('az');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_scoring_transaction',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_scoring_transaction'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('process_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('rule_id'       ,'integer',array("unsigned" => true));
            $table->addColumn('rule_group_id' ,'integer',array("unsigned" => true,'default'=>null));
            $table->addColumn('score_id'      ,'integer',array("unsigned" => true));
            $table->addColumn('score_group_id','integer',array("unsigned" => true,'default'=>null));
            $table->addColumn('system_id'     ,'integer',array("unsigned" => true));
            $table->addColumn('zone_id'       ,'integer',array("unsigned" => true));
            $table->addColumn('event_type_id' ,'integer',array("unsigned" => true));
            $table->addColumn('event_id'      ,'integer',array("unsigned" => true));
            
            $table->addColumn('score_base'      ,'float',array());
            $table->addColumn('score_balance'   ,'float',array());
            $table->addColumn('score_modifier'  ,'float',array());
            $table->addColumn('score_multiplier','float',array());
            
            $table->addColumn('created_date'    ,'datetime',array());
            $table->addColumn('processing_date' ,'datetime',array());
            $table->addColumn('occured_date'    ,'datetime' ,array());
            
            $table->setPrimaryKey(array('process_id'));
            $table->addForeignKeyConstraint($aTableMap['pt_rule']       ,array('rule_id')       ,array('episode_id') ,array(), 'pt_tran_rule_fk1');
            $table->addForeignKeyConstraint($aTableMap['pt_rule_group'] ,array('rule_group_id') ,array('episode_id') ,array(), 'pt_tran_rule_gp_fk2');
            $table->addForeignKeyConstraint($aTableMap['pt_system']     ,array('system_id')     ,array('episode_id') ,array(), 'pt_tran_sys_fk3');
            $table->addForeignKeyConstraint($aTableMap['pt_system_zone'],array('zone_id')       ,array('episode_id') ,array(), 'pt_tran_sys_zone_fk4');
            $table->addForeignKeyConstraint($aTableMap['pt_score']      ,array('score_id')      ,array('episode_id') ,array(), 'pt_tran_score_fk5');
            $table->addForeignKeyConstraint($aTableMap['pt_score_group'],array('score_group_id'),array('episode_id') ,array(), 'pt_tran_score_gp_fk6');
            $table->addForeignKeyConstraint($aTableMap['pt_event_type'] ,array('event_type_id') ,array('episode_id') ,array(), 'pt_tran_event_type_fk7');
            $table->addForeignKeyConstraint($aTableMap['pt_event']      ,array('event_id')      ,array('event_id')    ,array(), 'pt_tran_event_fk8');
   
           
            $oBuilder = new CalculationBuilder();
            $oGateway = new CalculationGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('t');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_rule_chain',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_rule_chain'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('rule_chain_id','guid',array());
            $table->addColumn('event_type_id','guid',array()); 
            $table->addColumn('system_id','guid',array()); 
            $table->addColumn('chain_name','string',array("length" => 100));
            $table->addColumn('chain_name_slug','string',array("length" => 100));
            $table->addColumn('rounding_option','smallint',array('default'=> 1,'comment' => 'Rounding method to apply floor|ceil|round'));
            $table->addColumn('cap_value','float',array('signed' => true, 'comment' =>'Max value +- that this event type can generate after all calculations have been made'));
            
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
            
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('rule_chain_id','enabled_from'),'pt_rule_chain_uiq1');
            $table->addForeignKeyConstraint($aTableMap['pt_event_type'],array('event_type_id'),array('event_type_id'),array(),'pt_rule_chain_fk1');
            $table->addForeignKeyConstraint($aTableMap['pt_system'],array('system_id'),array('system_id'),array(),'pt_rule_chain_fk2');
            
           
            $oBuilder = new RuleChainBuilder();
            $oGateway = new RuleChainGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('t');
            
            return $oGateway;
        });
        
        $oGatewayCol->addGateway('pt_chain_member',function() use ($c, $oSchema, $aTableMap) {
            $sActualTableName = $aTableMap['pt_chain_member'];
            $oEvent           = $c->getEventDispatcher();
            $oLogger          = $c->getAppLogger();
            $oDatabase        = $c->getDatabaseAdaper();
            $oGatewayCol      = $c->getGatewayCollection();
            
            # Transaction Header Table
            $table = $oSchema->createTable($sActualTableName);
            $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
            $table->addColumn('chain_member_id','guid',array());
            $table->addColumn('rule_chain_id','guid',array()); 
            $table->addColumn('rule_group_id','guid',array()); 
            $table->addColumn('order_seq','integer',array("unsigned" => true));
            $table->addColumn('enabled_from','datetime',array());
            $table->addColumn('enabled_to','datetime',array());
            
            
            $table->setPrimaryKey(array('episode_id'));
            $table->addUniqueIndex(array('chain_member_id','enabled_from'),'pt_chain_member_uiq1');
            $table->addForeignKeyConstraint($aTableMap['pt_rule_chain'],array('rule_chain_id'),array('rule_chain_id'),array(),'pt_chain_member_fk1');
            $table->addForeignKeyConstraint($aTableMap['pt_rule_group'],array('rule_group_id'),array('rule_group_id'),array(),'pt_chain_member_fk2');

            $oBuilder = new RuleChainMemberBuilder();
            $oGateway = new RuleChainMemberGateway($sActualTableName, $oDatabase, $oEvent, $table , null, $oBuilder);
    
            $oBuilder->setGateway($oGateway);
            $oBuilder->setLogger($oLogger);
            $oGateway->setTableQueryAlias('t');
            
            return $oGateway;
        });
        
        $this['gateway_collection'] = $oGatewayCol;    
        
    }
    
}
/* End of Class */