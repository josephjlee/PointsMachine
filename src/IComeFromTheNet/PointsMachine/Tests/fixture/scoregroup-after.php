<?php
return [
        'pt_score_group' => 
        [
            
            /* A Score Group with no Relations that enabled before now (Can be closed)*/
            [
                'episode_id'       => 5
                ,'score_group_id'  => '08AA3D4D-2166-2F9D-3C2F-393AFFC59125'
                ,'group_name'      => 'No Relations'
                ,'group_name_slug' => 'no_relations'
                ,'enabled_from'    => (new DateTime('now -7 day'))->format('Y-m-d')
                ,'enabled_to'      => (new DateTime('now'))->format('Y-m-d')
            
            ]
            
            /* A Score group enabled from now that was updated using existing episode */
            ,[
                 'episode_id'       => 6
                ,'score_group_id'  => 'CD48671F-DE97-6C41-70B9-1060FDC4433D'
                ,'group_name'      => 'Can be Updated Again'
                ,'group_name_slug' => 'can_be_updated_again'
                ,'enabled_from'    => (new DateTime('now'))->format('Y-m-d')
                ,'enabled_to'      => (new DateTime('3000-01-01'))->format('Y-m-d')
                
            ]
            
            /* A Score group that has is current and requires a new version when updated */
             ,[
                 'episode_id'       => 7
                ,'score_group_id'  => '30FAB3CD-B30A-C750-DF96-41A38A0998BC'
                ,'group_name'      => 'Req new version'
                ,'group_name_slug' => 'req_new_version'
                ,'enabled_from'    => (new DateTime('now - 7 day'))->format('Y-m-d')
                ,'enabled_to'      => (new DateTime('now'))->format('Y-m-d')
                
            ]
            /* Non Current Score Group */
             ,[
                 'episode_id'       => 8
                ,'score_group_id'  => '62A58FF0-97F7-1D23-A88D-C00D9542FF18'
                ,'group_name'      => 'Non Current'
                ,'group_name_slug' => 'non_current'
                ,'enabled_from'    => (new DateTime('now - 8 day'))->format('Y-m-d')
                ,'enabled_to'      => (new DateTime('now -1 day'))->format('Y-m-d')
                
            ]
            
             /* New Score Group */
             ,[
                 'episode_id'       => 9
                ,'score_group_id'  => '7DE4013D-BD1F-1709-15ED-9A24AA4C7A98'
                ,'group_name'      => 'Mock Score Group'
                ,'group_name_slug' => 'mock_score_group'
                ,'enabled_from'    => (new DateTime('now'))->format('Y-m-d')
                ,'enabled_to'      => (new DateTime('3000-01-01'))->format('Y-m-d')
                
            ]
            
            /* Created new Episode */
            
            ,[
                 'episode_id'       => 10
                ,'score_group_id'  => '30FAB3CD-B30A-C750-DF96-41A38A0998BC'
                ,'group_name'      => 'Created New Version'
                ,'group_name_slug' => 'created_new_version'
                ,'enabled_from'    => (new DateTime('now'))->format('Y-m-d')
                ,'enabled_to'      => (new DateTime('3000-01-01'))->format('Y-m-d')
                
            ]
          
        ]
        
    ];