<?php
return [
        'pt_system' => 
        [
            [
                'episode_id' => 1
                ,'system_id'  => '9B753E70-881B-F53E-2D46-8151BED1BBAF'
                ,'system_name' => 'Donations System'
                ,'system_name_slug' => 'donations_system'
                ,'enabled_from' => (new DateTime())->format('Y-m-d')
                ,'enabled_to'   => (new DateTime('3000-01-01'))->format('Y-m-d')
            ]
            
            ,[
                'episode_id' => 2
                ,'system_id'  => '1E8659DA-127C-BE67-6DF5-EB6554AD4B0'
                ,'system_name' => 'Mock System'
                ,'system_name_slug' => 'mock_system'
                ,'enabled_from' => (new DateTime())->format('Y-m-d')
                ,'enabled_to'   => (new DateTime('3000-01-01'))->format('Y-m-d')
            ]
            
        ]
      
    
];