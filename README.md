# PointsMachine
Library to build Points Systems for MySql.

#Overview
PointsMachine allows user defined rule systems that can be configured though values stored in database. 

PointsMachine does not provide the GUI but is a set of classes that will manage the processing and storage.

A points run starts with a selection of scores that are then applied to a formula defined as a series of rule groups chain together with each group containg one to many adjustment rules that either modify or multiply the base score. These scorce can then be rounded and capped.  


# Getting Started.
To show you how this library is to be used I will implement a DKP or (Dragon Kill Points) system that are used in Gaming Guids. (I used to play alot of World of Warcraft).

## Boostrap the Library

First step is to create the system but before we can do that we need to bootstrap the libraries container.

```php

// Start the Database

$connectionParams = array(
    'dbname' => $DEMO_DATABASE_SCHEMA,
    'user' => $DEMO_DATABASE_USER,
    'password' => $DEMO_DATABASE_PASSWORD,
    'host' => $DEMO_DATABASE_HOST,
    'driver' => $DEMO_DATABASE_TYPE,
);

$oConn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);


// Start the Logger

$oLog = new BufferedQueryLogger();
$oLog->setMaxBuffer(100);
$oConn->getConfiguration()->setSQLLogger($oLog);

// create a log channel
$ologger = new Logger('runner');
$ologger->pushHandler(new StreamHandler(__DIR__.'/out.log', Logger::DEBUG));


// Start Event Dispatcher

$oEvent = new EventDispatcher();

// Create the Container

$oPointsContainer = new PointsMachineContainer($oEvent,$oConn,$ologger);
 
// Bootstrap the container

$oPointsContainer->boot(new \DateTime('now'));


```

## System and SystemZones

After the container is started we will be able to start configuring this system.

I use an Active Record pattern to build this libraries data model each entity has both an entity::save() and an entity::remove().
The arguments are as follows:
1. Table Gateway for the database table this entity represents.
2. The Application Logger.


```php

   
  $oSystemGateway = $oPointsContainer->getGatewayCollection()->getGateway('pt_system');
  $oLogger        = $oPointsContainer->getAppLogger();
 
  
  $oPointSystem = new PointSystem($oSystemGateway,$oLogger);
    
  $oPointSystem->sSystemID       = $oPointSystem->guid();
  $oPointSystem->sSystemName     = 'Raid Calculator';
  $oPointSystem->sSystemNameSlug = 'raid_calculator';
  
  $bResult = $oPointSystem->save();
  $aLastResult = $oPointSystem->getLastQueryResult();
  
  if(false === $bResult) {
    throw new \RuntimeException($aLastResult['msg']);
  }  

```

After we have defind the system basic details we should consider if we need any SystemZones.  

I best analogy is sales territories these zones are used to further filter which rules should apply and like territories should be mutually exclusive.

For our Raid Calcualtor I am going to use character classes a setup might go like.


```php

 // Create some SystemZones
  
  $oSystemZoneGateway = $oPointsContainer->getGatewayCollection()->getGateway('pt_system_zone');
  
  
  $oPriestZone  = new PointSystemZone($oSystemZoneGateway,$oLogger);
  $oWarriorZone = new PointSystemZone($oSystemZoneGateway,$oLogger);
  $oMageZone    = new PointSystemZone($oSystemZoneGateway,$oLogger);
  
  $oPriestZone->sZoneID       = $oPriestZone->guid();
  $oPriestZone->sSystemID     = $oPointSystem->sSystemID;
  $oPriestZone->sZoneName     = 'Priest Class';
  $oPriestZone->sZoneNameSlug = 'priest_class';
  
  $oWarriorZone->sZoneID       = $oPriestZone->guid();
  $oWarriorZone->sSystemID     = $oPointSystem->sSystemID;
  $oWarriorZone->sZoneName     = 'Warrior Class';
  $oWarriorZone->sZoneNameSlug = 'warrior_class';
  
  $oMageZone->sZoneID       = $oPriestZone->guid();
  $oMageZone->sSystemID     = $oPointSystem->sSystemID;
  $oMageZone->sZoneName     = 'Mage Class';
  $oMageZone->sZoneNameSlug = 'mage_class';
  
  foreach(array($oPriestZone,$oWarriorZone,$oMageZone) as $oZone) {
    
    $bResult = $oZone->save();
    $aLastResult = $oZone->getLastQueryResult();
  
    if(false === $bResult) {
      throw new \RuntimeException($oZone->sZoneName .' '.$aLastResult['msg']);
    }
    
  }


```

## Events Types

This entity is used to group those occurances that would cause a calcualtion run. 

In this raid calcualtor an event could a dungeon raid, an outdoor raid, ro a PVP raid.


```php

  $oEventTypeGateway =  $oPointsContainer->getGatewayCollection()->getGateway('pt_event_type');
    
  $oDungeonRaidEventType  = new EventType($oEventTypeGateway,$oLogger);  
  $oOutdoorRaidEventType = new EventType($oEventTypeGateway,$oLogger);  
  $oPVPRaidEventType     = new EventType($oEventTypeGateway,$oLogger);  
  
   
  $oDungeonRaidEventType->sEventTypeID  = $oDungeonRaidEventType->guid();
  $oDungeonRaidEventType->sEventName    = 'Dungeon Raid';
  $oDungeonRaidEventType->sEventNameSlug = 'dungeon_raid';
  
  $oOutdoorRaidEventType->sEventTypeID  = $oOutdoorRaidEventType->guid();
  $oOutdoorRaidEventType->sEventName    = 'Outdoor Raid';
  $oOutdoorRaidEventType->sEventNameSlug = 'outdoor_raid';
  
  $oPVPRaidEventType->sEventTypeID  = $oPVPRaidEventType->guid();
  $oPVPRaidEventType->sEventName    = 'PVP Raid';
  $oPVPRaidEventType->sEventNameSlug = 'pvp_raid';
  
  foreach(array($oDungeonRaidEventType,$oOutdoorRaidEventType,$oPVPRaidEventType) as $oEventType) {
    
    $bResult = $oEventType->save();
    $aLastResult = $oEventType->getLastQueryResult();
  
    if(false === $bResult) {
      throw new \RuntimeException($oZone->sEventName .' '.$aLastResult['msg']);
    }
    
  }



```

## Score and Score Groups

Scores are used to define starting values.

For this raid calculator I am going to have each score represent an allowance for attendence so each character that attends this raid will start of with x points. 

Score Groups are used to provide categories for scores.  

We start of with 3 Score Groups.

1. PVE Scores.
2. PVP Scores.
3. Donations.


Score groups can be used as extra filters during a calculation run for example I don't want PVE rules being applied to PVP scores.

```php


  $oScoreGroupGateway =  $oPointsContainer->getGatewayCollection()->getGateway('pt_score_group');
  
    
  $oPVEScoreGroup      = new ScoreGroup($oScoreGroupGateway,$oLogger); 
  $oPVPScoreGroup      = new ScoreGroup($oScoreGroupGateway,$oLogger);
  $oDonationScoreGroup = new ScoreGroup($oScoreGroupGateway,$oLogger);
  
  $oPVEScoreGroup->sScoreGroupID = $oPVEScoreGroup->guid();
  $oPVEScoreGroup->sGroupName    = 'PVE Score Group';
  $oPVEScoreGroup->sGroupNameSlug = 'pve_score_group';
  
  $oPVPScoreGroup->sScoreGroupID = $oPVPScoreGroup->guid();
  $oPVPScoreGroup->sGroupName    = 'PVP Score Group';
  $oPVPScoreGroup->sGroupNameSlug = 'pvp_score_group';
  
  $oDonationScoreGroup->sScoreGroupID = $oDonationScoreGroup->guid();
  $oDonationScoreGroup->sGroupName    = 'Donations Score Group';
  $oDonationScoreGroup->sGroupNameSlug = 'donations_score_group';
  
  foreach(array($oPVEScoreGroup,$oPVPScoreGroup,$oDonationScoreGroup) as $oScoreGroup) {
    
    $bResult = $oScoreGroup->save();
    $aLastResult = $oScoreGroup->getLastQueryResult();
  
    if(false === $bResult) {
      throw new \RuntimeException($oScoreGroup->sEventName .' '.$aLastResult['msg']);
    }
    
  }
  



```

Create some scores that use these groups.

Need to create

1. Large donation.
2. Small donation.
3. Average donation.
4. 5 man Dungeon
5. 10 man Dungeon
6. 20 man Dungeon
7. PVP Participation

```php

$oScoreGateway = $oPointsContainer->getGatewayCollection()->getGateway('pt_score');
  

  
  $oLargeDonationScore   = new Score($oScoreGateway,$oLogger);
  $oSmallDonationScore   = new Score($oScoreGateway,$oLogger);
  $oAverageDonationScore = new Score($oScoreGateway,$oLogger);
  $oDungeon5ManScore      = new Score($oScoreGateway,$oLogger);
  $oDungeon10ManScore     = new Score($oScoreGateway,$oLogger);
  $oDungeon20ManScore     = new Score($oScoreGateway,$oLogger);
  $oPVPParticipationScore = new Score($oScoreGateway,$oLogger);
  
  $oLargeDonationScore->sScoreID        = $oLargeDonationScore->guid();
  $oLargeDonationScore->sScoreGroupID   = $oDonationScoreGroup->sScoreGroupID;
  $oLargeDonationScore->sScoreName      = 'Large Donations';
  $oLargeDonationScore->sScoreNameSlug  = 'large_donations';
  $oLargeDonationScore->fScoreValue     = 20;
  
  $oSmallDonationScore->sScoreID        = $oSmallDonationScore->guid();
  $oSmallDonationScore->sScoreGroupID   = $oDonationScoreGroup->sScoreGroupID;
  $oSmallDonationScore->sScoreName      = 'Small Donations';
  $oSmallDonationScore->sScoreNameSlug  = 'small_donations';
  $oSmallDonationScore->fScoreValue     = 5;
  
  $oAverageDonationScore->sScoreID        = $oAverageDonationScore->guid();
  $oAverageDonationScore->sScoreGroupID   = $oDonationScoreGroup->sScoreGroupID;
  $oAverageDonationScore->sScoreName      = 'Average Donations';
  $oAverageDonationScore->sScoreNameSlug  = 'average_donations';
  $oAverageDonationScore->fScoreValue     = 10;
  
  $oDungeon5ManScore->sScoreID        = $oDungeon5ManScore->guid();
  $oDungeon5ManScore->sScoreGroupID   = $oPVEScoreGroup->sScoreGroupID;
  $oDungeon5ManScore->sScoreName      = '5 Man Dungeon';
  $oDungeon5ManScore->sScoreNameSlug  = '5_man_dungeon';
  $oDungeon5ManScore->fScoreValue     = 5;
  
  $oDungeon10ManScore->sScoreID        = $oDungeon10ManScore->guid();
  $oDungeon10ManScore->sScoreGroupID   = $oPVEScoreGroup->sScoreGroupID;
  $oDungeon10ManScore->sScoreName      = '10 Man Dungeon';
  $oDungeon10ManScore->sScoreNameSlug  = '10_man_dungeon';
  $oDungeon10ManScore->fScoreValue     = 20;
  
  
  $oDungeon20ManScore->sScoreID       = $oDungeon20ManScore->guid();
  $oDungeon20ManScore->sScoreGroupID   = $oPVEScoreGroup->sScoreGroupID;
  $oDungeon20ManScore->sScoreName      = '20 Man Dungeon';
  $oDungeon20ManScore->sScoreNameSlug  = '20_man_dungeon';
  $oDungeon20ManScore->fScoreValue     = 20;
  
  $oPVPParticipationScore->sScoreID       = $oPVPParticipationScore->guid();
  $oPVPParticipationScore->sScoreGroupID   = $oPVPScoreGroup->sScoreGroupID;
  $oPVPParticipationScore->sScoreName      = 'PVP Participation';
  $oPVPParticipationScore->sScoreNameSlug  = 'pvp_participation';
  $oPVPParticipationScore->fScoreValue     = 15;
    
    
  foreach(array($oLargeDonationScore,   
                $oSmallDonationScore,   
                $oAverageDonationScore, 
                $oDungeon5ManScore,     
                $oDungeon10ManScore,    
                $oDungeon20ManScore,    
                $oPVPParticipationScore ) as $oScore) {
                  
    $bResult = $oScore->save();
    $aLastResult = $oScore->getLastQueryResult();
  
    if(false === $bResult) {
      throw new \RuntimeException($oScore->sScoreName .' '.$aLastResult['msg']);
    }
    
  } 
  

```

# Adjustment Rules and Adjustment Groups

## Event : Donation 

Start with a formula that will convert the score the elements of that formula will become your Rule Groups and the values 
that this group can take will be your adjustment rules. 

For our Donation Event the following conversion formula should be used.

Sum All Donations for the week (Base Score each * Class Difficulty Modifer + Demand Bonus ) <= 500 

For a single week Sunday - Monday following week earn no more than 500 points. Each score will have a class modifer and a demand bonus applied.

```php

  $oAdjGroupGateway       = $oPointsContainer->getGatewayCollection()->getGateway('pt_rule_group');
  $oAdjRuleGateway        = $oPointsContainer->getGatewayCollection()->getGateway('pt_rule');
  $oRuleChainGateway      = $oPointsContainer->getGatewayCollection()->getGateway('pt_rule_chain');
  $oChainMemberGateway    = $oPointsContainer->getGatewayCollection()->getGateway('pt_chain_member');
  $oAdjRuleZones          = $oPointsContainer->getGatewayCollection()->getGateway('pt_rule_sys_zone');
  $oAdjGroupLimitsGateway = $oPointsContainer->getGatewayCollection()->getGateway('pt_rule_group_limits');
  

```

The first group will be the 'Class Difficulty' this group will contain adjustment rules that will affect a score based on the character class.
We have a number of qualifications that we need to configure as where building this adjustment group.

1. Only want to apply 1 modifier 
2. Use the system zones to stop control which modifers are applicable .
3. This group should always be applied. 

The Second Group the 'Demand Bonus' will contain a number of rules of which one should be applied. Only if they are attached to the calculation run.


```php
  
  // Define the group
  
  $oClassDifficultyAdjGroup = new AdjustmentGroup($oAdjGroupGateway,$oLogger);
  
  
  
  $oClassDifficultyAdjGroup->sAdjustmentGroupID = $oClassDifficultyAdjGroup->guid();
  $oClassDifficultyAdjGroup->sGroupName         = 'Class Difficulty Group';
  $oClassDifficultyAdjGroup->sGroupNameSlug     = 'class_difficulty_group';
  
  // These settings ensure only 1 modifer is used from this group and will be the largest
  $oClassDifficultyAdjGroup->iMaxCount          = 1; 
  $oClassDifficultyAdjGroup->iOrderMethod       = AdjustmentGroup::ORDER_HIGHT;
  
  // This will ensure that all modifers are included in the calculation run (filtered out by system zones)
  $oClassDifficultyAdjGroup->bIsMandatory       = 1;
  
  
  
  
  
  
  // Link rules to their System Zones
  
  

```


## Event: Dungon Run





#Concepts Overview:

##Period of Validity.
Many of the entities (Rules,Scores,Chains) all have a period of applicability. This library does not allow for future data only current. For example a delete operation will cause an existing entity to be closed meaning the databse record is not removed only its valid date range changed. If new entity is added it is active from that date. 

##Systems and Zones
All Formulas and Rules are linked to a System if you are running multiple reward schemes they would each belong to a different system. 




## Score and Score Groups
A Score is a starting point each score has a value. This value will be modified by the processor.

You may have many scores in calculation run each score is modified independently. 

Each Score may belong to a single Score Group. 

Each product in your catelog could be assigned its own score with product categories making up the score groups.

## Adjustment Rules and Adjustment Groups
An adjustment rule contains a modifier and a multipler they can be:
1. Linked to specific system zones.
2. Linked to only a single Adjustment Group.

A rule multipler is a multiplication operation.
A rule modifier is a addition operation.


An Adjustment Group is a container for one or more Adjustment Rules.

A group has the following functions.
1. Be linked to only apply on specific Score Groups (different groups for each product category)
2. Be linked to one more Points Systems. (reuse one group across multiple point systems)
3. Have a maximum and minimum multipler and modifier (based on combined value of adjustment rules applied in the run)
4. Group can me made to be mandatory (always apply its rules)
5. Limit on number of adjustment rules that are applied to score (limit to a single rule).
6. Change how the linked rules are ordered (max or min).

For example you can have an adjustment group apply only a single largest rule from those included in a calculation run.



> If you developing a system and do not need the end user to configure the values through a GUI I would recommend that avoid using a library like mine.



