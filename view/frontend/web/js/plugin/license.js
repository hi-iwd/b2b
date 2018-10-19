define([
    'jquery',
    'b2b'
], function($ji, $b2b) {
    'use strict';
  
    $ji.widget('iwd.b2blicense', {
      
        options: {
        },
          
        _create: function () {
            
            if($ji('#b2b-login-modal').length)
                $ji('#b2b-login-modal').modaliwd({"show":true,"backdrop":"static","keyboard":false});
            
            $b2b.init();
        },
    
    });

    return $ji.iwd.b2blicense;
  
});

