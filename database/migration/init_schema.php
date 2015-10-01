<?php
namespace Migration\Components\Migration\Entities;

use Doctrine\DBAL\Connection,
    Doctrine\DBAL\Schema\AbstractSchemaManager as Schema,
    Doctrine\DBAL\Schema\Schema as ASchema,
    Migration\Components\Migration\EntityInterface;

class init_schema implements EntityInterface
{

    protected function getSystemTable(Connection $db, ASchema $sc)
    {
        # Systems Table
        $table = $sc->createTable("pt_system");
        $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
        $table->addColumn('system_id','guid',array());
        $table->addColumn('system_name','string',array("length" => 100));
        $table->addColumn('system_name_slug','string',array("length" => 100));
        $table->addColumn('enabled_from','datetime',array());
        $table->addColumn('enabled_to','datetime',array());
    
        $table->setPrimaryKey(array('episode_id'));
        $table->addUniqueIndex(array('system_id','enabled_from'),'pt_system_uiq1');
        
        
        $table = $sc->createTable("pt_system_zone");
        $table->addColumn('episode_id'   ,'integer' ,array("unsigned" => true,'autoincrement' => true));
        $table->addColumn('zone_id'       ,'guid'    ,array());
        $table->addColumn('zone_name'     ,'string'  ,array("length" => 100));
        $table->addColumn('zone_name_slug','string'  ,array("length" => 100));
        $table->addColumn('system_id'     ,'guid'    ,array("unsigned" => true));
        $table->addColumn('enabled_from'  ,'datetime',array());
        $table->addColumn('enabled_to'    ,'datetime',array());
    
        $table->setPrimaryKey(array('episode_id'));
        $table->addUniqueIndex(array('zone_id','enabled_from'),'pr_sys_zone_uk1');
        $table->addForeignKeyConstraint('pt_system',array('system_id'),array('system_id'),array(),'pt_sys_zone_fk1');
        
        
    }

    protected function getEventsTables(Connection $db, ASchema $sc)
    {
        # Event Types
        $table = $sc->createTable("pt_event_type");
        $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
        $table->addColumn('event_type_id','guid',array());
        $table->addColumn('event_name','string',array("length" => 100));
        $table->addColumn('event_name_slug','string',array("length" => 100));
        $table->addColumn('enabled_from','datetime',array());
        $table->addColumn('enabled_to','datetime',array());
        $table->addColumn('rounding_option','smallint',array('default'=> 1,'comment' => 'Rounding method to apply floor|ceil|round'));
        $table->addColumn('cap_value','float',array('signed' => true, 'comment' =>'Max value +- that this event type can generate after all calculations have been made'));
        
        $table->setPrimaryKey(array('episode_id'));
        $table->addUniqueIndex(array('event_type_id','enabled_from'),'pt_event_type_uiq1');
        
        
        # Event Instances
        $table = $sc->createTable("pt_event");
        $table->addColumn('event_id','integer',array("unsigned" => true,'autoincrement' => true));
        $table->addColumn('event_type_id','guid',array("unsigned" => true));
        $table->addColumn('event_created','datetime',array());
        $table->addColumn('process_date','datetime',array('comment' => 'Processing date for the calculator'));
        
        $table->setPrimaryKey(array('event_id'));
        $table->addForeignKeyConstraint('pt_event_type',array('event_type_id'),array('event_type_id'),array(),'pt_event_fk1');
        
        
        
    }
   
   protected function getScoresTables(Connection $db, ASchema $sc)
   {
        
        # Groups table for Scores
        $table = $sc->createTable("pt_score_group");
        $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
        $table->addColumn('score_group_id','guid',array());
        $table->addColumn('score_group_name','string',array("length" => 100));
        $table->addColumn('score_name_group_slug','string',array("length" => 100));
        $table->addColumn('is_disabled','smallint',array('default'=> 1,'comment' => 'Is this group in current use'));
        $table->addColumn('enabled_from','datetime',array());
        $table->addColumn('enabled_to','datetime',array());  
        
        $table->setPrimaryKey(array('episode_id'));
        $table->addUniqueIndex(array('score_group_id','enabled_from'),'pt_score_gp_uiq1');
        
        
        # Scores instance table
        $table = $sc->createTable("pt_score");
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
        $table->addForeignKeyConstraint('pt_score_group',array('score_group_id'),array('score_group_id'),array(),'pt_score_fk1');
        
   }
 
   
   protected function getScoringRulesTables(Connection $db, ASchema $sc)
   {
       # Rule Group
       $table = $sc->createTable("pt_rule_group");
       $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
       $table->addColumn('rule_group_id','guid',array());
       $table->addColumn('rule_group_name','string',array("length" => 100));
       $table->addColumn('rule_group_name_slug','string',array("length" => 100));
       $table->addColumn('enabled_from'  ,'datetime',array());
       $table->addColumn('enabled_to'    ,'datetime',array());
       $table->addColumn('group_priority','integer',array("unsigned" => true, 'default' => 0,'comment' => 'Order to apply the lower the value the earlier this group executed'));
       $table->addColumn('max_multiplier','float',array("unsigned" => true, 'comment' => 'Max value of multiplier once all rules are combined in this group allows group capping'));
       $table->addColumn('min_multiplier','float',array("unsigned" => true, 'comment' => 'Min value of multiplier once all rules are combined in this group allows group capping'));
       $table->addColumn('max_modifier'  ,'integer',array("unsigned" => true, 'comment' => 'Max value of modifier once all rules are combined in this group allows group capping'));
       $table->addColumn('min_modifier'  ,'integer',array("unsigned" => true, 'comment' => 'Min value of modifier once all rules are combined in this group allows group capping'));
       $table->addColumn('max_count'     ,'integer',array("unsigned" => true, 'comment' => 'Max number of scroing rules that can be used that linked to this group'));
       $table->addColumn('order_method'  ,'smallint',array("unsigned" => true, 'comment' => 'method of order to use 1= max 0=min'));
       $table->addColumn('is_mandatory'  ,'smallint',array("unsigned" => true,'comment' => 'Group always be applied unless not linked to system and score groups'));
       
       $table->setPrimaryKey(array('episode_id'));
       $table->addUniqueIndex(array('rule_group_id','enabled_from'),'pt_rule_gp_uiq1');
       
       
       # Rule Group Scores Groups Relations
       $table = $sc->createTable("pt_rule_group_scores");
       $table->addColumn('rule_group_id','guid',array());
       $table->addColumn('score_group_id','guid',array()); 
       $table->addColumn('enabled_from','datetime',array());
       $table->addColumn('enabled_to','datetime',array());
       
       $table->setPrimaryKey(array('rule_group_id','score_group_id','enabled_from'),'pt_rule_gp_uiq1');
       $table->addForeignKeyConstraint('pt_rule_group',array('rule_group_id'),array('rule_group_id'),array(),'pt_rule_gp_score_fk1');
       $table->addForeignKeyConstraint('pt_score_group',array('score_group_id'),array('score_group_id'),array(),'pt_rule_gp_score_fk2');
       
       # Rule Group Systems Relations
       $table = $sc->createTable("pt_rule_group_systems");
       $table->addColumn('rule_group_id','guid',array());
       $table->addColumn('system_id','guid',array()); 
       $table->addColumn('enabled_from','datetime',array());
       $table->addColumn('enabled_to','datetime',array());

       $table->setPrimaryKey(array('rule_group_id','system_id','enabled_from'));
       $table->addForeignKeyConstraint('pt_rule_group',array('rule_group_id'),array('rule_group_id'),array(),'pt_rule_group_sys_fk1');
       $table->addForeignKeyConstraint('pt_system',array('system_id'),array('system_id'),array(),'pt_rule_gp_sys_fk2');
       
       
       # Rule Tables
       $table = $sc->createTable("pt_rule");
       $table->addColumn('episode_id','integer',array("unsigned" => true,'autoincrement' => true));
       $table->addColumn('rule_id','guid',array());
       $table->addColumn('rule_name','string',array("length" => 100));
       $table->addColumn('rule_name_slug','string',array("length" => 100));
       $table->addColumn('enabled_from','datetime',array());
       $table->addColumn('enabled_to','datetime',array());
       
       $table->setPrimaryKey(array('episode_id'));
       $table->addUniqueIndex(array('rule_id','enabled_from'),'pt_rule_uiq1');
       
       
       # Rule System Zones
       $table = $sc->createTable("pt_rule_sys_zone");
       $table->addColumn('rule_id','guid',array());
       $table->addColumn('zone_id','guid',array()); 
       $table->addColumn('enabled_from','datetime',array());
       $table->addColumn('enabled_to','datetime',array());

       $table->setPrimaryKey(array('zone_id','rule_id','enabled_from'));
       $table->addForeignKeyConstraint('pt_rule',array('rule_id'),array('rule_id'),array(),'pt_rule_sys_zone_fk1');
       $table->addForeignKeyConstraint('pt_system_zone',array('zone_id'),array('zone_id'),array(),'pt_rule_sys_zone_fk2');
       
       
       # Rule transaction
       $table = $sc->createTable("pt_scoring_transaction");
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
       
       $table->addColumn('processing_date' ,'datetime',array());
       $table->addColumn('occured_date'    ,'datetime' ,array());
       
       $table->setPrimaryKey(array('process_id'));
       $table->addForeignKeyConstraint('pt_rule'       ,array('rule_id')       ,array('episode_id') ,array(), 'pt_tran_rule_fk1');
       $table->addForeignKeyConstraint('pt_rule_group' ,array('rule_group_id') ,array('episode_id') ,array(), 'pt_tran_rule_gp_fk2');
       $table->addForeignKeyConstraint('pt_system'     ,array('system_id')     ,array('episode_id') ,array(), 'pt_tran_sys_fk3');
       $table->addForeignKeyConstraint('pt_system_zone',array('zone_id')       ,array('episode_id') ,array(), 'pt_tran_sys_zone_fk4');
       $table->addForeignKeyConstraint('pt_score'      ,array('score_id')      ,array('episode_id') ,array(), 'pt_tran_score_fk5');
       $table->addForeignKeyConstraint('pt_score_group',array('score_group_id'),array('episode_id') ,array(), 'pt_tran_score_gp_fk6');
       $table->addForeignKeyConstraint('pt_event_type' ,array('event_type_id') ,array('episode_id') ,array(), 'pt_tran_event_type_fk7');
       $table->addForeignKeyConstraint('pt_event'      ,array('event_id')      ,array('event_id')    ,array(), 'pt_tran_event_fk8');
       
       
   }
    
    public function buildSchema(Connection $db, ASchema $schema)
    {
        
        $this->getSystemTable($db,$schema);
        $this->getEventsTables($db,$schema);
        $this->getScoresTables($db,$schema);
        $this->getScoringRulesTables($db,$schema);
        
        return $schema;
    }
    
    public function up(Connection $db, Schema $sc)
    {
        
        
        $schema = $this->buildSchema($db,new ASchema());
        
        $queries = $schema->toSql($db->getDatabasePlatform()); // get queries to create this schema.
        
        # execute setup queries
        foreach($queries as $query) {
            
            echo $query . PHP_EOL;
            $db->exec($query);    
        }
        
    }

    public function down(Connection $db, Schema $sc)
    {


    }


}
/* End of File */