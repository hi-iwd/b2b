define([
    'jquery',
    'b2b'
], function($ji, $b2b) {
    'use strict';
  
    $ji.widget('iwd.b2blogin', {
      
        options: {
        },
          
        _create: function () {
            
            if($ji('#b2b-login-modal').length)
                $ji('#b2b-login-modal').modaliwd({"show":true,"backdrop":"static","keyboard":false});
            
            $b2b.init();
        },
    
    });

    window.IWDB2B.Login = {
        
        init: function(){
            window.IWDB2B.Register.init();
            
            this.triggerLogin();
            this.triggerForgot();
            this.triggerForgotSubmit();
        },

        
        triggerLogin: function(){
            $ji(document).on('submit', '#b2b-login-post-form', function(event){
                window.IWDB2B.Decorator.showLoader();
                event.preventDefault();
                
                $ji('#signin-error').remove();
                $ji('.b2b-messages-container').html('');
                
                var form = $ji('#b2b-login-post-form').serializeArray();
                $ji.post(window.IWDB2B.App.config.signInUrl, form, window.IWDB2B.Login.parseLoginResponse, 'json');
            });
        },

        
        parseLoginResponse: function(response){
            window.IWDB2B.Decorator.hideLoader();
            if (response==null){return;}
            
            if (typeof(response.error) !="undefined" && response.error==1){
                //if error show error message               
                $ji('#signin-error').remove();
                $ji('<div />').attr('id','signin-error').addClass('signin-error').html(response.message).insertBefore('#b2b-login-post-form'); 
            }

            if (typeof(response.linkAfterLogin)!="undefined"){
                
                if (typeof(response.message)!="undefined"){
                    //show message and redirect to url after 2.5s;
                    setTimeout(function(){
                        window.IWDB2B.App.setLocation(response.linkAfterLogin);
                    }, 2500);
                }else{
                    //just redirect to url
                    setTimeout(function(){
                        window.IWDB2B.App.setLocation(response.linkAfterLogin);
                    }, 500);                    
                }
                
            }
            
            if (typeof(response.location)!="undefined"){
                var time = 1;
                if (typeof(response.message)!="undefined")
                  time = 2500;
                setTimeout(function(){
                  window.IWDB2B.App.setLocation(response.location);
                },time);
            }
            
        },
                
        triggerForgot: function(){
            $ji('#b2b-forgot-password').click(function(e){
                e.preventDefault();
                $ji('#b2b-forgot-form').removeClass('hidden');
                $ji('#b2b-signin-modal').show();
                $ji('#b2b-login-form').addClass('hidden');
                $ji('.b2b-login-modal-dialog').addClass('hidden');
                
                $ji('#b2b-login-modal').hide();
                
            });

            $ji('#back-link-login,.b2b-modal a.close').click(function(e){
                e.preventDefault();
                $ji('#b2b-login-modal').show();
                
                $ji('#b2b-forgot-form').addClass('hidden');
                $ji('#b2b-signin-modal').hide();
                $ji('#b2b-login-form').removeClass('hidden');
                $ji('#signin-error').remove();
                $ji('.b2b-login-modal-dialog').removeClass('hidden');

            });
        },
                
        triggerForgotSubmit: function(){
            $ji(document).on('submit', '#form-validate-forgot', function(e){
                e.preventDefault();
                
                $ji('.signin-error').remove();
                $ji('.b2b-messages-container').html('');
                
                window.IWDB2B.Decorator.showLoader();
                var form = $ji('#form-validate-forgot').serializeArray();
                $ji.post(window.IWDB2B.App.config.forgotPasswordUrl, form, window.IWDB2B.Login.parseForgotPasswordResponse, 'json');
            });
        },
        
        
        parseForgotPasswordResponse: function (response){
            window.IWDB2B.Decorator.hideLoader();
            $ji('.signin-error').remove();
            if (typeof(response.error)!="undefined"){
                $ji('<div />').attr('id','signin-error').addClass('signin-error').html(response.message).insertBefore('#form-validate-forgot'); 
            }
                
            if (typeof(response.link)!="undefined"){
                $ji('<div />').attr('id','signin-error').addClass('signin-error').html(response.link).insertBefore('#form-validate-forgot'); 
            }
            
            
            if (typeof(response.location)!="undefined"){
              var time = 1;
              if (typeof(response.message)!="undefined")
                time = 2500;
              setTimeout(function(){
                window.IWDB2B.App.setLocation(response.location);
              },time);
            }
            
        },      
    };

    window.IWDB2B.Register = {
        init: function(){
            this.triggerRegister();
          
            this.initSameAsShipping();
            this.sameAsShipping();
        }, 
        
        triggerRegister: function(){
            $ji(document).on('submit', '#b2b-register-form', function(event){
                window.IWDB2B.Decorator.showLoader();
            });
        },
        
        initSameAsShipping: function(){
            $ji('#same_as_shipping').click(function(){
              window.IWDB2B.Register.sameAsShipping();
            })
        },
        
        sameAsShipping: function(){
          if ($ji('#same_as_shipping').prop('checked')==true){
            $ji('#b2b-billing-form').fadeOut('fast');
            $ji('#billing_same_as_shipping').val('1');
            $ji('#same_as_shipping').find('input').each(function(){
                $ji(this).removeClass('required-entry')
            });
          }else{
            $ji('#b2b-billing-form').fadeIn();
            $ji('#billing_same_as_shipping').val('fast');
            $ji('#same_as_shipping').find('input').each(function(){
                $ji(this).addClass('required-entry')
            });
          }
        }
        
    };
  
    return $ji.iwd.b2blogin;
  
});

