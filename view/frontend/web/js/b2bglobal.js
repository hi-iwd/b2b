(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            "jquery",
            "domReady",
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($ji, domReady) {

  $ji.widget('iwd.b2bglobal', {
      _create: function () {
          window.IWDB2B.Global.init();
      },
  });

  window.IWDB2B = window.IWDB2B||{};

  window.IWDB2B.Global = {
      init: function(){
          $ji(document).ready(function(){
              
              $ji(window).scroll(function(){
                  window.IWDB2B.Global.footerMode();
              });
              
              window.IWDB2B.Global.footerMode();
              
              var is_touch_device = 'ontouchstart' in document.documentElement;
              if(is_touch_device)
                  $ji('body').addClass('b2b-touch-device');
              else
                  $ji('body').addClass('b2b-notouch-device');
          });
      },
      
      footerMode: function(){
          if($ji('.b2b-sticky-footer').length){
              var scrollBottom = $ji(document).height() - $ji(window).height() - $ji(window).scrollTop();
              offset = $ji('.b2b-sticky-footer').offset();
              var footBottom = $ji(document).height() - offset.top;
              
              var h = $ji('.b2b-sticky-footer').outerHeight();
              if(scrollBottom < 10){
                  $ji('body').addClass('b2b-at-bottom');
              }
              else{
                  if(scrollBottom > footBottom || (scrollBottom > 25 && footBottom > 140))
                      $ji('body').removeClass('b2b-at-bottom');
              }
          }
      },
      
  };
  
  return $ji.iwd.b2bglobal;

}));
