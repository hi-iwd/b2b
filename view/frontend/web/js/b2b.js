(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define([
            "jquery",
            "domReady",
            "jquery/jquery.metadata",
            "b2bnicescroll",
            "b2bjcarousel",
            "b2bglobal",
            "b2bmodal",
            'jquery/jquery-storageapi'
        ], factory);
    } else {
        factory(jQuery);
    }
}(function ($ji, domReady) {

  $ji.widget('iwd.b2b', {
      _create: function () {
          window.IWDB2B.Init.init();
      },
  });

  window.IWDB2B = window.IWDB2B||{};

  window.IWDB2B.Init = {
          init: function(){
              // remove minicart section from storage to prevent issue
              storageInvalidation = $ji.initNamespaceStorage('mage-cache-storage-section-invalidation').localStorage;
              sections = storageInvalidation.keys();
              storageInvalidation.remove('minicart');
              //

              $ji(document).ready(function(){
                  if (typeof(IWDB2BConfig) !="undefined"){
                      window.IWDB2B.App.config = $ji.parseJSON(IWDB2BConfig);
                  }

                  window.IWDB2B.App.init();
              });
          }
  };

  window.IWDB2B.App = {
        xhr : null,
        config: null,

        init: function(){
            if (window.IWDB2B.App.config.extensionActive==1){

                if (typeof(window.IWDB2B.Login)!="undefined"){
                  window.IWDB2B.Login.init();
                }
                if (typeof(window.IWDB2B.QuickSearch)!="undefined"){
                  window.IWDB2B.QuickSearch.init();
                  window.IWDB2B.AjaxCart.init();
                }
                if (typeof(window.IWDB2B.List)!="undefined")
                  window.IWDB2B.List.init();

                if(typeof(window.IWDB2B.Login) == "undefined"){
                  // decorator should be run before filters below
                  window.IWDB2B.Decorator.init();

                  if(typeof(window.IWDB2B.Filter) != "undefined")
                    window.IWDB2B.Filter.init();
                  if(typeof(window.IWDB2B.FilterReorder) != "undefined")
                    window.IWDB2B.FilterReorder.init();
                  if(typeof(window.IWDB2B.Download)!="undefined")
                    window.IWDB2B.Download.init();
                  if(typeof(window.IWDB2B.Stock)!="undefined")
                    window.IWDB2B.Stock.init();
                }
            }
        },

        setLocation: function(url){
            window.location.href = url;
        }
  };

//////// decorator

  window.IWDB2B.Decorator = {
      widthWindow:null,
      maxColumHeight: null,
      IWDB2BTableWidthMode: 'auto',
      popupResize: null,

      matrixProducts:{},
      matrixProductSections:{},

      init: function(){

          //// show tables
          $ji('#quick-list-products').removeClass('hidden');
          $ji('.pre_loader').remove();
          ////

          window.IWDB2B.Decorator.widthWindow = $ji(window).width();

          this.backToTopQueue();
          window.IWDB2B.Tooltip.init();
          this.initTableScroll();
          this.initChangeImage();

          this.initWindowEventDecorators();

          this.initMatrixInfo();

          this.initPriceTooltip();
      },

      initChangeImage: function(){
          $ji(document).on('click','.more-views li a', function(e){
              e.preventDefault();
              var url = $ji(this).data('image');
              $ji('#b2b-image').attr('src', url);
          });
      },

      initTableScroll: function(){
          var niceScrollOpt = {/*touchbehavior:true,*/
                  railpadding: {top: 3, right: 0, left: 0, bottom: 0},
                  cursorborder: "",
                  cursorcolor: "#ccc",
                  cursoropacitymin: 0.35,
                  cursoropacitymax: 0.35,
                  cursorwidth: 9,
                  boxzoom: false,
                  railoffset: {top: 11}
              };

           var niceScroll = $ji(".b2b-table-scroll").niceScroll(niceScrollOpt);

           niceScroll.onscrollend = function(){
               window.IWDB2B.Decorator.setTableHeaderPosition();
           }
      },

      initMatrixTableScroll: function(grid){

          grid_scroll = grid.parents(".matrix_table_scroll");
          if(!grid.hasClass('matrix_srollable'))
              return false;

          var niceScrollOpt = {/*touchbehavior:true,*/
                  railpadding: {top: 0, right: 0, left: 0, bottom: 0},
                  cursorborder: "",
                  cursorcolor: "#ccc",
                  cursoropacitymin: 0.35,
                  cursoropacitymax: 0.35,
                  cursorwidth: 9,
                  boxzoom: false,
                  railoffset: {top: 5}
              };

          var niceScroll = grid_scroll.niceScroll(niceScrollOpt);
      },

      destroyMatrixTableScroll: function(grid){
          grid_scroll = grid.parents(".matrix_table_scroll");
          if(!grid.hasClass('matrix_srollable'))
              return false;

          grid_scroll.getNiceScroll().remove();
      },

      reInitTableScroll: function(){
          $ji(".b2b-table-scroll").getNiceScroll().resize();
      },

      searchMatrixTablesScrollable: function(){
          $ji('.matrix_table').each(function(){
              window.IWDB2B.Decorator.reInitMTPosition($ji(this));
          });
      },

      reInitMTPosition: function(table){
          var w = table.width();
          var wc = table.parents('.matrix_table_scroll').width();
          if(w > wc)
              table.addClass('matrix_srollable');
          else
              table.removeClass('matrix_srollable');
      },

      /** DROPDOWN SCROLL **/
      initDropScroll: function(){
          $ji(".quick-search-result").niceScroll({cursorborder:"",cursorcolor:"#ccc", cursoropacitymax:0.35,cursorwidth:9,boxzoom:false, railoffset:{left:-11}});
          $ji(".quick-search-result").niceScroll().scrollend(function(info){
              var y = info['end']['y'];
              $ji('.b2b-search-loader').animate({'top': y}, 200, function() {
                  // Animation complete.
              });
              // detect if need load more results
              var h1 = $ji('.quick-search-wrapper').height();
              var h2 = $ji('.quick-search-result').innerHeight();

              diff = h1-h2;
              if(diff > 0){
                  diff=diff-300;
                  if(y>diff){
                      // try load more results
                      window.IWDB2B.QuickSearch.loadMore();
                  }
              }
          });
      },

      hideDropScroll: function(){
          $ji(".quick-search-result").getNiceScroll().hide();
      },

      /** DESCRIPTION SCROLL **/
      initDescriptionScroll: function(){
          $ji(".b2b-product-description .std").niceScroll({cursorborder:"",cursorcolor:"#ccc", cursoropacitymax:1,cursorwidth:9,boxzoom:false, railoffset:{left:11}});
      },

      hideDescriptionScroll: function(){
          $ji(".b2b-product-description .std").getNiceScroll().hide();
      },

      updateDescriptionScroll: function(){
          $ji(".b2b-product-description .std").getNiceScroll().show();
          $ji(".b2b-product-description .std").getNiceScroll().resize();
      },

      showLoader: function(){
          $ji('.b2b-loader-container').show();
      },

      hideLoader: function(){
          $ji('.b2b-loader-container').hide();
      },

      lockBlock: function(wrapElement, skip_wrapper){
          if(typeof(skip_wrapper) == 'undefined' || skip_wrapper == undefined || !skip_wrapper)
              skip_wrapper = false;

         // check if loader exists
         if(wrapElement.parents('.locker-block').length){
             var c = wrapElement.data('count_loaders');
             if(typeof(c) == 'undefined' || c == undefined || !c || c == '')
                 c = 0;
             c = c + 1;
             wrapElement.data('count_loaders', c);
         }
         else{
             if(!skip_wrapper)
                 wrapElement.wrap('<div class="locker-block"></div>');

             //$ji('<div>').addClass('table-locker-block').appendTo(wrapElement);
             $ji('.load-more-products').hide();
             $ji('<div>').addClass('view-all-loader').appendTo(wrapElement);
             wrapElement.data('count_loaders', 1);
         }
      },

      unLockBlock: function(wrapElement){
          // check if other process created loader too
          var c = wrapElement.data('count_loaders');
          if(typeof(c) == 'undefined' || c == undefined || !c || c == '')
              c = 0;

          c = c-1;
          if(c < 0)
              c = 0;
          wrapElement.data('count_loaders', c);

          if(c == 0){
              var $locker = wrapElement.closest('.locker-block');
              if($locker.length !=0 ){
                  //$locker.find('.table-locker-block').remove();
                  $locker.find('.view-all-loader').remove();
                  wrapElement.unwrap()
              }
              else{
                  var loader = wrapElement.find('.view-all-loader');
                  if(loader.length)
                      loader.remove();
                      $ji('.load-more-products').show();
              }
          }
          else{ // set timeout and try clear loader after sometime
              setTimeout(function(){
                  window.IWDB2B.Decorator.unLockBlock(wrapElement);
              }, 10000);
          }
      },

      backToTopQueue: function(){
          $ji(document).on('click','#b2b-back-queue',function(e){
              e.preventDefault();
              $ji("html, body").animate({ scrollTop: 0 }, "slow");
          });

          $ji(window).resize(function(){
              window.IWDB2B.Decorator.widthWindow = $ji(window).width();

              if (window.IWDB2B.Decorator.widthWindow<1024){
                  $ji('.b2b-selected-items .table-header ').removeClass('fixed-header');
                  $ji('.reset-fixed').addClass('hidden');

                  window.IWDB2B.Decorator.setTableHeaderPosition();
              }
          });

          $ji(window).scroll(function(){

              var $table = $ji('.b2b-selected-items .div-table'),
              $tableHeader = $ji('.b2b-selected-items .table-header');

              if (!$table.length || !$tableHeader.length)
                  return;

              var tablePosition = $table.offset().top;

              if (window.IWDB2B.Decorator.widthWindow<=1024){
                  return;
              }
              position = $ji(window).scrollTop();

              if (position >= tablePosition) {
                  var height = $table.height();

                  if (position>=tablePosition+height-50){
                      $tableHeader.removeClass('fixed-header');
                      $ji('.reset-fixed').addClass('hidden');
                      window.IWDB2B.Decorator.setTableHeaderPosition();
                  }else{
                      if(!$tableHeader.hasClass('fixed-header')){

                          $tableHeader.stop();
                          $tableHeader.addClass('fixed-header').css('top','-75px');
                          $ji('.reset-fixed').removeClass('hidden');

                          window.IWDB2B.Decorator.setTableHeaderPosition();

                          $tableHeader.animate({
                                  top: "+=75"
                          }, 200, function() {
                                  // Animation complete.
                          });
                      }
                  }
              }else{
                  $ji('.b2b-selected-items .table-header ').removeClass('fixed-header');
                  $ji('.reset-fixed').addClass('hidden');

                  window.IWDB2B.Decorator.setTableHeaderPosition();
              }

          });

      },

      tierPrice: function(){
      },

      initThumbnailSlider: function(){
          if(!$ji('.b2b-product-slider').length)
              return;

          $ji('.b2b-product-slider').jcarousel({
              // Configuration goes here
          });

          $ji('.b2b-jcarousel-prev').click(function(e) {
              e.preventDefault();
              $ji('.b2b-product-slider').jcarousel('scroll', '-=1');
          });

          $ji('.b2b-jcarousel-next').click(function(e) {
              e.preventDefault();
              $ji('.b2b-product-slider').jcarousel('scroll', '+=1');
          });

      },

      block: function(){
          $ji(document).on('click','.b2b-wrapper .minimal-price-link',function(e){
              e.preventDefault();
          });
      },

      decorateTable: function(){
          $ji('.b2b-selected-items .table-row').each(function(){
              var maxRowColumnHeight = 0;
              $fields = $ji(this).find('div.left:not(.sub-row div.left)');
              $fields.each(function(){
                  $ji(this).css('height', '');

                  if ($ji(this).outerHeight() > maxRowColumnHeight){
                      maxRowColumnHeight = $ji(this).outerHeight();
                  }
              });
              $fields.each(function(){
                  $ji(this).css('height', maxRowColumnHeight);
              });
          });
      },

      getCellWidth: function($cell, any){
          if(typeof(any) == 'undefined' || any == undefined || !any)
              any = false;

          var cellWidth = $cell.css('width');
          var w1 = $cell.width();
          var w2 = $cell.outerWidth();

          if($cell.hasClass('th-attribute') || $cell.hasClass('th-thumbnail') || any){

              var width = $cell.attr('data-width');
              if(width != undefined){
                  var w = $cell.attr('data-full-width');
                  return parseInt(w);
              }

              var diff = w2-w1;

//              w2+=20;
              w1=w2-diff;

              $cell.attr('data-width', w1);
              $cell.attr('data-full-width', w2);

              return parseInt(w2);
          }

          if (cellWidth) {
              cellWidth = Math.ceil(1*cellWidth.replace('px', ''));
          } else {
              cellWidth = 0;
          }

          return parseInt(cellWidth);
      },

      setTableHeaderPosition: function(){
          if(!window.IWDB2B.Decorator.allowColumnsLogic())
              return false;

          var pageMargin = 10,
              tablePadding = 13;

          $ji('.table-header .fixed-wrapper').css({marginLeft: 0});

          var $fixedHeader = $ji('.table-header.fixed-header');
          if ($fixedHeader.length) {

              $ji('.table-header .visible-header').css({marginLeft: tablePadding+pageMargin});

              var tw = $ji('.b2b-selected-items .b2b-table-scroll').width();

              $fixedHeader.find('.visible-header').css({
                  width: $ji('.b2b-selected-items .b2b-table-scroll').width()
              });
              var $table = $ji('.b2b-selected-items .div-table');

              var tablePosition = $table.offset().left;
              var mrg = (window.IWDB2B.Decorator.widthWindow-tw)/2;

              var rp = window.IWDB2B.Decorator.widthWindow-tw-mrg;
              $fixedHeader.find('#b2b-reset-table').css({right:rp});

              if (window.IWDB2B.Decorator.IWDB2BTableWidthMode == 'fixed') {
                  var $table1 = $ji('.b2b-selected-items');
                  var tablePosition1 = $table1.offset().left+tablePadding;

                  var diff = tablePosition-tablePosition1;

                  ///////
                  $fixedHeader.find('.visible-header').css({
                      marginLeft: tablePosition1
                  });

                  if (diff < 0) {
                      $fixedHeader.find('.fixed-wrapper').css({
                          marginLeft: diff
                      });
                  }
              }
              else{
                  if (tablePosition < (tablePadding+pageMargin)) {

                      $fixedHeader.find('.visible-header').css({marginLeft: tablePadding+25});

                      $fixedHeader.find('.fixed-wrapper').css({
                          marginLeft: tablePosition - tablePadding - 25
                      });
                  } else {
                      $fixedHeader.find('.visible-header').css({
                          marginLeft: tablePosition
                      });
                  }
              }
          } else {
              $ji('.table-header .visible-header').css({width:'auto', marginLeft: 0});
          }

          //// reposition matrix table
          window.IWDB2B.Decorator.repositionMatrixTable();
      },

      repositionMatrixTable: function(){
          var tw = $ji('.b2b-selected-items .b2b-table-scroll').width();
          if(!$ji('.b2b-selected-items .div-table').length)
              return false;

          var $table = $ji('.b2b-selected-items .div-table');

          var pad_left = parseInt($ji('.b2b-selected-items').css("padding-left"));

          var tablePosition = $table.position().left;

          var left_tablePosition = tablePosition-pad_left;

          var full_width = $table.width();

          // left-aligned
          if(left_tablePosition > 0)
              left_tablePosition = 0;

          $ji('.matrix_table.matrix_align_left').each(function(){
              var w = $ji(this).width();
              var max_left = full_width-w;

              if(left_tablePosition > 0)
                  new_position = 0;
              else{
                  new_position = left_tablePosition*-1;
              }

              // check if no right overflow
              if(new_position > max_left)
                  new_position = max_left;

              $ji(this).animate({left: new_position});
          });

          // center-aligned
          $ji('.matrix_table.matrix_align_center').each(function(){
              var w = $ji(this).width();
              var max_left = full_width-w;

              var w1 = (tw-w)/2;

              if(w1 < 0)
                  new_position = left_tablePosition*-1;
              else
                  new_position = left_tablePosition*-1+w1;

              // check if no right overflow
              if(new_position > max_left)
                  new_position = max_left;

              $ji(this).animate({left: new_position});
          });

          // right-aligned
          $ji('.matrix_table.matrix_align_right').each(function(){
              var w = $ji(this).width();
              var max_left = full_width-w;

              var w1 = tw-w;

              if(w1 < 0)
                  new_position = left_tablePosition*-1;
              else
                  new_position = left_tablePosition*-1+w1;

              // check if no right overflow
              if(new_position > max_left)
                  new_position = max_left;

              $ji(this).animate({left: new_position});
          });

      },

      adjustMatrixTableWidth:function(){
          $ji('.matrix_grid').each(function(){
              var matrix_id = $ji(this).data('matrix_product');
              // check if need to expand tables
              if(window.IWDB2B.Decorator.matrixProducts[matrix_id]){
                  $ji(this).addClass('matrix_grid_expanded');
              }
              else {
                  $ji(this).removeClass('matrix_grid_expanded');
              }

              // check if need to expand rows
              $ji(this).find('.matrix-row-expand-link').each(function(){
                  var target = $ji(this).data('target');
                  if(window.IWDB2B.Decorator.matrixProductSections[target]){
                      $ji(this).addClass('matrix_row_expanded');
                      $ji('.'+target).addClass('matrix_fields_expanded');
                  }
                  else{
                      $ji(this).removeClass('matrix_row_expanded');
                      $ji('.'+target).removeClass('matrix_fields_expanded');
                  }
              });
              ///

              mt = $ji(this).find('.matrix_table');
              var mtw = 0;
              mt.find('.matrix_row_input.matrix_row_1 .matrix_col').each(function(){
                  mtw+=$ji(this).outerWidth();
              });

              mt.width(mtw);
          });
      },

      adjustTableWidth: function(fixThCellWidth){
          if(!window.IWDB2B.Decorator.allowColumnsLogic())
              return false;

          if (typeof fixThCellWidth == 'undefined') {
              fixThCellWidth = false;
          }

          var cellWidth,
              headerWidth = 0;
          $ji('.table-header .th-cell').each(function(){
              $cell = $ji(this);

              if(fixThCellWidth){ // clear attributes
                  $cell.removeAttr('data-width');
                  $cell.removeAttr('data-full-width');
                  $cell.css('width', '');

                  $cell.removeClass('width_set');
              }

              cellWidth = window.IWDB2B.Decorator.getCellWidth($cell);
              cellWidth = parseInt(cellWidth);

              headerWidth += cellWidth;

              if (fixThCellWidth && cellWidth > 0) {

                  if($cell.hasClass('th-attribute') || $cell.hasClass('th-thumbnail')){
                      var width = $cell.attr('data-width');
                      $ji($cell).width(width);

                      $ji($cell).addClass('width_set');
                  }
              }

          });

          if (headerWidth > 0) {
              $ji('.b2b-table-scroll .table-header .fixed-wrapper').width(headerWidth)
                  .parents('.div-table').width(headerWidth);
          }

          window.IWDB2B.Decorator.applyTablePositionMode();

          var changed = window.IWDB2B.Decorator.fillEmptySpace();

          if(changed)
              window.IWDB2B.Decorator.applyTablePositionMode();
      },


      fillEmptySpace: function(){

          var cellWidth,
          headerWidth = 0;

          $ji('.table-header .th-cell').each(function(){
              $cell = $ji(this);

              cellWidth = window.IWDB2B.Decorator.getCellWidth($cell);
              cellWidth = parseInt(cellWidth);

              headerWidth += cellWidth;

          });

//          var tw = $ji('.b2b-table-scroll').outerWidth();
          var tw = $ji('.b2b-selected-items').width();

          var diff = tw - headerWidth;

          if(diff > 0){
              var cf = 0;
              var cf_primary = 0;
              $ji('.table-header .th-cell.textarea').each(function(){
                  cf++;
                  if($ji(this).hasClass('textarea_primary'))
                      cf_primary++;
              });

              if(cf > 0){
                  var mid_w = Math.floor(diff / cf);

                  var skip_primary = false;

                  //// check if need to change product name field
                  if(cf_primary > 0 && (cf > cf_primary)){ // if there are primary and general columns
                      var primary_w = 0;
                      $ji('.table-header .th-cell.textarea.textarea_primary').each(function(){
                          primary_w = $ji(this).outerWidth();
                      });

                      var count_not_primary = 0;
                      var max_attr_w = 0;
                      $ji('.table-header .th-cell.textarea:not(.textarea_primary)').each(function(){
                          count_not_primary++;
                          var attr_w = $ji(this).outerWidth()+mid_w;
                          if(attr_w > max_attr_w){
                              max_attr_w = attr_w;
                          }
                      });

                      if(primary_w >= max_attr_w)
                          skip_primary = true;

                      if(skip_primary){
                          if(count_not_primary > 0)
                              mid_w = Math.floor(diff / count_not_primary);
                      }
                  }
                  ////

                  $ji('.table-header .th-cell.textarea').each(function(){

                      $cell = $ji(this);

                      // clear prev data
                      $cell.removeAttr('data-width');
                      $cell.removeAttr('data-full-width');
                      $cell.css('width', '');

                      $cell.removeClass('width_set');

                      // get real width
                      cellWidth = window.IWDB2B.Decorator.getCellWidth($cell);
                      cellWidth = parseInt(cellWidth);

                      var skip_this = false;

                      $cell.addClass('width_set');

                      if($cell.hasClass('th-attribute')){
                          var cellWidth = $cell.attr('data-width');
                          cellWidth = parseInt(cellWidth);
                      }
                      else{
                          if(!skip_primary){
                              var w1 = $cell.width();
                              var w2 = $cell.outerWidth();
                              var d = w2-w1;
                              var new_w = cellWidth-d;

                              cellWidth = new_w;

                              $cell.addClass('width_modified');
                          }
                          else
                              skip_this = true;
                      }

                      if(!skip_this){
                          // set new width
                          cellWidth+=mid_w;

                          $cell.removeAttr('data-width');
                          $cell.removeAttr('data-full-width');
                          $cell.css('width', '');

                          $cell.width(cellWidth);

                          // apply attributes
                          window.IWDB2B.Decorator.getCellWidth($cell);
                      }

                  });
              }
              else{ // increase width of each attribute field
                  var cf = 0;
                  $ji('.table-header .th-cell.th-attribute').each(function(){
                      cf++;
                  });

                  var no_attr = true;
                  var cells_class = '';
                  if(cf > 0){
                      cells_class = '.table-header .th-cell.th-attribute';
                      no_attr = false;
                  }
                  else{
                      cells_class = '.table-header .th-cell';
                      $ji('.table-header .th-cell').each(function(){
                          cf++;
                      });
                  }

                  if(cf > 0){
                      var mid_w = Math.floor(diff / cf);

                      var extra_width = 0;
                      if($ji('#quick-list-products').hasClass('reorder_table'))
                          extra_width = 1;

                      $ji(cells_class).each(function(){
                          $cell = $ji(this);

                          // clear prev data
                          $cell.removeAttr('data-width');
                          $cell.removeAttr('data-full-width');
                          $cell.css('width', '');

                          $cell.removeClass('width_set');

                          // get real width
                          cellWidth = window.IWDB2B.Decorator.getCellWidth($cell);
                          cellWidth = parseInt(cellWidth);

                          $cell.addClass('width_set');

                          cellWidth_before = cellWidth;

                          var cellWidth = $cell.attr('data-width');
                          if(cellWidth == undefined){
                              cellWidth = cellWidth_before;
                              if(extra_width == 0){
                                  if($ji('#quick-list-products').hasClass('reorder_table'))
                                      extra_width = 5;
                                  else
                                      extra_width = 10;
                              }
                              else
                                  cellWidth = cellWidth-extra_width;
                          }
                          else
                              cellWidth = parseInt(cellWidth);

                          // set new width
                          cellWidth+=mid_w;

                          $cell.removeAttr('data-width');
                          $cell.removeAttr('data-full-width');
                          $cell.css('width', '');

                          $cell.addClass('width_set');

                          $cell.width(cellWidth);

                          if(no_attr)
                              $cell.addClass('product-field-wider');

                          // apply attributes
                          window.IWDB2B.Decorator.getCellWidth($cell, no_attr);

                      });

                  }

              }

              headerWidth = 0;

              $ji('.table-header .th-cell').each(function(){
                  $cell = $ji(this);

                  cellWidth = window.IWDB2B.Decorator.getCellWidth($cell);
                  cellWidth = parseInt(cellWidth);

                  headerWidth += cellWidth;
              });

              $ji('.b2b-table-scroll .table-header .fixed-wrapper').width(headerWidth)
                  .parents('.div-table').width(headerWidth);

              return true;
          }

          return false;
      },

      adjustTableHeight: function(){
          if(!window.IWDB2B.Decorator.allowColumnsLogic())
              return false;

          var $contentHolder = $ji('#quick-list-products'),
              $tableBlock = $ji('.b2b-selected-items');

          var h = $tableBlock.outerHeight();

          if($contentHolder.hasClass('quick_table'))
              h+=52;

          $contentHolder.height(h);

      },

      adjustBodyCellsWidth: function(){
          if(!window.IWDB2B.Decorator.allowColumnsLogic())
              return false;

          $ji('.table-header .th-cell').each(function(idx){
              $cell = $ji(this);
              var cellWidth = window.IWDB2B.Decorator.getCellWidth($cell);
              $ji('.b2b-selected-items .table-body .table-row, .table-body .table-row .sub-row').each(function(){
                  var cell = $ji(this).find('.body-cell')
                      .not('.download-icon')
                      .eq(idx);

                  if(cell){
                      if(cell.hasClass('product-attribute') || cell.hasClass('product-thumbnail')){
                          var w = $cell.attr('data-width');
                          cell.width(w);
                      }
                      else{
                          if(($cell.hasClass('textarea_primary') && $cell.hasClass('width_modified')) || $cell.hasClass('product-field-wider')){
                              var w1 = cell.width();
                              var w2 = cell.outerWidth();
                              var d = w2-w1;
                              var new_w = cellWidth-d;

                              if(cell.hasClass('has_download')){
                                  var down_width = 31;
                                  var down = cell.next();
                                  if(down && down.hasClass('download-icon')){
                                      down_width = down.outerWidth();
                                  }

                                  new_w-=down_width;
                              }

                              cell.width(new_w);
                          }
                      }
                  }

              });
          });
          window.IWDB2B.Decorator.decorateTable();
      },

      applyTablePositionMode: function(){
          if(!window.IWDB2B.Decorator.allowColumnsLogic())
              return false;

          var $contentHolder = $ji('#quick-list-products'),
              $tableBlock = $ji('.b2b-selected-items'),
              pageMargin = 25,
              borderWidth = 3,
              tablePadding = 13,
              fullTableWidth = document.body.clientWidth - 2*pageMargin,
              fixedTableWidth = 1000-(2*tablePadding);

          if (typeof window.IWDB2B.Decorator.IWDB2BTableWidthMode == 'undefined') {
              window.IWDB2B.Decorator.IWDB2BTableWidthMode = 'auto';
          }

          if (window.IWDB2B.Decorator.IWDB2BTableWidthMode == 'full') {
              $tableBlock.css({
                  position: 'absolute',
                  left: pageMargin,
                  width: fullTableWidth
              });
          }
          else if (window.IWDB2B.Decorator.IWDB2BTableWidthMode == 'fixed') {
              var headerWidth = 0,
              tableWidth = 0;

              headerWidth = fixedTableWidth;

              if (headerWidth > fullTableWidth) {
                  tableWidth = fullTableWidth;
              } else {
                  pageMargin = Math.ceil((fullTableWidth - headerWidth - 2*borderWidth + pageMargin+borderWidth ) / 2);

                  tableWidth = headerWidth + 2*tablePadding;
              }
              $tableBlock.css({
                  position: 'absolute',
                  left: pageMargin,
                  width: tableWidth
              });
          } else if (window.IWDB2B.Decorator.IWDB2BTableWidthMode == 'auto') {
              var headerWidth = 0,
                  tableWidth = 0;
              $ji('.table-header .th-cell').each(function(){
                  $cell = $ji(this);
                  var w = window.IWDB2B.Decorator.getCellWidth($cell);
                  headerWidth += w;
              });

              if (headerWidth > fullTableWidth) {
                  tableWidth = fullTableWidth;
              } else {
                  pageMargin = Math.ceil((fullTableWidth - headerWidth - 2*borderWidth + pageMargin+borderWidth ) / 2);
                  tableWidth = headerWidth + 2*tablePadding;
              }
              $tableBlock.css({
                  position: 'absolute',
                  left: pageMargin,
                  width: tableWidth
              });
          }
          window.IWDB2B.Decorator.setTableHeaderPosition();
          window.IWDB2B.Decorator.adjustTableHeight();
      },

      /////////////////////////////
      initOpenedModal: function(modal_identifier){
          window.IWDB2B.Decorator.executeValignModal(modal_identifier);

          $ji(window).on('resize', function() {
              window.IWDB2B.Decorator.valignModal(modal_identifier);
          });
      },

      valignModal: function(modal_identifier){
          var $dialog  = $ji(modal_identifier);
          if(!$dialog.is(':visible'))
              return;

          if(!window.IWDB2B.Decorator.popupResize){
              window.IWDB2B.Decorator.popupResize = true;
              setTimeout(function(){
                  window.IWDB2B.Decorator.popupResize = null;
                  window.IWDB2B.Decorator.executeValignModal(modal_identifier);
              },1000);
          }
      },

      executeValignModal: function(modal_identifier){
          var $dialog  = $ji(modal_identifier);
          if(!$dialog.is(':visible'))
              return;

          offset       = ($ji(window).height() - $dialog.outerHeight()) / 2;
          bottomMargin = parseInt($dialog.css('marginBottom'), 10);
          // Make sure you don't hide the top part of the modal w/ a negative margin if it's longer than the screen height, and keep the margin equal to the bottom margin of the modal
          if(offset < bottomMargin)
              offset = bottomMargin;

          $dialog.css("margin-top", offset);
      },

      //////// custom columns
      initWindowEventDecorators: function(){
          if(!window.IWDB2B.Decorator.allowColumnsLogic())
              return false;

          $ji(window).resize(function(){
              window.IWDB2B.Decorator.resizeTable();
              // double this, to fix empty space after quick list table
              window.IWDB2B.Decorator.resizeTable();
          });

          $ji(document).on('click', '.page-wrapper', function(e){
              target = $ji(e.target);
              if(target.hasClass('b2b-main-wrapper') || target.parents('.b2b-main-wrapper').length){
              }
              else{
                  setTimeout(function(){
                      window.IWDB2B.Decorator.resizeTable();
                      // double this, to fix empty space after quick list table
                      window.IWDB2B.Decorator.resizeTable();
                  },300);
              }
          });

          window.IWDB2B.Decorator.resizeTable(100);
      },

      resizeTable: function(adjust_height, decorate){
          window.IWDB2B.Decorator.adjustMatrixTableWidth();
          window.IWDB2B.Decorator.adjustTableWidth(true);
          window.IWDB2B.Decorator.adjustBodyCellsWidth();

          window.IWDB2B.Decorator.reInitTableScroll();
          window.IWDB2B.Decorator.searchMatrixTablesScrollable();

          if(typeof(adjust_height) != 'undefined' && adjust_height != undefined && adjust_height){
              setTimeout(function(){

                  if(typeof(decorate) != 'undefined' && decorate != undefined && decorate){
                      window.IWDB2B.Decorator.decorateTable();
                  }

                  window.IWDB2B.Decorator.adjustTableHeight();
              }, adjust_height);
          }

          $ji('.matrix_grid').each(function(){
            $ji(this).removeClass('matrix_grid_expanded');
          });
      },

      allowColumnsLogic: function(){
          if(typeof(pageType) == 'undefined')
              return false;

          return (pageType == "quick" || pageType == "prev" || pageType == "products");
      },

      hideCartMessages: function(){
          $ji('.b2b_cart_error_msg').remove();
      },

      initMatrixInfo: function(){
          $ji(document).on('click', '.matrix-row-expand-link', function(e){
              e.preventDefault();
              target = $ji(this).data('target');

              if($ji(this).hasClass('matrix_row_expanded')){
                  $ji('.'+target).removeClass('matrix_fields_expanded');
                  $ji(this).removeClass('matrix_row_expanded');

                  $ji(this).children('a').children().removeClass('fa-angle-up');
                  $ji(this).children('a').children().addClass('fa-angle-down');

                  // add info about expanded row
                  window.IWDB2B.Decorator.matrixProductSections[target] = false;

                  // reinit scroll for matrix
                  // destroy scrolling for matrix
                  grid_table = $ji(this).parents('.matrix_table');
                  window.IWDB2B.Decorator.destroyMatrixTableScroll(grid_table);

                  // init scrolling for matrix
                  grid_table = $ji(this).parents('.matrix_table');
                  window.IWDB2B.Decorator.initMatrixTableScroll(grid_table);
                  ///
              }
              else{
                  $ji('.'+target).addClass('matrix_fields_expanded');
                  $ji(this).addClass('matrix_row_expanded');

                  $ji(this).children('a').children().removeClass('fa-angle-down');
                  $ji(this).children('a').children().addClass('fa-angle-up');

                  // remove info about expanded product
                  window.IWDB2B.Decorator.matrixProductSections[target] = true;
              }

              window.IWDB2B.Decorator.adjustTableHeight();
          });

      },

      showhideMatrix: function($this){
          var grid = $this.parents('.matrix_grid');
          var matrix_product = grid.data('matrix_product');
          if(!grid.hasClass('matrix_grid_expanded')){
              grid.addClass('matrix_grid_expanded');

              // init scrolling for matrix
              grid_table = grid.find('.matrix_table');
              window.IWDB2B.Decorator.initMatrixTableScroll(grid_table);

              // add info about expanded product
              window.IWDB2B.Decorator.matrixProducts[matrix_product] = matrix_product;
          }
          else{
              // destroy scrolling for matrix
              grid_table = grid.find('.matrix_table');
              window.IWDB2B.Decorator.destroyMatrixTableScroll(grid_table);

              grid.removeClass('matrix_grid_expanded');

              // remove info about expanded product
              window.IWDB2B.Decorator.matrixProducts[matrix_product] = false;
          }

          window.IWDB2B.Decorator.adjustTableHeight();
      },

      initPriceTooltip: function(){
          $ji(document).on('mouseover','.tier-price-container',function(e){
              var html = $ji(this).find('ul').first().clone();

              $ji('#b2b-price-tooltip').html('').append(html);

              var w = $ji('#b2b-price-tooltip').width();

              window_width = window.IWDB2B.Decorator.widthWindow;
              x_pos = e.pageX;
              if((x_pos+w) > window_width)
                  x_pos = window_width-w-20;
              else
                  x_pos = x_pos-5;

              $ji('#b2b-price-tooltip').css('top', e.pageY);
              $ji('#b2b-price-tooltip').css('left',x_pos);
              $ji('#b2b-price-tooltip').show();

          });

          $ji(document).on('mouseleave','#b2b-price-tooltip',function(e){
              $ji('#b2b-price-tooltip').html('').hide();
          });
      },

      // var disabled = document.getElementsByName("payments[]");
      // console.log(disabled);
      // if($ji(disabled).siblings().find('span').html() === '(disabled)') {
      //     console.log("set to disabled");
      //     $ji(disbaled).disabled = true;
      // }
      // else {
      //     $ji(disbaled).disabled = false;
      // }
  };

/////////

  window.IWDB2B.Tooltip = {
      block:null,
      init: function(){
          this.addBlock();
          this.createEvent();
      },
      addBlock: function(){
          this.block = $ji('<a />').addClass('b2b-tooltip');
          this.block.appendTo('body');
          this.block.hide();
      },
      createEvent: function(){
          $ji(document).on('mousemove','[data-toggle="tooltip"]',function(e){
              if ($ji(this).data('title')==null){
                  if ($ji(this).attr('title')==""){
                      return;
                  }else{
                      $ji(this).data('title', $ji(this).attr('title'));
                      $ji(this).removeAttr('title');
                      $ji()
                  }
              }

              var xOffset = e.pageX +10;
              var yOffset = e.pageY +10;

              window.IWDB2B.Tooltip.block.show().html($ji(this).data('title'));
              window.IWDB2B.Tooltip.block.css('left', xOffset).css('top', yOffset);
          });

          $ji(document).on('mouseleave','[data-toggle="tooltip"]',function(e){
              window.IWDB2B.Tooltip.block.hide().empty();
          });
      }
  };

/////////

  return {
      init: function(){
          window.IWDB2B.Init.init();
      },
      get: function(){
          return window.IWDB2B;
      }
  };

}));
